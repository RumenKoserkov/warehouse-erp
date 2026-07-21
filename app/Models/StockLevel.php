<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class StockLevel extends Model
{
    public function allByCompany(
        int $companyId,
        string $search = ''
    ): array {
        $sql = "
            SELECT
                stock_levels.id,
                stock_levels.quantity,
                stock_levels.average_unit_cost,
                stock_levels.inventory_value,
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

            INNER JOIN products
                ON stock_levels.product_id =
                    products.id

            INNER JOIN warehouses
                ON stock_levels.warehouse_id =
                    warehouses.id

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

            $searchTerm =
                '%' . $search . '%';

            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= "
            ORDER BY
                warehouses.name ASC,
                products.name ASC
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findByProductAndWarehouse(
        int $companyId,
        int $productId,
        int $warehouseId
    ): ?array {
        $sql = "
            SELECT *
            FROM stock_levels
            WHERE company_id = ?
            AND product_id = ?
            AND warehouse_id = ?
            LIMIT 1
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            $companyId,
            $productId,
            $warehouseId,
        ]);

        $stockLevel =
            $statement->fetch();

        return $stockLevel === false
            ? null
            : $stockLevel;
    }

    public function createIfMissing(
        int $companyId,
        int $productId,
        int $warehouseId
    ): void {
        $existingStock =
            $this->findByProductAndWarehouse(
                $companyId,
                $productId,
                $warehouseId
            );

        if ($existingStock !== null) {
            return;
        }

        $sql = "
            INSERT INTO stock_levels
            (
                company_id,
                product_id,
                warehouse_id,
                quantity,
                average_unit_cost,
                inventory_value
            )
            VALUES
            (
                ?,
                ?,
                ?,
                0,
                0,
                0
            )
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
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
        $this->createIfMissing(
            $companyId,
            $productId,
            $warehouseId
        );

        $sql = "
            UPDATE stock_levels
            SET
                quantity = quantity + ?,
                updated_at = NOW()
            WHERE company_id = ?
            AND product_id = ?
            AND warehouse_id = ?
        ";

        $statement =
            $this->db->prepare($sql);

        return $statement->execute([
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
        $this->createIfMissing(
            $companyId,
            $productId,
            $warehouseId
        );

        $sql = "
            UPDATE stock_levels
            SET
                quantity = quantity - ?,
                updated_at = NOW()
            WHERE company_id = ?
            AND product_id = ?
            AND warehouse_id = ?
            AND quantity >= ?
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            $quantity,
            $companyId,
            $productId,
            $warehouseId,
            $quantity,
        ]);

        return $statement->rowCount() > 0;
    }

    public function hasEnoughStock(
        int $companyId,
        int $productId,
        int $warehouseId,
        float $quantity
    ): bool {
        $stockLevel =
            $this->findByProductAndWarehouse(
                $companyId,
                $productId,
                $warehouseId
            );

        if ($stockLevel === null) {
            return false;
        }

        return
            (float) $stockLevel['quantity']
            >= $quantity;
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
                quantity,
                average_unit_cost,
                inventory_value
            )
            VALUES
            (
                :company_id,
                :product_id,
                :warehouse_id,
                0,
                0,
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
            throw new RuntimeException(
                'Stock level could not be locked.'
            );
        }

        return $stockLevel;
    }

    public function updateCostState(
        int $companyId,
        int $productId,
        int $warehouseId,
        float $quantity,
        float $averageUnitCost,
        float $inventoryValue
    ): bool {
        $sql = "
            UPDATE stock_levels
            SET
                quantity = :quantity,

                average_unit_cost =
                    :average_unit_cost,

                inventory_value =
                    :inventory_value,

                updated_at = NOW()

            WHERE company_id = :company_id
            AND product_id = :product_id
            AND warehouse_id = :warehouse_id
        ";

        $statement =
            $this->db->prepare($sql);

        return $statement->execute([
            'quantity' =>
                round($quantity, 3),

            'average_unit_cost' =>
                round($averageUnitCost, 4),

            'inventory_value' =>
                round($inventoryValue, 4),

            'company_id' =>
                $companyId,

            'product_id' =>
                $productId,

            'warehouse_id' =>
                $warehouseId,
        ]);
    }

    public function costByProductAndWarehouse(
        int $companyId,
        int $productId,
        int $warehouseId
    ): ?array {
        $sql = "
            SELECT
                quantity,
                average_unit_cost,
                inventory_value

            FROM stock_levels

            WHERE company_id = :company_id
            AND product_id = :product_id
            AND warehouse_id = :warehouse_id

            LIMIT 1
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'company_id' =>
                $companyId,

            'product_id' =>
                $productId,

            'warehouse_id' =>
                $warehouseId,
        ]);

        $row = $statement->fetch();

        return $row === false
            ? null
            : $row;
    }
}