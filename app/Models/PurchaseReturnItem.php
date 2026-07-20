<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PurchaseReturnItem extends Model
{
    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            "
            INSERT INTO purchase_return_items
            (
                purchase_return_id,
                company_id,
                purchase_item_id,
                product_id,

                product_name,
                product_internal_code,
                product_unit,

                purchased_quantity,
                return_quantity,

                unit_cost,
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
                :purchase_return_id,
                :company_id,
                :purchase_item_id,
                :product_id,

                :product_name,
                :product_internal_code,
                :product_unit,

                :purchased_quantity,
                :return_quantity,

                :unit_cost,
                :subtotal_amount,
                :discount_amount,
                :net_amount,
                :vat_rate,
                :tax_amount,
                :total_amount,

                :item_note
            )
            "
        );

        $statement->execute([
            'purchase_return_id' =>
                $data['purchase_return_id'],

            'company_id' =>
                $data['company_id'],

            'purchase_item_id' =>
                $data['purchase_item_id'],

            'product_id' =>
                $data['product_id'],

            'product_name' =>
                $data['product_name'],

            'product_internal_code' =>
                $data['product_internal_code'],

            'product_unit' =>
                $data['product_unit'],

            'purchased_quantity' =>
                $data['purchased_quantity'],

            'return_quantity' =>
                $data['return_quantity'],

            'unit_cost' =>
                $data['unit_cost'],

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
        int $purchaseReturnId,
        int $companyId
    ): bool {
        $statement = $this->db->prepare(
            "
            DELETE FROM purchase_return_items
            WHERE purchase_return_id =
                :purchase_return_id
            AND company_id = :company_id
            "
        );

        return $statement->execute([
            'purchase_return_id' =>
                $purchaseReturnId,

            'company_id' =>
                $companyId,
        ]);
    }

    public function allByReturn(
        int $purchaseReturnId,
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM purchase_return_items
            WHERE purchase_return_id =
                :purchase_return_id
            AND company_id = :company_id
            ORDER BY id ASC
            "
        );

        $statement->execute([
            'purchase_return_id' =>
                $purchaseReturnId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function allForUpdate(
        int $purchaseReturnId,
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM purchase_return_items
            WHERE purchase_return_id =
                :purchase_return_id
            AND company_id = :company_id
            ORDER BY product_id ASC
            FOR UPDATE
            "
        );

        $statement->execute([
            'purchase_return_id' =>
                $purchaseReturnId,

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
        $statement = $this->db->prepare(
            "
            UPDATE purchase_return_items
            SET
                stock_quantity_before =
                    :stock_quantity_before,

                stock_quantity_after =
                    :stock_quantity_after,

                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
            "
        );

        return $statement->execute([
            'stock_quantity_before' =>
                $quantityBefore,

            'stock_quantity_after' =>
                $quantityAfter,

            'id' => $id,
            'company_id' => $companyId,
        ]);
    }

    public function returnableByPurchase(
        int $purchaseId,
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT
                purchase_items.*,

                COALESCE(
                    completed_returns.returned_quantity,
                    0
                ) AS returned_quantity,

                GREATEST(
                    purchase_items.quantity -
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

            FROM purchase_items

            LEFT JOIN (
                SELECT
                    purchase_return_items.purchase_item_id,

                    SUM(
                        purchase_return_items.return_quantity
                    ) AS returned_quantity,

                    SUM(
                        purchase_return_items.subtotal_amount
                    ) AS returned_subtotal,

                    SUM(
                        purchase_return_items.discount_amount
                    ) AS returned_discount,

                    SUM(
                        purchase_return_items.net_amount
                    ) AS returned_net,

                    SUM(
                        purchase_return_items.tax_amount
                    ) AS returned_tax,

                    SUM(
                        purchase_return_items.total_amount
                    ) AS returned_total

                FROM purchase_return_items

                INNER JOIN purchase_returns
                    ON purchase_returns.id =
                        purchase_return_items.purchase_return_id

                    AND purchase_returns.company_id =
                        purchase_return_items.company_id

                WHERE purchase_returns.company_id =
                    :returned_company_id

                AND purchase_returns.status =
                    'completed'

                GROUP BY
                    purchase_return_items.purchase_item_id
            ) AS completed_returns
                ON completed_returns.purchase_item_id =
                    purchase_items.id

            WHERE purchase_items.purchase_id =
                :purchase_id

            AND purchase_items.company_id =
                :company_id

            ORDER BY purchase_items.id ASC
            "
        );

        $statement->execute([
            'returned_company_id' =>
                $companyId,

            'purchase_id' =>
                $purchaseId,

            'company_id' =>
                $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function completedSummaryByPurchase(
        int $purchaseId,
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT
                COUNT(
                    DISTINCT purchase_returns.id
                ) AS return_count,

                COALESCE(
                    SUM(
                        purchase_return_items.return_quantity
                    ),
                    0
                ) AS returned_quantity,

                COALESCE(
                    SUM(
                        purchase_return_items.total_amount
                    ),
                    0
                ) AS returned_total

            FROM purchase_returns

            LEFT JOIN purchase_return_items
                ON purchase_return_items.purchase_return_id =
                    purchase_returns.id

                AND purchase_return_items.company_id =
                    purchase_returns.company_id

            WHERE purchase_returns.purchase_id =
                :purchase_id

            AND purchase_returns.company_id =
                :company_id

            AND purchase_returns.status =
                'completed'
            "
        );

        $statement->execute([
            'purchase_id' => $purchaseId,
            'company_id' => $companyId,
        ]);

        $summary = $statement->fetch();

        return [
            'return_count' =>
                (int) ($summary['return_count'] ?? 0),

            'returned_quantity' =>
                (float) (
                    $summary['returned_quantity'] ??
                    0
                ),

            'returned_total' =>
                (float) (
                    $summary['returned_total'] ??
                    0
                ),
        ];
    }
}