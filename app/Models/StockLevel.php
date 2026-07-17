<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class StockLevel extends Model
{
    public function allByCompany(int $companyId, string $search = ''): array
    {
        $sql = "
        SELECT
            stock_levels.id,
            stock_levels.quantity,
            stock_levels.created_at,
            stock_levels.updated_at,

            products.name AS product_name,
            products.internal_code,
            products.barcode,
            products.unit,
            products.min_stock,
            products.is_active AS product_is_active,

            warehouses.name AS warehouse_name,
            warehouses.code AS warehouse_code,
            warehouses.is_active AS warehouse_is_active
        FROM stock_levels
        INNER JOIN products ON stock_levels.product_id = products.id
        INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
        WHERE stock_levels.company_id = ?
    ";

        $params = [$companyId];

        if ($search !== '') {
            $sql .= "
            AND (
                products.name LIKE ?
                OR products.internal_code LIKE ?
                OR products.barcode LIKE ?
                OR warehouses.name LIKE ?
                OR warehouses.code LIKE ?
            )
        ";

            $searchTerm = '%' . $search . '%';

            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= "
        ORDER BY warehouses.name ASC, products.name ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findByProductAndWarehouse(
        int $companyId,
        int $productId,
        int $warehouseId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM stock_levels
            WHERE company_id = ?
            AND product_id = ?
            AND warehouse_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $companyId,
            $productId,
            $warehouseId,
        ]);

        $stockLevel = $stmt->fetch();

        if (!$stockLevel) {
            return null;
        }

        return $stockLevel;
    }

    public function createIfMissing(
        int $companyId,
        int $productId,
        int $warehouseId
    ): void {
        $existingStock = $this->findByProductAndWarehouse(
            $companyId,
            $productId,
            $warehouseId
        );

        if ($existingStock !== null) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO stock_levels
                (company_id, product_id, warehouse_id, quantity)
            VALUES
                (?, ?, ?, 0)
        ");

        $stmt->execute([
            $companyId,
            $productId,
            $warehouseId,
        ]);
    }

    public function increase(
        int $companyId,
        int $productId,
        int $warehouseId,
        float $quantity
    ): bool {
        $this->createIfMissing($companyId, $productId, $warehouseId);

        $stmt = $this->db->prepare("
            UPDATE stock_levels
            SET quantity = quantity + ?
            WHERE company_id = ?
            AND product_id = ?
            AND warehouse_id = ?
        ");

        return $stmt->execute([
            $quantity,
            $companyId,
            $productId,
            $warehouseId,
        ]);
    }

    public function decrease(
        int $companyId,
        int $productId,
        int $warehouseId,
        float $quantity
    ): bool {
        $this->createIfMissing($companyId, $productId, $warehouseId);

        $stmt = $this->db->prepare("
            UPDATE stock_levels
            SET quantity = quantity - ?
            WHERE company_id = ?
            AND product_id = ?
            AND warehouse_id = ?
            AND quantity >= ?
        ");

        $stmt->execute([
            $quantity,
            $companyId,
            $productId,
            $warehouseId,
            $quantity,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function hasEnoughStock(
        int $companyId,
        int $productId,
        int $warehouseId,
        float $quantity
    ): bool {
        $stockLevel = $this->findByProductAndWarehouse(
            $companyId,
            $productId,
            $warehouseId
        );

        if ($stockLevel === null) {
            return false;
        }

        return (float)$stockLevel['quantity'] >= $quantity;
    }

    public function lockForUpdate(
        int $companyId,
        int $productId,
        int $warehouseId
    ): array {
        $insertSql = "
        INSERT IGNORE INTO stock_levels
        (
            company_id,
            product_id,
            warehouse_id,
            quantity
        )
        VALUES
        (
            :company_id,
            :product_id,
            :warehouse_id,
            0
        )
    ";

        $insertStatement =
            $this->db->prepare($insertSql);

        $insertStatement->execute([
            'company_id' => $companyId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);

        $selectSql = "
        SELECT *
        FROM stock_levels
        WHERE company_id = :company_id
        AND product_id = :product_id
        AND warehouse_id = :warehouse_id
        LIMIT 1
        FOR UPDATE
    ";

        $selectStatement =
            $this->db->prepare($selectSql);

        $selectStatement->execute([
            'company_id' => $companyId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);

        $stockLevel =
            $selectStatement->fetch();

        if ($stockLevel === false) {
            throw new \RuntimeException(
                'Stock level could not be locked.'
            );
        }

        return $stockLevel;
    }
}
