<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SalesReturnItem extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO sales_return_items
            (
                sales_return_id,
                company_id,
                sale_item_id,
                product_id,

                product_name,
                product_internal_code,
                product_unit,

                sold_quantity,
                return_quantity,
                restock_quantity,

                unit_price,
                subtotal_amount,
                discount_amount,
                net_amount,
                vat_rate,
                tax_amount,
                total_amount,

                item_note
            )
            VALUES
            (
                :sales_return_id,
                :company_id,
                :sale_item_id,
                :product_id,

                :product_name,
                :product_internal_code,
                :product_unit,

                :sold_quantity,
                :return_quantity,
                :restock_quantity,

                :unit_price,
                :subtotal_amount,
                :discount_amount,
                :net_amount,
                :vat_rate,
                :tax_amount,
                :total_amount,

                :item_note
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'sales_return_id' =>
                $data['sales_return_id'],

            'company_id' =>
                $data['company_id'],

            'sale_item_id' =>
                $data['sale_item_id'],

            'product_id' =>
                $data['product_id'],

            'product_name' =>
                $data['product_name'],

            'product_internal_code' =>
                $data['product_internal_code'],

            'product_unit' =>
                $data['product_unit'],

            'sold_quantity' =>
                $data['sold_quantity'],

            'return_quantity' =>
                $data['return_quantity'],

            'restock_quantity' =>
                $data['restock_quantity'],

            'unit_price' =>
                $data['unit_price'],

            'subtotal_amount' =>
                $data['subtotal_amount'],

            'discount_amount' =>
                $data['discount_amount'],

            'net_amount' =>
                $data['net_amount'],

            'vat_rate' =>
                $data['vat_rate'],

            'tax_amount' =>
                $data['tax_amount'],

            'total_amount' =>
                $data['total_amount'],

            'item_note' =>
                $data['item_note'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function deleteByReturn(
        int $salesReturnId,
        int $companyId
    ): bool {
        $sql = "
            DELETE FROM sales_return_items
            WHERE sales_return_id =
                :sales_return_id
            AND company_id = :company_id
        ";

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'sales_return_id' =>
                $salesReturnId,

            'company_id' =>
                $companyId,
        ]);
    }

    public function allByReturn(
        int $salesReturnId,
        int $companyId
    ): array {
        $sql = "
            SELECT *
            FROM sales_return_items

            WHERE sales_return_id =
                :sales_return_id

            AND company_id = :company_id

            ORDER BY id ASC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'sales_return_id' =>
                $salesReturnId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function allForUpdate(
        int $salesReturnId,
        int $companyId
    ): array {
        $sql = "
            SELECT *
            FROM sales_return_items

            WHERE sales_return_id =
                :sales_return_id

            AND company_id = :company_id

            ORDER BY product_id ASC

            FOR UPDATE
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'sales_return_id' =>
                $salesReturnId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function markApplied(
        int $id,
        int $companyId,
        float $quantityBefore,
        float $quantityAfter
    ): bool {
        $sql = "
            UPDATE sales_return_items
            SET
                stock_quantity_before =
                    :stock_quantity_before,

                stock_quantity_after =
                    :stock_quantity_after,

                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
        ";

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'stock_quantity_before' =>
                $quantityBefore,

            'stock_quantity_after' =>
                $quantityAfter,

            'id' => $id,
            'company_id' => $companyId,
        ]);
    }

    public function returnableBySale(
        int $saleId,
        int $companyId
    ): array {
        $sql = "
            SELECT
                sale_items.*,

                COALESCE(
                    completed_returns.returned_quantity,
                    0
                ) AS returned_quantity,

                GREATEST(
                    sale_items.quantity -
                    COALESCE(
                        completed_returns.returned_quantity,
                        0
                    ),
                    0
                ) AS remaining_quantity,

                COALESCE(
                    completed_returns.returned_subtotal,
                    0
                ) AS returned_subtotal,

                COALESCE(
                    completed_returns.returned_discount,
                    0
                ) AS returned_discount,

                COALESCE(
                    completed_returns.returned_net,
                    0
                ) AS returned_net,

                COALESCE(
                    completed_returns.returned_tax,
                    0
                ) AS returned_tax,

                COALESCE(
                    completed_returns.returned_total,
                    0
                ) AS returned_total

            FROM sale_items

            LEFT JOIN (
                SELECT
                    sales_return_items.sale_item_id,

                    SUM(
                        sales_return_items.return_quantity
                    ) AS returned_quantity,

                    SUM(
                        sales_return_items.subtotal_amount
                    ) AS returned_subtotal,

                    SUM(
                        sales_return_items.discount_amount
                    ) AS returned_discount,

                    SUM(
                        sales_return_items.net_amount
                    ) AS returned_net,

                    SUM(
                        sales_return_items.tax_amount
                    ) AS returned_tax,

                    SUM(
                        sales_return_items.total_amount
                    ) AS returned_total

                FROM sales_return_items

                INNER JOIN sales_returns
                    ON sales_returns.id =
                        sales_return_items.sales_return_id

                    AND sales_returns.company_id =
                        sales_return_items.company_id

                WHERE sales_returns.company_id =
                    :returned_company_id

                AND sales_returns.status =
                    'completed'

                GROUP BY
                    sales_return_items.sale_item_id
            ) AS completed_returns
                ON completed_returns.sale_item_id =
                    sale_items.id

            WHERE sale_items.sale_id =
                :sale_id

            AND sale_items.company_id =
                :company_id

            ORDER BY sale_items.id ASC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'returned_company_id' =>
                $companyId,

            'sale_id' =>
                $saleId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function completedSummaryBySale(
        int $saleId,
        int $companyId
    ): array {
        $sql = "
            SELECT
                COUNT(
                    DISTINCT sales_returns.id
                ) AS return_count,

                COALESCE(
                    SUM(
                        sales_return_items.return_quantity
                    ),
                    0
                ) AS returned_quantity,

                COALESCE(
                    SUM(
                        sales_return_items.restock_quantity
                    ),
                    0
                ) AS restocked_quantity,

                COALESCE(
                    SUM(
                        sales_return_items.total_amount
                    ),
                    0
                ) AS returned_total

            FROM sales_returns

            LEFT JOIN sales_return_items
                ON sales_return_items.sales_return_id =
                    sales_returns.id

                AND sales_return_items.company_id =
                    sales_returns.company_id

            WHERE sales_returns.sale_id =
                :sale_id

            AND sales_returns.company_id =
                :company_id

            AND sales_returns.status =
                'completed'
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'sale_id' => $saleId,
            'company_id' => $companyId,
        ]);

        $summary = $statement->fetch();

        if ($summary === false) {
            return [
                'return_count' => 0,
                'returned_quantity' => 0.0,
                'restocked_quantity' => 0.0,
                'returned_total' => 0.0,
            ];
        }

        return [
            'return_count' =>
                (int) $summary['return_count'],

            'returned_quantity' =>
                (float) $summary[
                    'returned_quantity'
                ],

            'restocked_quantity' =>
                (float) $summary[
                    'restocked_quantity'
                ],

            'returned_total' =>
                (float) $summary[
                    'returned_total'
                ],
        ];
    }
}