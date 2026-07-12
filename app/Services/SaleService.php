<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockLevel;
use App\Models\WarehouseTransaction;
use App\Services\AuditLogService;
use Exception;
use PDO;

class SaleService
{
    private PDO $db;
    private Sale $saleModel;
    private SaleItem $saleItemModel;
    private Product $productModel;
    private StockLevel $stockLevelModel;
    private WarehouseTransaction $warehouseTransactionModel;
    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->saleModel = new Sale();
        $this->saleItemModel = new SaleItem();
        $this->productModel = new Product();
        $this->stockLevelModel = new StockLevel();
        $this->warehouseTransactionModel = new WarehouseTransaction();
        $this->auditLogService = new AuditLogService();
    }

    public function createSale(array $data): array
    {
        try {
            $this->db->beginTransaction();

            $companyId = (int)$data['company_id'];
            $warehouseId = (int)$data['warehouse_id'];
            $userId = (int)$data['user_id'];

            $saleNumber = $this->saleModel->generateNextSaleNumber($companyId);

            $items = $this->prepareItems(
                $companyId,
                $warehouseId,
                $data['items']
            );

            $totals = $this->calculateTotals($items);

            $saleId = $this->saleModel->create([
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'sale_number' => $saleNumber,
                'sale_date' => $data['sale_date'],
                'status' => 'completed',
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => 0,
                'total_amount' => $totals['total_amount'],
                'payment_method' => $data['payment_method'],
                'note' => $data['note'],
            ]);

            foreach ($items as $item) {
                $this->saleItemModel->create([
                    'sale_id' => $saleId,
                    'company_id' => $companyId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_internal_code' => $item['product_internal_code'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'total_price' => $item['total_price'],
                ]);

                $decreased = $this->stockLevelModel->decrease(
                    $companyId,
                    $item['product_id'],
                    $warehouseId,
                    $item['quantity']
                );

                if (!$decreased) {
                    throw new Exception('Could not decrease stock.');
                }

                $this->warehouseTransactionModel->create([
                    'company_id' => $companyId,
                    'product_id' => $item['product_id'],
                    'from_warehouse_id' => $warehouseId,
                    'to_warehouse_id' => null,
                    'user_id' => $userId,
                    'type' => 'sale',
                    'quantity' => $item['quantity'],
                    'reference_type' => 'sale',
                    'reference_id' => $saleId,
                    'note' => 'Sale ' . $saleNumber,
                ]);
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'sale',
                $saleId,
                'Created sale ' . $saleNumber
            );

            $this->db->commit();

            return [
                'success' => true,
                'sale_id' => $saleId,
                'error' => null,
            ];
        } catch (Exception $exception) {
            $this->db->rollBack();

            return [
                'success' => false,
                'sale_id' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function cancelSale(int $saleId, int $companyId, int $userId): array
    {
        try {
            $this->db->beginTransaction();

            $sale = $this->saleModel->findByIdAndCompany($saleId, $companyId);

            if ($sale === null) {
                throw new Exception('Sale was not found.');
            }

            if ($sale['status'] === 'cancelled') {
                throw new Exception('Sale is already cancelled.');
            }

            if ($sale['status'] !== 'completed') {
                throw new Exception('Only completed sales can be cancelled.');
            }

            $items = $this->saleItemModel->allBySale($saleId, $companyId);

            if (empty($items)) {
                throw new Exception('Sale has no items.');
            }

            $warehouseId = (int)$sale['warehouse_id'];

            foreach ($items as $item) {
                $this->stockLevelModel->increase(
                    $companyId,
                    (int)$item['product_id'],
                    $warehouseId,
                    (float)$item['quantity']
                );

                $this->warehouseTransactionModel->create([
                    'company_id' => $companyId,
                    'product_id' => (int)$item['product_id'],
                    'from_warehouse_id' => null,
                    'to_warehouse_id' => $warehouseId,
                    'user_id' => $userId,
                    'type' => 'sale_cancel',
                    'quantity' => (float)$item['quantity'],
                    'reference_type' => 'sale',
                    'reference_id' => $saleId,
                    'note' => 'Cancel sale ' . $sale['sale_number'],
                ]);
            }

            $this->saleModel->cancel($saleId, $companyId);

            $this->auditLogService->log(
                $companyId,
                $userId,
                'cancel',
                'sale',
                $saleId,
                'Cancelled sale ' . $sale['sale_number']
            );

            $this->db->commit();

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (Exception $exception) {
            $this->db->rollBack();

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function prepareItems(int $companyId, int $warehouseId, array $items): array
    {
        $preparedItems = [];

        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $quantity = (float)$item['quantity'];
            $unitPrice = (float)$item['unit_price'];
            $discountAmount = (float)$item['discount_amount'];

            if ($productId <= 0) {
                continue;
            }

            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than zero.');
            }

            if ($unitPrice < 0) {
                throw new Exception('Unit price cannot be negative.');
            }

            if ($discountAmount < 0) {
                throw new Exception('Discount cannot be negative.');
            }

            $product = $this->productModel->findByIdAndCompany($productId, $companyId);

            if ($product === null) {
                throw new Exception('Selected product was not found.');
            }

            $hasEnoughStock = $this->stockLevelModel->hasEnoughStock(
                $companyId,
                $productId,
                $warehouseId,
                $quantity
            );

            if (!$hasEnoughStock) {
                throw new Exception('Not enough stock for product: ' . $product['name']);
            }

            $subtotal = $quantity * $unitPrice;
            $totalPrice = $subtotal - $discountAmount;

            if ($totalPrice < 0) {
                $totalPrice = 0;
            }

            $preparedItems[] = [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'product_internal_code' => $product['internal_code'],
                'quantity' => $quantity,
                'unit' => $product['unit'],
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'total_price' => $totalPrice,
            ];
        }

        if (empty($preparedItems)) {
            throw new Exception('Sale must have at least one product.');
        }

        return $preparedItems;
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $discountAmount = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
            $discountAmount += $item['discount_amount'];
            $totalAmount += $item['total_price'];
        }

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ];
    }
}
