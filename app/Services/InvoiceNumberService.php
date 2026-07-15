<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\DocumentSequence;
use App\Models\Invoice;
use Exception;
use PDO;
use Throwable;

class InvoiceNumberService
{
    private const INVOICE_TYPE = 'invoice';

    private const CREDIT_NOTE_TYPE =
    'credit_note';

    private const MIN_NUMBER = 1;

    private const MAX_NUMBER = 9999999999;

    private PDO $db;

    private DocumentSequence $sequenceModel;

    private Invoice $invoiceModel;

    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->db = Database::getConnection();

        $this->sequenceModel =
            new DocumentSequence();

        $this->invoiceModel =
            new Invoice();

        $this->auditLogService =
            new AuditLogService();
    }

    public function information(
        int $companyId
    ): array {
        $this->sequenceModel->ensureExists(
            $companyId,
            self::INVOICE_TYPE
        );

        $sequence =
            $this->sequenceModel
            ->findByCompanyAndType(
                $companyId,
                self::INVOICE_TYPE
            );

        if ($sequence === null) {
            return [
                'next_number' => 1,
                'next_invoice_number' =>
                $this->formatNumber(1),

                'last_issued_number' => null,
                'last_invoice_number' => null,

                'can_change_start' => false,
            ];
        }

        $nextNumber =
            (int) $sequence['next_number'];

        $lastIssuedNumber = null;

        if (
            isset($sequence['last_issued_number']) &&
            $sequence['last_issued_number'] !== null
        ) {
            $lastIssuedNumber =
                (int) $sequence['last_issued_number'];
        }

        $issuedCount =
            $this->invoiceModel
            ->countIssuedByCompany(
                $companyId
            );

        $lastInvoiceNumber = null;

        if ($lastIssuedNumber !== null) {
            $lastInvoiceNumber =
                $this->formatNumber(
                    $lastIssuedNumber
                );
        }

        return [
            'next_number' => $nextNumber,

            'next_invoice_number' =>
            $this->formatNumber(
                $nextNumber
            ),

            'last_issued_number' =>
            $lastIssuedNumber,

            'last_invoice_number' =>
            $lastInvoiceNumber,

            'can_change_start' =>
            $lastIssuedNumber === null &&
                $issuedCount === 0,
        ];
    }

    public function configureStartingNumber(
        int $companyId,
        int $nextNumber,
        int $userId
    ): array {
        if (
            $nextNumber < self::MIN_NUMBER ||
            $nextNumber > self::MAX_NUMBER
        ) {
            return [
                'success' => false,
                'error' =>
                'Starting invoice number must be between 1 and 9999999999.',
            ];
        }

        try {
            $this->db->beginTransaction();

            $this->sequenceModel->ensureExists(
                $companyId,
                self::INVOICE_TYPE
            );

            $sequence =
                $this->sequenceModel
                ->lockForUpdate(
                    $companyId,
                    self::INVOICE_TYPE
                );

            if ($sequence === null) {
                throw new Exception(
                    'Invoice sequence was not found.'
                );
            }

            $issuedCount =
                $this->invoiceModel
                ->countIssuedByCompany(
                    $companyId
                );

            if (
                $sequence['last_issued_number'] !== null ||
                $issuedCount > 0
            ) {
                throw new Exception(
                    'The starting invoice number cannot be changed after the first invoice has been issued.'
                );
            }

            $updated =
                $this->sequenceModel
                ->setStartingNumber(
                    (int) $sequence['id'],
                    $nextNumber
                );

            if (!$updated) {
                throw new Exception(
                    'Unable to update the starting invoice number.'
                );
            }

            $formattedNumber =
                $this->formatNumber(
                    $nextNumber
                );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'update',
                'invoice',
                null,
                'Configured starting invoice number: ' .
                    $formattedNumber
            );

            $this->db->commit();

            return [
                'success' => true,
                'error' => null,
                'next_invoice_number' =>
                $formattedNumber,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function issue(
        int $documentId,
        int $companyId,
        int $userId
    ): array {
        $preview =
            $this->invoiceModel
            ->findByIdAndCompany(
                $documentId,
                $companyId
            );

        if ($preview === null) {
            return [
                'success' => false,
                'issued' => false,
                'invoice_number' => null,
                'document_type' => null,
                'error' =>
                'Document was not found.',
            ];
        }

        $documentType =
            (string) $preview['document_type'];

        if (
            $documentType !==
            self::INVOICE_TYPE &&
            $documentType !==
            self::CREDIT_NOTE_TYPE
        ) {
            return [
                'success' => false,
                'issued' => false,
                'invoice_number' => null,
                'document_type' => null,
                'error' =>
                'Unsupported document type.',
            ];
        }

        try {
            $this->db->beginTransaction();

            /*
             * Sequence се заключва първо.
             */
            $this->sequenceModel->ensureExists(
                $companyId,
                $documentType
            );

            $sequence =
                $this->sequenceModel
                ->lockForUpdate(
                    $companyId,
                    $documentType
                );

            if ($sequence === null) {
                throw new Exception(
                    'Document number sequence was not found.'
                );
            }

            /*
             * При credit note оригиналната фактура
             * трябва все още да е издадена.
             */
            if (
                $documentType ===
                self::CREDIT_NOTE_TYPE
            ) {
                $relatedInvoiceId =
                    (int) $preview['related_invoice_id'];

                if ($relatedInvoiceId <= 0) {
                    throw new Exception(
                        'The credit note does not reference an invoice.'
                    );
                }

                $originalInvoice =
                    $this->invoiceModel
                    ->findForUpdate(
                        $relatedInvoiceId,
                        $companyId
                    );

                if (
                    $originalInvoice === null ||
                    (string) $originalInvoice['status'] !== 'issued'
                ) {
                    throw new Exception(
                        'The original invoice is not available for this credit note.'
                    );
                }
            }

            $document =
                $this->invoiceModel
                ->findForUpdate(
                    $documentId,
                    $companyId
                );

            if ($document === null) {
                throw new Exception(
                    'Document was not found.'
                );
            }

            $existingNumber = '';

            if (
                isset($document['invoice_number']) &&
                $document['invoice_number'] !== null
            ) {
                $existingNumber = trim(
                    (string) $document['invoice_number']
                );
            }

            if (
                (string) $document['status'] ===
                'issued' &&
                $existingNumber !== ''
            ) {
                $this->db->commit();

                return [
                    'success' => true,
                    'issued' => false,
                    'invoice_number' =>
                    $existingNumber,
                    'document_type' =>
                    $documentType,
                    'error' => null,
                ];
            }

            if ($existingNumber !== '') {
                throw new Exception(
                    'The document contains a number but is not marked as issued.'
                );
            }

            if (
                (string) $document['status'] !==
                'draft'
            ) {
                throw new Exception(
                    'Only document drafts can be issued.'
                );
            }

            $issueDate = date('Y-m-d');

            /*
             * Due date се прилага само за фактури.
             * Credit notes нямат падеж в тази стъпка.
             */
            if (
                $documentType ===
                self::INVOICE_TYPE &&
                isset($document['due_date']) &&
                $document['due_date'] !== null &&
                trim(
                    (string) $document['due_date']
                ) !== '' &&
                (string) $document['due_date'] <
                $issueDate
            ) {
                throw new Exception(
                    'Due date cannot be before the invoice issue date.'
                );
            }

            $nextNumber =
                (int) $sequence['next_number'];

            if (
                $nextNumber < self::MIN_NUMBER ||
                $nextNumber > self::MAX_NUMBER
            ) {
                throw new Exception(
                    'The document sequence is outside the supported 10-digit range.'
                );
            }

            $documentNumber =
                $this->formatNumber(
                    $nextNumber
                );

            $documentUpdated =
                $this->invoiceModel
                ->markAsIssued(
                    $documentId,
                    $companyId,
                    $documentNumber,
                    $issueDate,
                    $userId
                );

            if (!$documentUpdated) {
                throw new Exception(
                    'The document could not be issued.'
                );
            }

            $sequenceUpdated =
                $this->sequenceModel
                ->advance(
                    (int) $sequence['id'],
                    $nextNumber,
                    $nextNumber + 1
                );

            if (!$sequenceUpdated) {
                throw new Exception(
                    'The document sequence could not be advanced.'
                );
            }

            $entityType = 'invoice';
            $documentLabel = 'invoice';

            if (
                $documentType ===
                self::CREDIT_NOTE_TYPE
            ) {
                $entityType = 'credit_note';
                $documentLabel = 'credit note';
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'issue',
                $entityType,
                $documentId,
                'Issued ' .
                    $documentLabel .
                    ' ' .
                    $documentNumber
            );

            $this->db->commit();

            return [
                'success' => true,
                'issued' => true,
                'invoice_number' =>
                $documentNumber,
                'document_type' =>
                $documentType,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'issued' => false,
                'invoice_number' => null,
                'document_type' =>
                $documentType,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    private function formatNumber(
        int $number
    ): string {
        return str_pad(
            (string) $number,
            10,
            '0',
            STR_PAD_LEFT
        );
    }
}
