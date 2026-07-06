<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\StockLevel;
use App\Models\WarehouseTransaction;
use Exception;
use PDO;

class StockService
{
    private PDO $db;
    private StockLevel $stockLevelModel;
    private WarehouseTransaction $transactionModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->stockLevelModel = new StockLevel();
        $this->transactionModel = new WarehouseTransaction();
    }

    public function increase(array $data): bool
    {
        try {
            $this->validatePositiveQuantity((float)$data['quantity']);

            $this->db->beginTransaction();

            $this->stockLevelModel->increase(
                (int)$data['company_id'],
                (int)$data['product_id'],
                (int)$data['warehouse_id'],
                (float)$data['quantity']
            );

            $this->transactionModel->create([
                'company_id' => (int)$data['company_id'],
                'product_id' => (int)$data['product_id'],
                'from_warehouse_id' => null,
                'to_warehouse_id' => (int)$data['warehouse_id'],
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'quantity' => (float)$data['quantity'],
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'],
                'note' => $data['note'],
            ]);

            $this->db->commit();

            return true;
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function decrease(array $data): bool
    {
        try {
            $this->validatePositiveQuantity((float)$data['quantity']);

            $this->db->beginTransaction();

            $hasEnoughStock = $this->stockLevelModel->hasEnoughStock(
                (int)$data['company_id'],
                (int)$data['product_id'],
                (int)$data['warehouse_id'],
                (float)$data['quantity']
            );

            if (!$hasEnoughStock) {
                throw new Exception('Not enough stock.');
            }

            $decreased = $this->stockLevelModel->decrease(
                (int)$data['company_id'],
                (int)$data['product_id'],
                (int)$data['warehouse_id'],
                (float)$data['quantity']
            );

            if (!$decreased) {
                throw new Exception('Could not decrease stock.');
            }

            $this->transactionModel->create([
                'company_id' => (int)$data['company_id'],
                'product_id' => (int)$data['product_id'],
                'from_warehouse_id' => (int)$data['warehouse_id'],
                'to_warehouse_id' => null,
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'quantity' => (float)$data['quantity'],
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'],
                'note' => $data['note'],
            ]);

            $this->db->commit();

            return true;
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function transfer(array $data): bool
    {
        try {
            $this->validatePositiveQuantity((float)$data['quantity']);

            if ((int)$data['from_warehouse_id'] === (int)$data['to_warehouse_id']) {
                return false;
            }

            $this->db->beginTransaction();

            $hasEnoughStock = $this->stockLevelModel->hasEnoughStock(
                (int)$data['company_id'],
                (int)$data['product_id'],
                (int)$data['from_warehouse_id'],
                (float)$data['quantity']
            );

            if (!$hasEnoughStock) {
                throw new Exception('Not enough stock for transfer.');
            }

            $decreased = $this->stockLevelModel->decrease(
                (int)$data['company_id'],
                (int)$data['product_id'],
                (int)$data['from_warehouse_id'],
                (float)$data['quantity']
            );

            if (!$decreased) {
                throw new Exception('Could not decrease source warehouse stock.');
            }

            $this->stockLevelModel->increase(
                (int)$data['company_id'],
                (int)$data['product_id'],
                (int)$data['to_warehouse_id'],
                (float)$data['quantity']
            );

            $this->transactionModel->create([
                'company_id' => (int)$data['company_id'],
                'product_id' => (int)$data['product_id'],
                'from_warehouse_id' => (int)$data['from_warehouse_id'],
                'to_warehouse_id' => (int)$data['to_warehouse_id'],
                'user_id' => $data['user_id'],
                'type' => 'transfer',
                'quantity' => (float)$data['quantity'],
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'],
                'note' => $data['note'],
            ]);

            $this->db->commit();

            return true;
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    private function validatePositiveQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than zero.');
        }
    }
}