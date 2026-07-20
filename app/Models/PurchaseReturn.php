<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PurchaseReturn extends Model
{
    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            "
            INSERT INTO purchase_returns
            (
                company_id,
                purchase_id,
                warehouse_id,
                return_number,
                return_date,
                reason_type,
                reason_description,
                status,
                notes,
                created_by_user_id
            )
            VALUES
            (
                :company_id,
                :purchase_id,
                :warehouse_id,
                NULL,
                :return_date,
                :reason_type,
                :reason_description,
                'draft',
                :notes,
                :created_by_user_id
            )
            "
        );

        $statement->execute([
            'company_id' =>
                $data['company_id'],

            'purchase_id' =>
                $data['purchase_id'],

            'warehouse_id' =>
                $data['warehouse_id'],

            'return_date' =>
                $data['return_date'],

            'reason_type' =>
                $data['reason_type'],

            'reason_description' =>
                $data['reason_description'],

            'notes' =>
                $data['notes'],

            'created_by_user_id' =>
                $data['created_by_user_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function assignNumber(
        int $id,
        int $companyId,
        string $returnNumber
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE purchase_returns
            SET
                return_number = :return_number,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND return_number IS NULL
            "
        );

        $statement->execute([
            'return_number' => $returnNumber,
            'id' => $id,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function updateTotals(
        int $id,
        int $companyId,
        array $totals
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE purchase_returns
            SET
                subtotal_amount =
                    :subtotal_amount,

                discount_amount =
                    :discount_amount,

                net_amount =
                    :net_amount,

                tax_amount =
                    :tax_amount,

                total_amount =
                    :total_amount,

                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
            AND status = 'draft'
            "
        );

        return $statement->execute([
            'subtotal_amount' =>
                $totals['subtotal_amount'],

            'discount_amount' =>
                $totals['discount_amount'],

            'net_amount' =>
                $totals['net_amount'],

            'tax_amount' =>
                $totals['tax_amount'],

            'total_amount' =>
                $totals['total_amount'],

            'id' => $id,
            'company_id' => $companyId,
        ]);
    }

    public function allByCompany(
        int $companyId,
        array $filters = []
    ): array {
        $sql = "
            SELECT
                purchase_returns.*,

                purchases.purchase_number,
                purchases.purchase_date,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                suppliers.name
                    AS supplier_name,

                suppliers.company_name
                    AS supplier_company_name,

                creator.name
                    AS created_by_user_name,

                completer.name
                    AS completed_by_user_name,

                COALESCE(
                    item_totals.item_count,
                    0
                ) AS item_count,

                COALESCE(
                    item_totals.returned_quantity,
                    0
                ) AS returned_quantity

            FROM purchase_returns

            INNER JOIN purchases
                ON purchases.id =
                    purchase_returns.purchase_id

                AND purchases.company_id =
                    purchase_returns.company_id

            INNER JOIN warehouses
                ON warehouses.id =
                    purchase_returns.warehouse_id

                AND warehouses.company_id =
                    purchase_returns.company_id

            LEFT JOIN suppliers
                ON suppliers.id =
                    purchases.supplier_id

                AND suppliers.company_id =
                    purchases.company_id

            INNER JOIN users AS creator
                ON creator.id =
                    purchase_returns.created_by_user_id

            LEFT JOIN users AS completer
                ON completer.id =
                    purchase_returns.completed_by_user_id

            LEFT JOIN (
                SELECT
                    purchase_return_id,

                    COUNT(*) AS item_count,

                    SUM(return_quantity)
                        AS returned_quantity

                FROM purchase_return_items

                GROUP BY purchase_return_id
            ) AS item_totals
                ON item_totals.purchase_return_id =
                    purchase_returns.id

            WHERE purchase_returns.company_id =
                :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        if (
            isset($filters['status']) &&
            is_string($filters['status']) &&
            $filters['status'] !== ''
        ) {
            $sql .= "
                AND purchase_returns.status =
                    :status
            ";

            $parameters['status'] =
                $filters['status'];
        }

        if (
            isset($filters['warehouse_id']) &&
            is_int($filters['warehouse_id']) &&
            $filters['warehouse_id'] > 0
        ) {
            $sql .= "
                AND purchase_returns.warehouse_id =
                    :warehouse_id
            ";

            $parameters['warehouse_id'] =
                $filters['warehouse_id'];
        }

        if (
            isset($filters['search']) &&
            is_string($filters['search']) &&
            $filters['search'] !== ''
        ) {
            $search =
                '%' . $filters['search'] . '%';

            $sql .= "
                AND (
                    purchase_returns.return_number
                        LIKE :search_return

                    OR purchases.purchase_number
                        LIKE :search_purchase

                    OR purchase_returns.reason_description
                        LIKE :search_reason

                    OR suppliers.name
                        LIKE :search_supplier

                    OR suppliers.company_name
                        LIKE :search_supplier_company

                    OR warehouses.name
                        LIKE :search_warehouse
                )
            ";

            $parameters['search_return'] =
                $search;

            $parameters['search_purchase'] =
                $search;

            $parameters['search_reason'] =
                $search;

            $parameters['search_supplier'] =
                $search;

            $parameters[
                'search_supplier_company'
            ] = $search;

            $parameters['search_warehouse'] =
                $search;
        }

        $sql .= "
            ORDER BY
                purchase_returns.return_date DESC,
                purchase_returns.id DESC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function allByPurchase(
        int $purchaseId,
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT
                purchase_returns.*,

                creator.name
                    AS created_by_user_name

            FROM purchase_returns

            INNER JOIN users AS creator
                ON creator.id =
                    purchase_returns.created_by_user_id

            WHERE purchase_returns.purchase_id =
                :purchase_id

            AND purchase_returns.company_id =
                :company_id

            ORDER BY purchase_returns.id DESC
            "
        );

        $statement->execute([
            'purchase_id' => $purchaseId,
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function findByIdAndCompany(
        int $id,
        int $companyId
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT
                purchase_returns.*,

                purchases.purchase_number,
                purchases.purchase_date,
                purchases.status AS purchase_status,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                suppliers.name
                    AS supplier_name,

                suppliers.company_name
                    AS supplier_company_name,

                suppliers.phone
                    AS supplier_phone,

                suppliers.email
                    AS supplier_email,

                creator.name
                    AS created_by_user_name,

                completer.name
                    AS completed_by_user_name,

                canceller.name
                    AS cancelled_by_user_name

            FROM purchase_returns

            INNER JOIN purchases
                ON purchases.id =
                    purchase_returns.purchase_id

                AND purchases.company_id =
                    purchase_returns.company_id

            INNER JOIN warehouses
                ON warehouses.id =
                    purchase_returns.warehouse_id

                AND warehouses.company_id =
                    purchase_returns.company_id

            LEFT JOIN suppliers
                ON suppliers.id =
                    purchases.supplier_id

                AND suppliers.company_id =
                    purchases.company_id

            INNER JOIN users AS creator
                ON creator.id =
                    purchase_returns.created_by_user_id

            LEFT JOIN users AS completer
                ON completer.id =
                    purchase_returns.completed_by_user_id

            LEFT JOIN users AS canceller
                ON canceller.id =
                    purchase_returns.cancelled_by_user_id

            WHERE purchase_returns.id = :id
            AND purchase_returns.company_id =
                :company_id

            LIMIT 1
            "
        );

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $purchaseReturn = $statement->fetch();

        if ($purchaseReturn === false) {
            return null;
        }

        return $purchaseReturn;
    }

    public function findForUpdate(
        int $id,
        int $companyId
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM purchase_returns
            WHERE id = :id
            AND company_id = :company_id
            LIMIT 1
            FOR UPDATE
            "
        );

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $purchaseReturn = $statement->fetch();

        if ($purchaseReturn === false) {
            return null;
        }

        return $purchaseReturn;
    }

    public function hasDraftForPurchase(
        int $purchaseId,
        int $companyId
    ): bool {
        $statement = $this->db->prepare(
            "
            SELECT id
            FROM purchase_returns
            WHERE purchase_id = :purchase_id
            AND company_id = :company_id
            AND status = 'draft'
            LIMIT 1
            "
        );

        $statement->execute([
            'purchase_id' => $purchaseId,
            'company_id' => $companyId,
        ]);

        return $statement->fetch() !== false;
    }

    public function hasActiveForPurchase(
        int $purchaseId,
        int $companyId
    ): bool {
        $statement = $this->db->prepare(
            "
            SELECT id
            FROM purchase_returns
            WHERE purchase_id = :purchase_id
            AND company_id = :company_id
            AND status IN (
                'draft',
                'completed'
            )
            LIMIT 1
            "
        );

        $statement->execute([
            'purchase_id' => $purchaseId,
            'company_id' => $companyId,
        ]);

        return $statement->fetch() !== false;
    }

    public function markCompleted(
        int $id,
        int $companyId,
        int $userId
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE purchase_returns
            SET
                status = 'completed',

                completed_by_user_id =
                    :completed_by_user_id,

                completed_at = NOW(),
                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
            AND status = 'draft'
            "
        );

        $statement->execute([
            'completed_by_user_id' => $userId,
            'id' => $id,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function markCancelled(
        int $id,
        int $companyId,
        int $userId,
        string $reason
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE purchase_returns
            SET
                status = 'cancelled',

                cancelled_by_user_id =
                    :cancelled_by_user_id,

                cancelled_at = NOW(),

                cancellation_reason =
                    :cancellation_reason,

                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
            AND status = 'draft'
            "
        );

        $statement->execute([
            'cancelled_by_user_id' => $userId,
            'cancellation_reason' => $reason,
            'id' => $id,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }
}