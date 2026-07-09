<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockLevel;
use App\Models\WarehouseTransaction;
use Exception;
use PDO;

class PurchaseService
{
    private PDO $db;
    private Purchase $purchaseModel;
    private PurchaseItem $purchaseItemModel;
    private Product $productModel;
    private StockLevel $stockLevelModel;
    private WarehouseTransaction $warehouseTransactionModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->purchaseModel = new Purchase();
        $this->purchaseItemModel = new PurchaseItem();
        $this->productModel = new Product();
        $this->stockLevelModel = new StockLevel();
        $this->warehouseTransactionModel = new WarehouseTransaction();
    }

    public function createPurchase(array $data): array
    {
        try {
            $this->db->beginTransaction();

            $companyId = (int)$data['company_id'];
            $warehouseId = (int)$data['warehouse_id'];
            $userId = (int)$data['user_id'];

            $purchaseNumber = $this->purchaseModel->generateNextPurchaseNumber($companyId);

            $items = $this->prepareItems(
                $companyId,
                $data['items']
            );

            $totals = $this->calculateTotals($items);

            $purchaseId = $this->purchaseModel->create([
                'company_id' => $companyId,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'purchase_number' => $purchaseNumber,
                'purchase_date' => $data['purchase_date'],
                'status' => 'completed',
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => 0,
                'total_amount' => $totals['total_amount'],
                'payment_method' => $data['payment_method'],
                'note' => $data['note'],
            ]);

            foreach ($items as $item) {
                $this->purchaseItemModel->create([
                    'purchase_id' => $purchaseId,
                    'company_id' => $companyId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_internal_code' => $item['product_internal_code'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_cost' => $item['unit_cost'],
                    'discount_amount' => $item['discount_amount'],
                    'total_price' => $item['total_price'],
                ]);

                $this->stockLevelModel->increase(
                    $companyId,
                    $item['product_id'],
                    $warehouseId,
                    $item['quantity']
                );

                $this->warehouseTransactionModel->create([
                    'company_id' => $companyId,
                    'product_id' => $item['product_id'],
                    'from_warehouse_id' => null,
                    'to_warehouse_id' => $warehouseId,
                    'user_id' => $userId,
                    'type' => 'purchase',
                    'quantity' => $item['quantity'],
                    'reference_type' => 'purchase',
                    'reference_id' => $purchaseId,
                    'note' => 'Purchase ' . $purchaseNumber,
                ]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'purchase_id' => $purchaseId,
                'error' => null,
            ];
        } catch (Exception $exception) {
            $this->db->rollBack();

            return [
                'success' => false,
                'purchase_id' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function cancelPurchase(int $purchaseId, int $companyId, int $userId): array
    {
        try {
            $this->db->beginTransaction();

            $purchase = $this->purchaseModel->findByIdAndCompany($purchaseId, $companyId);

            if ($purchase === null) {
                throw new Exception('Purchase was not found.');
            }

            if ($purchase['status'] === 'cancelled') {
                throw new Exception('Purchase is already cancelled.');
            }

            if ($purchase['status'] !== 'completed') {
                throw new Exception('Only completed purchases can be cancelled.');
            }

            $items = $this->purchaseItemModel->allByPurchase($purchaseId, $companyId);

            if (empty($items)) {
                throw new Exception('Purchase has no items.');
            }

            $warehouseId = (int)$purchase['warehouse_id'];

            foreach ($items as $item) {
                $hasEnoughStock = $this->stockLevelModel->hasEnoughStock(
                    $companyId,
                    (int)$item['product_id'],
                    $warehouseId,
                    (float)$item['quantity']
                );

                if (!$hasEnoughStock) {
                    throw new Exception('Not enough stock to cancel purchase for product: ' . $item['product_name']);
                }
            }

            foreach ($items as $item) {
                $decreased = $this->stockLevelModel->decrease(
                    $companyId,
                    (int)$item['product_id'],
                    $warehouseId,
                    (float)$item['quantity']
                );

                if (!$decreased) {
                    throw new Exception('Could not decrease stock for product: ' . $item['product_name']);
                }

                $this->warehouseTransactionModel->create([
                    'company_id' => $companyId,
                    'product_id' => (int)$item['product_id'],
                    'from_warehouse_id' => $warehouseId,
                    'to_warehouse_id' => null,
                    'user_id' => $userId,
                    'type' => 'purchase_cancel',
                    'quantity' => (float)$item['quantity'],
                    'reference_type' => 'purchase',
                    'reference_id' => $purchaseId,
                    'note' => 'Cancel purchase ' . $purchase['purchase_number'],
                ]);
            }

            $this->purchaseModel->cancel($purchaseId, $companyId);

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

    private function prepareItems(int $companyId, array $items): array
    {
        $preparedItems = [];

        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $quantity = (float)$item['quantity'];
            $unitCost = (float)$item['unit_cost'];
            $discountAmount = (float)$item['discount_amount'];

            if ($productId <= 0) {
                continue;
            }

            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than zero.');
            }

            if ($unitCost < 0) {
                throw new Exception('Unit cost cannot be negative.');
            }

            if ($discountAmount < 0) {
                throw new Exception('Discount cannot be negative.');
            }

            $product = $this->productModel->findByIdAndCompany($productId, $companyId);

            if ($product === null) {
                throw new Exception('Selected product was not found.');
            }

            $subtotal = $quantity * $unitCost;
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
                'unit_cost' => $unitCost,
                'discount_amount' => $discountAmount,
                'total_price' => $totalPrice,
            ];
        }

        if (empty($preparedItems)) {
            throw new Exception('Purchase must have at least one product.');
        }

        return $preparedItems;
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $discountAmount = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_cost'];
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
