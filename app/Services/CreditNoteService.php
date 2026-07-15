<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Exception;
use PDO;
use Throwable;

class CreditNoteService
{
    private PDO $db;

    private Invoice $invoiceModel;

    private InvoiceItem $invoiceItemModel;

    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->db = Database::getConnection();

        $this->invoiceModel = new Invoice();

        $this->invoiceItemModel =
            new InvoiceItem();

        $this->auditLogService =
            new AuditLogService();
    }

    public function createDraft(
        int $originalInvoiceId,
        int $companyId,
        int $userId,
        string $reason,
        array $requestedQuantities
    ): array {
        $reason = trim($reason);

        if ($reason === '') {
            return [
                'success' => false,
                'credit_note_id' => null,
                'error' =>
                'Credit note reason is required.',
            ];
        }

        if (mb_strlen($reason) > 500) {
            return [
                'success' => false,
                'credit_note_id' => null,
                'error' =>
                'Credit note reason must be maximum 500 characters.',
            ];
        }

        try {
            $this->db->beginTransaction();

            $originalInvoice =
                $this->invoiceModel
                ->findForUpdate(
                    $originalInvoiceId,
                    $companyId
                );

            if ($originalInvoice === null) {
                throw new Exception(
                    'Original invoice was not found.'
                );
            }

            if (
                (string) $originalInvoice['document_type'] !== 'invoice'
            ) {
                throw new Exception(
                    'A credit note can only be created for an invoice.'
                );
            }

            if (
                (string) $originalInvoice['status'] !== 'issued'
            ) {
                throw new Exception(
                    'A credit note can only be created for an issued invoice.'
                );
            }

            $originalItems =
                $this->invoiceItemModel
                ->allByInvoice(
                    $originalInvoiceId,
                    $companyId
                );

            if (empty($originalItems)) {
                throw new Exception(
                    'The original invoice has no items.'
                );
            }

            $usage =
                $this->invoiceItemModel
                ->creditUsageByOriginalInvoice(
                    $originalInvoiceId,
                    $companyId
                );

            $preparedItems = [];

            foreach ($originalItems as $item) {
                $itemId = (int) $item['id'];

                $requestedQuantity = 0.00;

                if (
                    isset(
                        $requestedQuantities[$itemId]
                    ) &&
                    is_scalar(
                        $requestedQuantities[$itemId]
                    )
                ) {
                    $quantityValue = str_replace(
                        ',',
                        '.',
                        trim(
                            (string)
                            $requestedQuantities[$itemId]
                        )
                    );

                    if (is_numeric($quantityValue)) {
                        $requestedQuantity =
                            (float) $quantityValue;
                    }
                }

                if ($requestedQuantity <= 0) {
                    continue;
                }

                $originalQuantity =
                    (float) $item['quantity'];

                $creditedQuantity = 0.00;
                $creditedDiscount = 0.00;
                $creditedNet = 0.00;
                $creditedTax = 0.00;
                $creditedTotal = 0.00;

                if (isset($usage[$itemId])) {
                    $creditedQuantity =
                        (float) $usage[$itemId]['credited_quantity'];

                    $creditedDiscount =
                        (float) $usage[$itemId]['credited_discount_amount'];

                    $creditedNet =
                        (float) $usage[$itemId]['credited_net_amount'];

                    $creditedTax =
                        (float) $usage[$itemId]['credited_tax_amount'];

                    $creditedTotal =
                        (float) $usage[$itemId]['credited_total_amount'];
                }

                $remainingQuantity = round(
                    $originalQuantity -
                        $creditedQuantity,
                    3
                );

                if (
                    $requestedQuantity >
                    $remainingQuantity + 0.0001
                ) {
                    throw new Exception(
                        'Credit quantity exceeds the remaining quantity for: ' .
                            (string) $item['description']
                    );
                }

                $isFullRemainingCredit =
                    abs(
                        $requestedQuantity -
                            $remainingQuantity
                    ) < 0.0001;

                if ($isFullRemainingCredit) {
                    $remainingDiscount =
                        (float) $item['discount_amount'] +
                        $creditedDiscount;

                    $remainingNet =
                        (float) $item['net_amount'] +
                        $creditedNet;

                    $remainingTax =
                        (float) $item['tax_amount'] +
                        $creditedTax;

                    $remainingTotal =
                        (float) $item['total_amount'] +
                        $creditedTotal;

                    $creditDiscount =
                        -round(
                            $remainingDiscount,
                            2
                        );

                    $creditNet =
                        -round(
                            $remainingNet,
                            2
                        );

                    $creditTax =
                        -round(
                            $remainingTax,
                            2
                        );

                    $creditTotal =
                        -round(
                            $remainingTotal,
                            2
                        );
                } else {
                    $ratio =
                        $requestedQuantity /
                        $originalQuantity;

                    $creditDiscount =
                        -round(
                            (float) $item['discount_amount'] * $ratio,
                            2
                        );

                    $creditNet =
                        -round(
                            (float) $item['net_amount'] * $ratio,
                            2
                        );

                    $creditTax =
                        -round(
                            (float) $item['tax_amount'] * $ratio,
                            2
                        );

                    $creditTotal =
                        -round(
                            (float) $item['total_amount'] * $ratio,
                            2
                        );
                }

                $preparedItems[] = [
                    'source_invoice_item_id' =>
                    $itemId,

                    'product_id' =>
                    $item['product_id'] === null
                        ? null
                        : (int) $item['product_id'],

                    'description' =>
                    (string) $item['description'],

                    'product_internal_code' =>
                    $this->nullableString(
                        $item['product_internal_code']
                    ),

                    'quantity' =>
                    round(
                        $requestedQuantity,
                        3
                    ),

                    'unit' =>
                    (string) $item['unit'],

                    'unit_price' =>
                    (float) $item['unit_price'],

                    'discount_amount' =>
                    $creditDiscount,

                    'vat_rate' =>
                    (float) $item['vat_rate'],

                    'net_amount' =>
                    $creditNet,

                    'tax_amount' =>
                    $creditTax,

                    'total_amount' =>
                    $creditTotal,
                ];
            }

            if (empty($preparedItems)) {
                throw new Exception(
                    'Select at least one quantity to credit.'
                );
            }

            $subtotal = 0.00;
            $discountAmount = 0.00;
            $taxAmount = 0.00;
            $totalAmount = 0.00;

            foreach ($preparedItems as $item) {
                $subtotal +=
                    (float) $item['net_amount'] +
                    (float) $item['discount_amount'];

                $discountAmount +=
                    (float) $item['discount_amount'];

                $taxAmount +=
                    (float) $item['tax_amount'];

                $totalAmount +=
                    (float) $item['total_amount'];
            }

            $documentDate = date('Y-m-d');

            $creditNoteId =
                $this->invoiceModel->create([
                    'company_id' => $companyId,

                    'client_id' =>
                    (int) $originalInvoice['client_id'],

                    'sale_id' => null,

                    'related_invoice_id' =>
                    $originalInvoiceId,

                    'created_by_user_id' =>
                    $userId,

                    'document_type' =>
                    'credit_note',

                    'invoice_number' => null,

                    'invoice_date' =>
                    $documentDate,

                    'supply_date' =>
                    $documentDate,

                    'due_date' => null,

                    'status' => 'draft',

                    'currency' =>
                    (string) $originalInvoice['currency'],

                    'vat_registered' =>
                    (int) $originalInvoice['vat_registered'],

                    'prices_include_vat' =>
                    (int) $originalInvoice['prices_include_vat'],

                    'default_vat_rate' =>
                    (float) $originalInvoice['default_vat_rate'],

                    'supplier_legal_name' =>
                    (string) $originalInvoice['supplier_legal_name'],

                    'supplier_eik' =>
                    (string) $originalInvoice['supplier_eik'],

                    'supplier_vat_number' =>
                    $this->nullableString(
                        $originalInvoice['supplier_vat_number']
                    ),

                    'supplier_manager_name' =>
                    $this->nullableString(
                        $originalInvoice['supplier_manager_name']
                    ),

                    'supplier_address' =>
                    (string) $originalInvoice['supplier_address'],

                    'supplier_city' =>
                    (string) $originalInvoice['supplier_city'],

                    'supplier_postal_code' =>
                    $this->nullableString(
                        $originalInvoice['supplier_postal_code']
                    ),

                    'supplier_country' =>
                    (string) $originalInvoice['supplier_country'],

                    'supplier_phone' =>
                    $this->nullableString(
                        $originalInvoice['supplier_phone']
                    ),

                    'supplier_email' =>
                    $this->nullableString(
                        $originalInvoice['supplier_email']
                    ),

                    'supplier_bank_name' =>
                    $this->nullableString(
                        $originalInvoice['supplier_bank_name']
                    ),

                    'supplier_iban' =>
                    $this->nullableString(
                        $originalInvoice['supplier_iban']
                    ),

                    'supplier_bic' =>
                    $this->nullableString(
                        $originalInvoice['supplier_bic']
                    ),

                    'client_type' =>
                    (string) $originalInvoice['client_type'],

                    'client_display_name' =>
                    (string) $originalInvoice['client_display_name'],

                    'client_legal_name' =>
                    (string) $originalInvoice['client_legal_name'],

                    'client_eik' =>
                    $this->nullableString(
                        $originalInvoice['client_eik']
                    ),

                    'client_vat_number' =>
                    $this->nullableString(
                        $originalInvoice['client_vat_number']
                    ),

                    'client_address' =>
                    (string) $originalInvoice['client_address'],

                    'client_city' =>
                    (string) $originalInvoice['client_city'],

                    'client_postal_code' =>
                    $this->nullableString(
                        $originalInvoice['client_postal_code']
                    ),

                    'client_country' =>
                    (string) $originalInvoice['client_country'],

                    'client_email' =>
                    $this->nullableString(
                        $originalInvoice['client_email']
                    ),

                    'subtotal' =>
                    round($subtotal, 2),

                    'discount_amount' =>
                    round(
                        $discountAmount,
                        2
                    ),

                    'tax_amount' =>
                    round($taxAmount, 2),

                    'total_amount' =>
                    round($totalAmount, 2),

                    'note' => $reason,

                    'correction_reason' =>
                    $reason,
                ]);

            foreach ($preparedItems as $item) {
                $this->invoiceItemModel->create([
                    'invoice_id' =>
                    $creditNoteId,

                    'company_id' =>
                    $companyId,

                    'product_id' =>
                    $item['product_id'],

                    'source_invoice_item_id' =>
                    $item['source_invoice_item_id'],

                    'description' =>
                    $item['description'],

                    'product_internal_code' =>
                    $item['product_internal_code'],

                    'quantity' =>
                    $item['quantity'],

                    'unit' =>
                    $item['unit'],

                    'unit_price' =>
                    $item['unit_price'],

                    'discount_amount' =>
                    $item['discount_amount'],

                    'vat_rate' =>
                    $item['vat_rate'],

                    'net_amount' =>
                    $item['net_amount'],

                    'tax_amount' =>
                    $item['tax_amount'],

                    'total_amount' =>
                    $item['total_amount'],
                ]);
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'credit_note',
                $creditNoteId,
                'Created credit note draft for invoice ' .
                    (string) $originalInvoice['invoice_number']
            );

            $this->db->commit();

            return [
                'success' => true,
                'credit_note_id' =>
                $creditNoteId,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'credit_note_id' => null,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $stringValue = trim(
            (string) $value
        );

        if ($stringValue === '') {
            return null;
        }

        return $stringValue;
    }
}
