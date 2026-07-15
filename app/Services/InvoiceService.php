<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Exception;
use PDO;
use Throwable;

class InvoiceService
{
    private PDO $db;

    private Invoice $invoiceModel;
    private InvoiceItem $invoiceItemModel;
    private Company $companyModel;
    private Client $clientModel;
    private Product $productModel;
    private Setting $settingModel;
    private Sale $saleModel;
    private SaleItem $saleItemModel;

    private TaxService $taxService;
    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->db = Database::getConnection();

        $this->invoiceModel = new Invoice();
        $this->invoiceItemModel = new InvoiceItem();
        $this->companyModel = new Company();
        $this->clientModel = new Client();
        $this->productModel = new Product();
        $this->settingModel = new Setting();
        $this->saleModel = new Sale();
        $this->saleItemModel = new SaleItem();

        $this->taxService = new TaxService();
        $this->auditLogService =
            new AuditLogService();
    }

    public function createDraft(array $data): array
    {
        try {
            $this->db->beginTransaction();

            $companyId = (int) $data['company_id'];
            $clientId = (int) $data['client_id'];
            $userId = (int) $data['user_id'];

            $company = $this->companyModel->findById(
                $companyId
            );

            if ($company === null) {
                throw new Exception(
                    'Company was not found.'
                );
            }

            $client =
                $this->clientModel
                ->findByIdAndCompany(
                    $clientId,
                    $companyId
                );

            if ($client === null) {
                throw new Exception(
                    'Client was not found.'
                );
            }

            $this->validateCompanyBilling($company);
            $this->validateClientBilling($client);

            $taxConfiguration =
                $this->taxService
                ->salesConfiguration(
                    $companyId
                );

            $items = $this->prepareItems(
                $companyId,
                $data['items'],
                $taxConfiguration
            );

            $totals = $this->calculateTotals(
                $items
            );

            $currency = $this->settingModel->get(
                $companyId,
                'currency',
                'EUR'
            );

            $invoiceId = $this->invoiceModel->create([
                'company_id' => $companyId,
                'client_id' => $clientId,
                'sale_id' => null,
                'created_by_user_id' => $userId,

                'document_type' => 'invoice',
                'invoice_number' => null,
                'invoice_date' => $data['invoice_date'],
                'supply_date' => $data['supply_date'],
                'due_date' => $data['due_date'],
                'status' => 'draft',
                'currency' => $currency,

                'vat_registered' =>
                $taxConfiguration['vat_registered'] ? 1 : 0,

                'prices_include_vat' =>
                $taxConfiguration['prices_include_vat'] ? 1 : 0,

                'default_vat_rate' =>
                $taxConfiguration['vat_rate'],

                'supplier_legal_name' =>
                (string) $company['legal_name'],

                'supplier_eik' =>
                (string) $company['eik'],

                'supplier_vat_number' =>
                $this->nullableString(
                    $company['vat_number']
                ),

                'supplier_manager_name' =>
                $this->nullableString(
                    $company['manager_name']
                ),

                'supplier_address' =>
                (string) $company['billing_address'],

                'supplier_city' =>
                (string) $company['billing_city'],

                'supplier_postal_code' =>
                $this->nullableString(
                    $company['billing_postal_code']
                ),

                'supplier_country' =>
                (string) $company['billing_country'],

                'supplier_phone' =>
                $this->nullableString(
                    $company['billing_phone']
                ),

                'supplier_email' =>
                $this->nullableString(
                    $company['billing_email']
                ),

                'supplier_bank_name' =>
                $this->nullableString(
                    $company['bank_name']
                ),

                'supplier_iban' =>
                $this->nullableString(
                    $company['iban']
                ),

                'supplier_bic' =>
                $this->nullableString(
                    $company['bic']
                ),

                'client_type' =>
                (string) $client['client_type'],

                'client_display_name' =>
                (string) $client['name'],

                'client_legal_name' =>
                $this->clientLegalName(
                    $client
                ),

                'client_eik' =>
                $this->nullableString(
                    $client['eik']
                ),

                'client_vat_number' =>
                $this->nullableString(
                    $client['vat_number']
                ),

                'client_address' =>
                (string) $client['billing_address'],

                'client_city' =>
                (string) $client['billing_city'],

                'client_postal_code' =>
                $this->nullableString(
                    $client['billing_postal_code']
                ),

                'client_country' =>
                (string) $client['billing_country'],

                'client_email' =>
                $this->nullableString(
                    $client['billing_email']
                ),

                'subtotal' => $totals['subtotal'],

                'discount_amount' =>
                $totals['discount_amount'],

                'tax_amount' =>
                $totals['tax_amount'],

                'total_amount' =>
                $totals['total_amount'],

                'note' =>
                $this->nullableString(
                    $data['note']
                ),
            ]);

            foreach ($items as $item) {
                $this->invoiceItemModel->create([
                    'invoice_id' => $invoiceId,
                    'company_id' => $companyId,
                    'product_id' =>
                    $item['product_id'],

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
                'invoice',
                $invoiceId,
                'Created invoice draft #' .
                    $invoiceId
            );

            $this->db->commit();

            return [
                'success' => true,
                'invoice_id' => $invoiceId,
                'error' => null,
            ];
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'invoice_id' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function createDraftFromSale(
        int $saleId,
        int $companyId,
        int $userId
    ): array {
        $existingInvoice =
            $this->invoiceModel
            ->findBySaleAndCompany(
                $saleId,
                $companyId
            );

        if ($existingInvoice !== null) {
            return [
                'success' => true,
                'created' => false,
                'invoice_id' =>
                (int) $existingInvoice['id'],
                'error' => null,
            ];
        }

        try {
            $this->db->beginTransaction();

            $sale =
                $this->saleModel
                ->findByIdAndCompany(
                    $saleId,
                    $companyId
                );

            if ($sale === null) {
                throw new Exception(
                    'Sale was not found.'
                );
            }

            if (
                (string) $sale['status'] !==
                'completed'
            ) {
                throw new Exception(
                    'Only completed sales can be invoiced.'
                );
            }

            $clientId = (int) $sale['client_id'];

            if ($clientId <= 0) {
                throw new Exception(
                    'The sale must have a client before an invoice can be generated.'
                );
            }

            $saleItems =
                $this->saleItemModel
                ->allBySale(
                    $saleId,
                    $companyId
                );

            if (empty($saleItems)) {
                throw new Exception(
                    'The sale does not contain any items.'
                );
            }

            $company =
                $this->companyModel
                ->findById($companyId);

            if ($company === null) {
                throw new Exception(
                    'Company was not found.'
                );
            }

            $client =
                $this->clientModel
                ->findByIdAndCompany(
                    $clientId,
                    $companyId
                );

            if ($client === null) {
                throw new Exception(
                    'Client was not found.'
                );
            }

            $this->validateCompanyBilling(
                $company
            );

            $this->validateClientBilling(
                $client
            );

            $currency =
                $this->settingModel->get(
                    $companyId,
                    'currency',
                    'EUR'
                );

            $invoiceDate = date('Y-m-d');

            $supplyDate =
                (string) $sale['sale_date'];

            $invoiceId =
                $this->invoiceModel->create([
                    'company_id' => $companyId,
                    'client_id' => $clientId,
                    'sale_id' => $saleId,

                    'created_by_user_id' =>
                    $userId,

                    'document_type' =>
                    'invoice',

                    'invoice_number' =>
                    null,

                    'invoice_date' =>
                    $invoiceDate,

                    'supply_date' =>
                    $supplyDate,

                    'due_date' =>
                    null,

                    'status' =>
                    'draft',

                    'currency' =>
                    $currency,

                    'vat_registered' =>
                    (int) $sale['vat_registered'],

                    'prices_include_vat' =>
                    (int) $sale['prices_include_vat'],

                    'default_vat_rate' =>
                    (float) $sale['default_vat_rate'],

                    'supplier_legal_name' =>
                    (string) $company['legal_name'],

                    'supplier_eik' =>
                    (string) $company['eik'],

                    'supplier_vat_number' =>
                    $this->nullableString(
                        $company['vat_number']
                    ),

                    'supplier_manager_name' =>
                    $this->nullableString(
                        $company['manager_name']
                    ),

                    'supplier_address' =>
                    (string) $company['billing_address'],

                    'supplier_city' =>
                    (string) $company['billing_city'],

                    'supplier_postal_code' =>
                    $this->nullableString(
                        $company['billing_postal_code']
                    ),

                    'supplier_country' =>
                    (string) $company['billing_country'],

                    'supplier_phone' =>
                    $this->nullableString(
                        $company['billing_phone']
                    ),

                    'supplier_email' =>
                    $this->nullableString(
                        $company['billing_email']
                    ),

                    'supplier_bank_name' =>
                    $this->nullableString(
                        $company['bank_name']
                    ),

                    'supplier_iban' =>
                    $this->nullableString(
                        $company['iban']
                    ),

                    'supplier_bic' =>
                    $this->nullableString(
                        $company['bic']
                    ),

                    'client_type' =>
                    (string) $client['client_type'],

                    'client_display_name' =>
                    (string) $client['name'],

                    'client_legal_name' =>
                    $this->clientLegalName(
                        $client
                    ),

                    'client_eik' =>
                    $this->nullableString(
                        $client['eik']
                    ),

                    'client_vat_number' =>
                    $this->nullableString(
                        $client['vat_number']
                    ),

                    'client_address' =>
                    (string) $client['billing_address'],

                    'client_city' =>
                    (string) $client['billing_city'],

                    'client_postal_code' =>
                    $this->nullableString(
                        $client['billing_postal_code']
                    ),

                    'client_country' =>
                    (string) $client['billing_country'],

                    'client_email' =>
                    $this->nullableString(
                        $client['billing_email']
                    ),

                    'subtotal' =>
                    (float) $sale['subtotal'],

                    'discount_amount' =>
                    (float) $sale['discount_amount'],

                    'tax_amount' =>
                    (float) $sale['tax_amount'],

                    'total_amount' =>
                    (float) $sale['total_amount'],

                    'note' =>
                    $this->nullableString(
                        $sale['note']
                    ),
                ]);

            foreach ($saleItems as $saleItem) {
                $productId = null;

                if (
                    isset($saleItem['product_id']) &&
                    (int) $saleItem['product_id'] > 0
                ) {
                    $productId =
                        (int) $saleItem['product_id'];
                }

                $this->invoiceItemModel->create([
                    'invoice_id' =>
                    $invoiceId,

                    'company_id' =>
                    $companyId,

                    'product_id' =>
                    $productId,

                    'description' =>
                    (string) $saleItem['product_name'],

                    'product_internal_code' =>
                    $this->nullableString(
                        $saleItem['product_internal_code']
                    ),

                    'quantity' =>
                    (float) $saleItem['quantity'],

                    'unit' =>
                    (string) $saleItem['unit'],

                    'unit_price' =>
                    (float) $saleItem['unit_price'],

                    'discount_amount' =>
                    (float) $saleItem['discount_amount'],

                    'vat_rate' =>
                    (float) $saleItem['vat_rate'],

                    'net_amount' =>
                    (float) $saleItem['net_amount'],

                    'tax_amount' =>
                    (float) $saleItem['tax_amount'],

                    'total_amount' =>
                    (float) $saleItem['total_price'],
                ]);
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'invoice',
                $invoiceId,
                'Generated invoice draft from sale ' .
                    (string) $sale['sale_number']
            );

            $this->db->commit();

            return [
                'success' => true,
                'created' => true,
                'invoice_id' => $invoiceId,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $existingInvoice =
                $this->invoiceModel
                ->findBySaleAndCompany(
                    $saleId,
                    $companyId
                );

            if ($existingInvoice !== null) {
                return [
                    'success' => true,
                    'created' => false,
                    'invoice_id' =>
                    (int) $existingInvoice['id'],
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'created' => false,
                'invoice_id' => null,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    private function prepareItems(
        int $companyId,
        array $items,
        array $taxConfiguration
    ): array {
        $preparedItems = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            $discountAmount =
                (float) $item['discount_amount'];

            if ($productId <= 0) {
                continue;
            }

            $product =
                $this->productModel
                ->findByIdAndCompany(
                    $productId,
                    $companyId
                );

            if ($product === null) {
                throw new Exception(
                    'Selected product was not found.'
                );
            }

            $taxResult =
                $this->taxService->calculateLine(
                    $quantity,
                    $unitPrice,
                    $discountAmount,
                    $taxConfiguration
                );

            $preparedItems[] = [
                'product_id' => $productId,

                'description' =>
                (string) $product['name'],

                'product_internal_code' =>
                (string) $product['internal_code'],

                'quantity' => $quantity,
                'unit' => (string) $product['unit'],
                'unit_price' => $unitPrice,

                'discount_amount' =>
                $taxResult['discount_amount'],

                'vat_rate' =>
                $taxResult['vat_rate'],

                'net_amount' =>
                $taxResult['net_amount'],

                'tax_amount' =>
                $taxResult['tax_amount'],

                'total_amount' =>
                $taxResult['total_amount'],
            ];
        }

        if (empty($preparedItems)) {
            throw new Exception(
                'Invoice must have at least one item.'
            );
        }

        return $preparedItems;
    }

    private function calculateTotals(
        array $items
    ): array {
        $subtotal = 0.00;
        $discountAmount = 0.00;
        $taxAmount = 0.00;
        $totalAmount = 0.00;

        foreach ($items as $item) {
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

        return [
            'subtotal' => round($subtotal, 2),

            'discount_amount' =>
            round($discountAmount, 2),

            'tax_amount' =>
            round($taxAmount, 2),

            'total_amount' =>
            round($totalAmount, 2),
        ];
    }

    private function validateCompanyBilling(
        array $company
    ): void {
        $requiredFields = [
            'legal_name',
            'eik',
            'billing_address',
            'billing_city',
            'billing_country',
        ];

        foreach ($requiredFields as $field) {
            if (
                !isset($company[$field]) ||
                trim((string) $company[$field]) === ''
            ) {
                throw new Exception(
                    'Complete the company billing information before creating an invoice.'
                );
            }
        }
    }

    private function validateClientBilling(
        array $client
    ): void {
        $requiredFields = [
            'billing_address',
            'billing_city',
            'billing_country',
        ];

        foreach ($requiredFields as $field) {
            if (
                !isset($client[$field]) ||
                trim((string) $client[$field]) === ''
            ) {
                throw new Exception(
                    'Complete the client billing information before creating an invoice.'
                );
            }
        }

        if (
            isset($client['client_type']) &&
            $client['client_type'] === 'company'
        ) {
            if (
                !isset($client['company_name']) ||
                trim(
                    (string) $client['company_name']
                ) === ''
            ) {
                throw new Exception(
                    'The client legal company name is required.'
                );
            }

            if (
                !isset($client['eik']) ||
                trim((string) $client['eik']) === ''
            ) {
                throw new Exception(
                    'The client EIK is required.'
                );
            }
        }
    }

    private function clientLegalName(
        array $client
    ): string {
        if (
            isset($client['client_type']) &&
            $client['client_type'] === 'company' &&
            isset($client['company_name']) &&
            trim(
                (string) $client['company_name']
            ) !== ''
        ) {
            return trim(
                (string) $client['company_name']
            );
        }

        return trim((string) $client['name']);
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

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return null;
        }

        return $stringValue;
    }
}
