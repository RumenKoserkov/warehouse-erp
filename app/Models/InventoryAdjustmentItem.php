<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InventoryAdjustmentItem extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO inventory_adjustment_items
            (
                inventory_adjustment_id,
                company_id,
                product_id,

                product_name,
                product_internal_code,
                product_barcode,
                product_unit,

                direction,
                quantity,

                unit_cost,
                total_cost,

                stock_quantity_at_add,
                item_note
            )
            VALUES
            (
                :inventory_adjustment_id,
                :company_id,
                :product_id,

                :product_name,
                :product_internal_code,
                :product_barcode,
                :product_unit,

                :direction,
                :quantity,

                :unit_cost,
                :total_cost,

                :stock_quantity_at_add,
                :item_note
            )
        ";

        $statement =
            $this->db->prepare($sql);

        $unitCost = null;

        if (
            array_key_exists(
                'unit_cost',
                $data
            ) &&
            $data['unit_cost'] !== null
        ) {
            $unitCost = round(
                (float) $data['unit_cost'],
                4
            );
        }

        $totalCost = null;

        if (
            array_key_exists(
                'total_cost',
                $data
            ) &&
            $data['total_cost'] !== null
        ) {
            $totalCost = round(
                (float) $data['total_cost'],
                4
            );
        }

        $statement->execute([
            'inventory_adjustment_id' =>
                $data[
                    'inventory_adjustment_id'
                ],

            'company_id' =>
                $data['company_id'],

            'product_id' =>
                $data['product_id'],

            'product_name' =>
                $data['product_name'],

            'product_internal_code' =>
                $data[
                    'product_internal_code'
                ],

            'product_barcode' =>
                $data['product_barcode'],

            'product_unit' =>
                $data['product_unit'],

            'direction' =>
                $data['direction'],

            'quantity' =>
                $data['quantity'],

            'unit_cost' =>
                $unitCost,

            'total_cost' =>
                $totalCost,

            'stock_quantity_at_add' =>
                $data[
                    'stock_quantity_at_add'
                ],

            'item_note' =>
                $data['item_note'],
        ]);

        return (int)
            $this->db->lastInsertId();
    }

    public function allByAdjustment(
        int $adjustmentId,
        int $companyId
    ): array {
        $sql = "
            SELECT *
            FROM inventory_adjustment_items

            WHERE inventory_adjustment_id =
                :inventory_adjustment_id

            AND company_id = :company_id

            ORDER BY
                product_name ASC,
                id ASC
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'inventory_adjustment_id' =>
                $adjustmentId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function allForUpdate(
        int $adjustmentId,
        int $companyId
    ): array {
        $sql = "
            SELECT *
            FROM inventory_adjustment_items

            WHERE inventory_adjustment_id =
                :inventory_adjustment_id

            AND company_id = :company_id

            ORDER BY product_id ASC

            FOR UPDATE
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'inventory_adjustment_id' =>
                $adjustmentId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function findByIdAndAdjustment(
        int $id,
        int $adjustmentId,
        int $companyId
    ): ?array {
        $sql = "
            SELECT *
            FROM inventory_adjustment_items

            WHERE id = :id

            AND inventory_adjustment_id =
                :inventory_adjustment_id

            AND company_id = :company_id

            LIMIT 1
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'id' =>
                $id,

            'inventory_adjustment_id' =>
                $adjustmentId,

            'company_id' =>
                $companyId,
        ]);

        $item = $statement->fetch();

        if ($item === false) {
            return null;
        }

        return $item;
    }

    public function productExists(
        int $adjustmentId,
        int $companyId,
        int $productId
    ): bool {
        $sql = "
            SELECT id
            FROM inventory_adjustment_items

            WHERE inventory_adjustment_id =
                :inventory_adjustment_id

            AND company_id = :company_id
            AND product_id = :product_id

            LIMIT 1
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'inventory_adjustment_id' =>
                $adjustmentId,

            'company_id' =>
                $companyId,

            'product_id' =>
                $productId,
        ]);

        return $statement->fetch() !== false;
    }

    public function deleteDraftItem(
        int $id,
        int $adjustmentId,
        int $companyId
    ): bool {
        $sql = "
            DELETE FROM inventory_adjustment_items

            WHERE id = :id

            AND inventory_adjustment_id =
                :inventory_adjustment_id

            AND company_id = :company_id
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'id' =>
                $id,

            'inventory_adjustment_id' =>
                $adjustmentId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function markApplied(
        int $id,
        int $companyId,
        float $quantityBefore,
        float $quantityAfter
    ): bool {
        $sql = "
            UPDATE inventory_adjustment_items

            SET
                quantity_before =
                    :quantity_before,

                quantity_after =
                    :quantity_after,

                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'quantity_before' =>
                round(
                    $quantityBefore,
                    3
                ),

            'quantity_after' =>
                round(
                    $quantityAfter,
                    3
                ),

            'id' =>
                $id,

            'company_id' =>
                $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function markCostApplied(
        int $id,
        int $companyId,
        float $quantityBefore,
        float $quantityAfter,
        float $unitCost,
        float $totalCost
    ): bool {
        $sql = "
            UPDATE inventory_adjustment_items

            SET
                quantity_before =
                    :quantity_before,

                quantity_after =
                    :quantity_after,

                unit_cost =
                    :unit_cost,

                total_cost =
                    :total_cost,

                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'quantity_before' =>
                round(
                    $quantityBefore,
                    3
                ),

            'quantity_after' =>
                round(
                    $quantityAfter,
                    3
                ),

            'unit_cost' =>
                round(
                    $unitCost,
                    4
                ),

            'total_cost' =>
                round(
                    $totalCost,
                    4
                ),

            'id' =>
                $id,

            'company_id' =>
                $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function findProductByCompany(
        int $productId,
        int $companyId
    ): ?array {
        $sql = "
            SELECT
                id,
                company_id,
                name,
                internal_code,
                barcode,
                unit,
                purchase_price,
                last_purchase_cost,
                is_active

            FROM products

            WHERE id = :id
            AND company_id = :company_id

            LIMIT 1
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'id' =>
                $productId,

            'company_id' =>
                $companyId,
        ]);

        $product = $statement->fetch();

        if ($product === false) {
            return null;
        }

        return $product;
    }

    public function productsForAdjustment(
        int $companyId,
        int $warehouseId,
        string $search = ''
    ): array {
        $sql = "
            SELECT
                products.id,
                products.name,
                products.internal_code,
                products.barcode,
                products.unit,
                products.purchase_price,
                products.last_purchase_cost,
                products.is_active,

                COALESCE(
                    stock_levels.quantity,
                    0
                ) AS current_quantity,

                COALESCE(
                    stock_levels.average_unit_cost,
                    0
                ) AS average_unit_cost,

                COALESCE(
                    stock_levels.inventory_value,
                    0
                ) AS inventory_value

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

        $parameters = [
            'warehouse_id' =>
                $warehouseId,

            'company_id' =>
                $companyId,
        ];

        if ($search !== '') {
            $searchTerm =
                '%' . $search . '%';

            $sql .= "
                AND (
                    products.name
                        LIKE :search_name

                    OR products.internal_code
                        LIKE :search_code

                    OR products.barcode
                        LIKE :search_barcode
                )
            ";

            $parameters['search_name'] =
                $searchTerm;

            $parameters['search_code'] =
                $searchTerm;

            $parameters['search_barcode'] =
                $searchTerm;
        }

        $sql .= "
            ORDER BY products.name ASC
            LIMIT 100
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute(
            $parameters
        );

        return $statement->fetchAll();
    }
}