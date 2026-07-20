<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\StockLevel;
use App\Models\WarehouseTransaction;
use Exception;
use PDO;

class PurchaseService
{
    private PDO $db;

    private Purchase $purchaseModel;

    private PurchaseItem $purchaseItemModel;

    private PurchaseReturn $purchaseReturnModel;

    private Product $productModel;

    private StockLevel $stockLevelModel;

    private WarehouseTransaction
        $warehouseTransactionModel;

    private AuditLogService $auditLogService;

    private TaxService $taxService;

    public function __construct()
    {
        $this->db =
            Database::getConnection();

        $this->purchaseModel =
            new Purchase();

        $this->purchaseItemModel =
            new PurchaseItem();

        $this->purchaseReturnModel =
            new PurchaseReturn();

        $this->productModel =
            new Product();

        $this->stockLevelModel =
            new StockLevel();

        $this->warehouseTransactionModel =
            new WarehouseTransaction();

        $this->auditLogService =
            new AuditLogService();

        $this->taxService =
            new TaxService();
    }

    public function createPurchase(
        array $data
    ): array {
        try {
            $this->db->beginTransaction();

            $companyId =
                (int) $data['company_id'];

            $warehouseId =
                (int) $data['warehouse_id'];

            $userId =
                (int) $data['user_id'];

            $taxConfiguration =
                $this->taxService
                ->purchaseConfiguration(
                    $companyId
                );

            $purchaseNumber =
                $this->purchaseModel
                ->generateNextPurchaseNumber(
                    $companyId
                );

            $items =
                $this->prepareItems(
                    $companyId,
                    $data['items'],
                    $taxConfiguration
                );

            $totals =
                $this->calculateTotals(
                    $items
                );

            $purchaseId =
                $this->purchaseModel
                ->create([
                    'company_id' =>
                    $companyId,

                    'supplier_id' =>
                    $data['supplier_id'],

                    'warehouse_id' =>
                    $warehouseId,

                    'user_id' =>
                    $userId,

                    'purchase_number' =>
                    $purchaseNumber,

                    'purchase_date' =>
                    $data['purchase_date'],

                    'status' =>
                    'completed',

                    'vat_registered' =>
                    $taxConfiguration['vat_registered'] ? 1 : 0,

                    'prices_include_vat' =>
                    $taxConfiguration['prices_include_vat'] ? 1 : 0,

                    'default_vat_rate' =>
                    $taxConfiguration['vat_rate'],

                    'subtotal' =>
                    $totals['subtotal'],

                    'discount_amount' =>
                    $totals['discount_amount'],

                    'tax_amount' =>
                    $totals['tax_amount'],

                    'total_amount' =>
                    $totals['total_amount'],

                    'payment_method' =>
                    $data['payment_method'],

                    'note' =>
                    $data['note'],
                ]);

            foreach ($items as $item) {
                $this->purchaseItemModel
                    ->create([
                        'purchase_id' =>
                        $purchaseId,

                        'company_id' =>
                        $companyId,

                        'product_id' =>
                        $item['product_id'],

                        'product_name' =>
                        $item['product_name'],

                        'product_internal_code' =>
                        $item['product_internal_code'],

                        'quantity' =>
                        $item['quantity'],

                        'unit' =>
                        $item['unit'],

                        'unit_cost' =>
                        $item['unit_cost'],

                        'discount_amount' =>
                        $item['discount_amount'],

                        'vat_rate' =>
                        $item['vat_rate'],

                        'net_amount' =>
                        $item['net_amount'],

                        'tax_amount' =>
                        $item['tax_amount'],

                        'total_price' =>
                        $item['total_price'],
                    ]);

                $this->stockLevelModel
                    ->increase(
                        $companyId,
                        (int) $item['product_id'],
                        $warehouseId,
                        (float) $item['quantity']
                    );

                $this->warehouseTransactionModel
                    ->create([
                        'company_id' =>
                        $companyId,

                        'product_id' =>
                        (int) $item['product_id'],

                        'from_warehouse_id' =>
                        null,

                        'to_warehouse_id' =>
                        $warehouseId,

                        'user_id' =>
                        $userId,

                        'type' =>
                        'purchase',

                        'quantity' =>
                        (float) $item['quantity'],

                        'reference_type' =>
                        'purchase',

                        'reference_id' =>
                        $purchaseId,

                        'note' =>
                        'Purchase ' .
                            $purchaseNumber,
                    ]);
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'purchase',
                $purchaseId,
                'Created purchase ' .
                    $purchaseNumber
            );

            $this->db->commit();

            return [
                'success' => true,
                'purchase_id' =>
                $purchaseId,
                'error' => null,
            ];
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'purchase_id' => null,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function cancelPurchase(
        int $purchaseId,
        int $companyId,
        int $userId
    ): array {
        try {
            $this->db->beginTransaction();

            $purchase =
                $this->purchaseModel
                ->findForUpdate(
                    $purchaseId,
                    $companyId
                );

            if ($purchase === null) {
                throw new Exception(
                    'Purchase was not found.'
                );
            }

            if (
                (string) $purchase['status'] ===
                'cancelled'
            ) {
                throw new Exception(
                    'Purchase is already cancelled.'
                );
            }

            if (
                (string) $purchase['status'] !==
                'completed'
            ) {
                throw new Exception(
                    'Only completed purchases can be cancelled.'
                );
            }

            if (
                $this->purchaseReturnModel
                ->hasActiveForPurchase(
                    $purchaseId,
                    $companyId
                )
            ) {
                throw new Exception(
                    'A purchase with draft or completed purchase returns cannot be cancelled.'
                );
            }

            $items =
                $this->purchaseItemModel
                ->allByPurchase(
                    $purchaseId,
                    $companyId
                );

            if (empty($items)) {
                throw new Exception(
                    'Purchase has no items.'
                );
            }

            $warehouseId =
                (int) $purchase['warehouse_id'];

            foreach ($items as $item) {
                $hasEnoughStock =
                    $this->stockLevelModel
                    ->hasEnoughStock(
                        $companyId,
                        (int) $item['product_id'],
                        $warehouseId,
                        (float) $item['quantity']
                    );

                if (!$hasEnoughStock) {
                    throw new Exception(
                        'Not enough stock to cancel purchase for product: ' .
                            (string) $item['product_name']
                    );
                }
            }

            foreach ($items as $item) {
                $decreased =
                    $this->stockLevelModel
                    ->decrease(
                        $companyId,
                        (int) $item['product_id'],
                        $warehouseId,
                        (float) $item['quantity']
                    );

                if (!$decreased) {
                    throw new Exception(
                        'Could not decrease stock for product: ' .
                            (string) $item['product_name']
                    );
                }

                $this->warehouseTransactionModel
                    ->create([
                        'company_id' =>
                        $companyId,

                        'product_id' =>
                        (int) $item['product_id'],

                        'from_warehouse_id' =>
                        $warehouseId,

                        'to_warehouse_id' =>
                        null,

                        'user_id' =>
                        $userId,

                        'type' =>
                        'purchase_cancel',

                        'quantity' =>
                        (float) $item['quantity'],

                        'reference_type' =>
                        'purchase',

                        'reference_id' =>
                        $purchaseId,

                        'note' =>
                        'Cancel purchase ' .
                            (string) $purchase['purchase_number'],
                    ]);
            }

            $cancelled =
                $this->purchaseModel
                ->cancel(
                    $purchaseId,
                    $companyId
                );

            if (!$cancelled) {
                throw new Exception(
                    'Purchase could not be cancelled.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'cancel',
                'purchase',
                $purchaseId,
                'Cancelled purchase ' .
                    (string) $purchase['purchase_number']
            );

            $this->db->commit();

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (Exception $exception) {
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

    private function prepareItems(
        int $companyId,
        array $items,
        array $taxConfiguration
    ): array {
        $preparedItems = [];

        foreach ($items as $item) {
            $productId =
                (int) (
                    $item['product_id'] ??
                    0
                );

            $quantity =
                (float) (
                    $item['quantity'] ??
                    0
                );

            $unitCost =
                (float) (
                    $item['unit_cost'] ??
                    0
                );

            $discountAmount =
                (float) (
                    $item['discount_amount'] ?? 0
                );

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
                $this->taxService
                ->calculateLine(
                    $quantity,
                    $unitCost,
                    $discountAmount,
                    $taxConfiguration
                );

            $preparedItems[] = [
                'product_id' =>
                $productId,

                'product_name' =>
                $product['name'],

                'product_internal_code' =>
                $product['internal_code'],

                'quantity' =>
                $quantity,

                'unit' =>
                $product['unit'],

                'unit_cost' =>
                $unitCost,

                'discount_amount' =>
                $taxResult['discount_amount'],

                'subtotal' =>
                $taxResult['subtotal'],

                'vat_rate' =>
                $taxResult['vat_rate'],

                'net_amount' =>
                $taxResult['net_amount'],

                'tax_amount' =>
                $taxResult['tax_amount'],

                'total_price' =>
                $taxResult['total_amount'],
            ];
        }

        if (empty($preparedItems)) {
            throw new Exception(
                'Purchase must have at least one product.'
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
                (float) $item['subtotal'];

            $discountAmount +=
                (float) $item['discount_amount'];

            $taxAmount +=
                (float) $item['tax_amount'];

            $totalAmount +=
                (float) $item['total_price'];
        }

        return [
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
        ];
    }
}
