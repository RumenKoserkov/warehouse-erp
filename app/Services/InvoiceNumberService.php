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
    private const DOCUMENT_TYPE = 'invoice';

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
            self::DOCUMENT_TYPE
        );

        $sequence =
            $this->sequenceModel
                ->findByCompanyAndType(
                    $companyId,
                    self::DOCUMENT_TYPE
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
                (int) $sequence[
                    'last_issued_number'
                ];
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
                self::DOCUMENT_TYPE
            );

            $sequence =
                $this->sequenceModel
                    ->lockForUpdate(
                        $companyId,
                        self::DOCUMENT_TYPE
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
        int $invoiceId,
        int $companyId,
        int $userId
    ): array {
        try {
            $this->db->beginTransaction();

            /*
             * Винаги заключваме sequence-а първо.
             * Това поддържа еднакъв lock order.
             */
            $this->sequenceModel->ensureExists(
                $companyId,
                self::DOCUMENT_TYPE
            );

            $sequence =
                $this->sequenceModel
                    ->lockForUpdate(
                        $companyId,
                        self::DOCUMENT_TYPE
                    );

            if ($sequence === null) {
                throw new Exception(
                    'Invoice sequence was not found.'
                );
            }

            $invoice =
                $this->invoiceModel
                    ->findForUpdate(
                        $invoiceId,
                        $companyId
                    );

            if ($invoice === null) {
                throw new Exception(
                    'Invoice was not found.'
                );
            }

            $existingNumber = '';

            if (
                isset($invoice['invoice_number']) &&
                $invoice['invoice_number'] !== null
            ) {
                $existingNumber = trim(
                    (string) $invoice[
                        'invoice_number'
                    ]
                );
            }

            if (
                (string) $invoice['status'] === 'issued' &&
                $existingNumber !== ''
            ) {
                $this->db->commit();

                return [
                    'success' => true,
                    'issued' => false,
                    'invoice_number' =>
                        $existingNumber,
                    'error' => null,
                ];
            }

            if ($existingNumber !== '') {
                throw new Exception(
                    'The invoice contains a number but is not marked as issued.'
                );
            }

            if (
                (string) $invoice['status'] !== 'draft'
            ) {
                throw new Exception(
                    'Only invoice drafts can be issued.'
                );
            }

            $issueDate = date('Y-m-d');

            if (
                isset($invoice['due_date']) &&
                $invoice['due_date'] !== null &&
                trim(
                    (string) $invoice['due_date']
                ) !== '' &&
                (string) $invoice['due_date'] <
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
                    'The invoice number sequence is outside the supported 10-digit range.'
                );
            }

            $invoiceNumber =
                $this->formatNumber(
                    $nextNumber
                );

            $invoiceUpdated =
                $this->invoiceModel
                    ->markAsIssued(
                        $invoiceId,
                        $companyId,
                        $invoiceNumber,
                        $issueDate,
                        $userId
                    );

            if (!$invoiceUpdated) {
                throw new Exception(
                    'The invoice could not be issued.'
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
                    'The invoice sequence could not be advanced.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'issue',
                'invoice',
                $invoiceId,
                'Issued invoice ' .
                $invoiceNumber
            );

            $this->db->commit();

            return [
                'success' => true,
                'issued' => true,
                'invoice_number' =>
                    $invoiceNumber,
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