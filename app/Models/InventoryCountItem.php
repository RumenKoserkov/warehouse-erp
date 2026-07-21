<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InventoryCountItem extends Model
{
    public function createWarehouseSnapshot(
        int $inventoryCountId,
        int $companyId,
        int $warehouseId
    ): int {
        $sql = "
            INSERT INTO inventory_count_items
            (
                inventory_count_id,
                company_id,
                product_id,

                product_name,
                product_internal_code,
                product_barcode,
                product_unit,

                system_quantity,
                counted_quantity,

                unit_cost,
                variance_value
            )
            SELECT
                :inventory_count_id,
                products.company_id,
                products.id,

                products.name,
                products.internal_code,
                products.barcode,
                products.unit,

                COALESCE(
                    stock_levels.quantity,
                    0
                ),

                NULL,

                CASE
                    WHEN COALESCE(
                        stock_levels.average_unit_cost,
                        0
                    ) > 0
                    THEN stock_levels.average_unit_cost

                    WHEN COALESCE(
                        products.last_purchase_cost,
                        0
                    ) > 0
                    THEN products.last_purchase_cost

                    ELSE COALESCE(
                        products.purchase_price,
                        0
                    )
                END,

                NULL

            FROM products

            LEFT JOIN stock_levels
                ON stock_levels.company_id =
                    products.company_id

                AND stock_levels.product_id =
                    products.id

                AND stock_levels.warehouse_id =
                    :warehouse_id

            WHERE products.company_id =
                :company_id

            AND (
                products.is_active = 1

                OR COALESCE(
                    stock_levels.quantity,
                    0
                ) <> 0
            )
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'inventory_count_id' =>
                $inventoryCountId,

            'warehouse_id' =>
                $warehouseId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->rowCount();
    }

    public function allByCount(
        int $inventoryCountId,
        int $companyId
    ): array {
        $sql = "
            SELECT *
            FROM inventory_count_items

            WHERE inventory_count_id =
                :inventory_count_id

            AND company_id = :company_id

            ORDER BY
                product_name ASC,
                id ASC
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'inventory_count_id' =>
                $inventoryCountId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function allForUpdate(
        int $inventoryCountId,
        int $companyId
    ): array {
        $sql = "
            SELECT *
            FROM inventory_count_items

            WHERE inventory_count_id =
                :inventory_count_id

            AND company_id = :company_id

            ORDER BY id ASC

            FOR UPDATE
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'inventory_count_id' =>
                $inventoryCountId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function updateCountedQuantity(
        int $id,
        int $inventoryCountId,
        int $companyId,
        ?float $countedQuantity
    ): bool {
        $sql = "
            UPDATE inventory_count_items

            SET
                counted_quantity =
                    :counted_quantity,

                updated_at = NOW()

            WHERE id = :id

            AND inventory_count_id =
                :inventory_count_id

            AND company_id = :company_id
        ";

        $statement =
            $this->db->prepare($sql);

        return $statement->execute([
            'counted_quantity' =>
                $countedQuantity,

            'id' =>
                $id,

            'inventory_count_id' =>
                $inventoryCountId,

            'company_id' =>
                $companyId,
        ]);
    }

    public function markCostVariance(
        int $id,
        int $inventoryCountId,
        int $companyId,
        float $unitCost,
        float $costVariance
    ): bool {
        $sql = "
            UPDATE inventory_count_items

            SET
                unit_cost =
                    :unit_cost,

                variance_value =
                    :variance_value,

                updated_at = NOW()

            WHERE id = :id

            AND inventory_count_id =
                :inventory_count_id

            AND company_id = :company_id
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'unit_cost' =>
                round(
                    $unitCost,
                    4
                ),

            'variance_value' =>
                round(
                    $costVariance,
                    4
                ),

            'id' =>
                $id,

            'inventory_count_id' =>
                $inventoryCountId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->rowCount() === 1;
    }
}