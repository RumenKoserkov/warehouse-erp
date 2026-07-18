<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InventoryAdjustment extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO inventory_adjustments
            (
                company_id,
                warehouse_id,
                adjustment_number,
                adjustment_date,
                reason_type,
                reason_description,
                status,
                notes,
                created_by_user_id
            )
            VALUES
            (
                :company_id,
                :warehouse_id,
                NULL,
                :adjustment_date,
                :reason_type,
                :reason_description,
                'draft',
                :notes,
                :created_by_user_id
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' =>
                $data['company_id'],

            'warehouse_id' =>
                $data['warehouse_id'],

            'adjustment_date' =>
                $data['adjustment_date'],

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
        string $adjustmentNumber
    ): bool {
        $sql = "
            UPDATE inventory_adjustments
            SET
                adjustment_number =
                    :adjustment_number,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND adjustment_number IS NULL
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'adjustment_number' =>
                $adjustmentNumber,

            'id' => $id,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function allByCompany(
        int $companyId,
        array $filters = []
    ): array {
        $sql = "
            SELECT
                inventory_adjustments.*,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                creator.name
                    AS created_by_user_name,

                completer.name
                    AS completed_by_user_name,

                COALESCE(
                    item_statistics.item_count,
                    0
                ) AS item_count,

                COALESCE(
                    item_statistics.increase_items,
                    0
                ) AS increase_items,

                COALESCE(
                    item_statistics.decrease_items,
                    0
                ) AS decrease_items,

                COALESCE(
                    item_statistics.total_increase,
                    0
                ) AS total_increase,

                COALESCE(
                    item_statistics.total_decrease,
                    0
                ) AS total_decrease

            FROM inventory_adjustments

            INNER JOIN warehouses
                ON warehouses.id =
                    inventory_adjustments.warehouse_id

                AND warehouses.company_id =
                    inventory_adjustments.company_id

            INNER JOIN users AS creator
                ON creator.id =
                    inventory_adjustments.created_by_user_id

            LEFT JOIN users AS completer
                ON completer.id =
                    inventory_adjustments.completed_by_user_id

            LEFT JOIN (
                SELECT
                    inventory_adjustment_id,

                    COUNT(*) AS item_count,

                    SUM(
                        direction = 'increase'
                    ) AS increase_items,

                    SUM(
                        direction = 'decrease'
                    ) AS decrease_items,

                    SUM(
                        CASE
                            WHEN direction = 'increase'
                                THEN quantity
                            ELSE 0
                        END
                    ) AS total_increase,

                    SUM(
                        CASE
                            WHEN direction = 'decrease'
                                THEN quantity
                            ELSE 0
                        END
                    ) AS total_decrease

                FROM inventory_adjustment_items

                GROUP BY inventory_adjustment_id
            ) AS item_statistics
                ON item_statistics.inventory_adjustment_id =
                    inventory_adjustments.id

            WHERE inventory_adjustments.company_id =
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
                AND inventory_adjustments.status =
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
                AND inventory_adjustments.warehouse_id =
                    :warehouse_id
            ";

            $parameters['warehouse_id'] =
                $filters['warehouse_id'];
        }

        if (
            isset($filters['reason_type']) &&
            is_string($filters['reason_type']) &&
            $filters['reason_type'] !== ''
        ) {
            $sql .= "
                AND inventory_adjustments.reason_type =
                    :reason_type
            ";

            $parameters['reason_type'] =
                $filters['reason_type'];
        }

        if (
            isset($filters['search']) &&
            is_string($filters['search']) &&
            $filters['search'] !== ''
        ) {
            $searchTerm =
                '%' . $filters['search'] . '%';

            $sql .= "
                AND (
                    inventory_adjustments.adjustment_number
                        LIKE :search_number

                    OR inventory_adjustments.reason_description
                        LIKE :search_reason

                    OR warehouses.name
                        LIKE :search_warehouse

                    OR warehouses.code
                        LIKE :search_code

                    OR creator.name
                        LIKE :search_user
                )
            ";

            $parameters['search_number'] =
                $searchTerm;

            $parameters['search_reason'] =
                $searchTerm;

            $parameters['search_warehouse'] =
                $searchTerm;

            $parameters['search_code'] =
                $searchTerm;

            $parameters['search_user'] =
                $searchTerm;
        }

        $sql .= "
            ORDER BY
                inventory_adjustments.adjustment_date DESC,
                inventory_adjustments.id DESC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findByIdAndCompany(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT
                inventory_adjustments.*,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                creator.name
                    AS created_by_user_name,

                completer.name
                    AS completed_by_user_name,

                canceller.name
                    AS cancelled_by_user_name

            FROM inventory_adjustments

            INNER JOIN warehouses
                ON warehouses.id =
                    inventory_adjustments.warehouse_id

                AND warehouses.company_id =
                    inventory_adjustments.company_id

            INNER JOIN users AS creator
                ON creator.id =
                    inventory_adjustments.created_by_user_id

            LEFT JOIN users AS completer
                ON completer.id =
                    inventory_adjustments.completed_by_user_id

            LEFT JOIN users AS canceller
                ON canceller.id =
                    inventory_adjustments.cancelled_by_user_id

            WHERE inventory_adjustments.id = :id

            AND inventory_adjustments.company_id =
                :company_id

            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $adjustment = $statement->fetch();

        if ($adjustment === false) {
            return null;
        }

        return $adjustment;
    }

    public function findForUpdate(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT *
            FROM inventory_adjustments
            WHERE id = :id
            AND company_id = :company_id
            LIMIT 1
            FOR UPDATE
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $adjustment = $statement->fetch();

        if ($adjustment === false) {
            return null;
        }

        return $adjustment;
    }

    public function markCompleted(
        int $id,
        int $companyId,
        int $userId
    ): bool {
        $sql = "
            UPDATE inventory_adjustments
            SET
                status = 'completed',
                completed_by_user_id =
                    :completed_by_user_id,
                completed_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND status = 'draft'
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'completed_by_user_id' =>
                $userId,

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
        $sql = "
            UPDATE inventory_adjustments
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
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'cancelled_by_user_id' =>
                $userId,

            'cancellation_reason' =>
                $reason,

            'id' => $id,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }
}