<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InventoryCount extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO inventory_counts
            (
                company_id,
                warehouse_id,
                count_number,
                count_date,
                snapshot_transaction_id,
                snapshot_at,
                status,
                notes,
                created_by_user_id
            )
            VALUES
            (
                :company_id,
                :warehouse_id,
                NULL,
                :count_date,
                :snapshot_transaction_id,
                :snapshot_at,
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

            'count_date' =>
                $data['count_date'],

            'snapshot_transaction_id' =>
                $data['snapshot_transaction_id'],

            'snapshot_at' =>
                $data['snapshot_at'],

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
        string $countNumber
    ): bool {
        $sql = "
            UPDATE inventory_counts
            SET count_number = :count_number
            WHERE id = :id
            AND company_id = :company_id
            AND count_number IS NULL
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'count_number' => $countNumber,
            'id' => $id,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function hasOpenForWarehouse(
        int $companyId,
        int $warehouseId
    ): bool {
        $sql = "
            SELECT id
            FROM inventory_counts
            WHERE company_id = :company_id
            AND warehouse_id = :warehouse_id
            AND status = 'draft'
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
        ]);

        return $statement->fetch() !== false;
    }

    public function allByCompany(
        int $companyId,
        array $filters = []
    ): array {
        $sql = "
            SELECT
                inventory_counts.*,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                creator.name
                    AS created_by_user_name,

                completer.name
                    AS completed_by_user_name,

                COALESCE(
                    item_stats.total_items,
                    0
                ) AS total_items,

                COALESCE(
                    item_stats.counted_items,
                    0
                ) AS counted_items,

                COALESCE(
                    item_stats.difference_items,
                    0
                ) AS difference_items

            FROM inventory_counts

            INNER JOIN warehouses
                ON warehouses.id =
                    inventory_counts.warehouse_id

                AND warehouses.company_id =
                    inventory_counts.company_id

            INNER JOIN users AS creator
                ON creator.id =
                    inventory_counts.created_by_user_id

            LEFT JOIN users AS completer
                ON completer.id =
                    inventory_counts.completed_by_user_id

            LEFT JOIN (
                SELECT
                    inventory_count_id,

                    COUNT(*) AS total_items,

                    SUM(
                        CASE
                            WHEN counted_quantity
                                IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS counted_items,

                    SUM(
                        CASE
                            WHEN difference_quantity
                                IS NOT NULL
                            AND ABS(
                                difference_quantity
                            ) > 0.0005
                            THEN 1
                            ELSE 0
                        END
                    ) AS difference_items

                FROM inventory_count_items

                GROUP BY inventory_count_id
            ) AS item_stats
                ON item_stats.inventory_count_id =
                    inventory_counts.id

            WHERE inventory_counts.company_id =
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
                AND inventory_counts.status =
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
                AND inventory_counts.warehouse_id =
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
            $search = '%' .
                $filters['search'] .
                '%';

            $sql .= "
                AND (
                    inventory_counts.count_number
                        LIKE :search_number

                    OR warehouses.name
                        LIKE :search_warehouse

                    OR warehouses.code
                        LIKE :search_code

                    OR creator.name
                        LIKE :search_creator
                )
            ";

            $parameters['search_number'] =
                $search;

            $parameters['search_warehouse'] =
                $search;

            $parameters['search_code'] =
                $search;

            $parameters['search_creator'] =
                $search;
        }

        $sql .= "
            ORDER BY inventory_counts.id DESC
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
                inventory_counts.*,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                creator.name
                    AS created_by_user_name,

                completer.name
                    AS completed_by_user_name,

                canceller.name
                    AS cancelled_by_user_name,

                COALESCE(
                    item_stats.total_items,
                    0
                ) AS total_items,

                COALESCE(
                    item_stats.counted_items,
                    0
                ) AS counted_items,

                COALESCE(
                    item_stats.difference_items,
                    0
                ) AS difference_items

            FROM inventory_counts

            INNER JOIN warehouses
                ON warehouses.id =
                    inventory_counts.warehouse_id

                AND warehouses.company_id =
                    inventory_counts.company_id

            INNER JOIN users AS creator
                ON creator.id =
                    inventory_counts.created_by_user_id

            LEFT JOIN users AS completer
                ON completer.id =
                    inventory_counts.completed_by_user_id

            LEFT JOIN users AS canceller
                ON canceller.id =
                    inventory_counts.cancelled_by_user_id

            LEFT JOIN (
                SELECT
                    inventory_count_id,

                    COUNT(*) AS total_items,

                    SUM(
                        CASE
                            WHEN counted_quantity
                                IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS counted_items,

                    SUM(
                        CASE
                            WHEN difference_quantity
                                IS NOT NULL
                            AND ABS(
                                difference_quantity
                            ) > 0.0005
                            THEN 1
                            ELSE 0
                        END
                    ) AS difference_items

                FROM inventory_count_items

                GROUP BY inventory_count_id
            ) AS item_stats
                ON item_stats.inventory_count_id =
                    inventory_counts.id

            WHERE inventory_counts.id = :id
            AND inventory_counts.company_id =
                :company_id

            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $inventoryCount = $statement->fetch();

        if ($inventoryCount === false) {
            return null;
        }

        return $inventoryCount;
    }

    public function findForUpdate(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT *
            FROM inventory_counts
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

        $inventoryCount = $statement->fetch();

        if ($inventoryCount === false) {
            return null;
        }

        return $inventoryCount;
    }

    public function markCompleted(
        int $id,
        int $companyId,
        int $userId
    ): bool {
        $sql = "
            UPDATE inventory_counts
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
            UPDATE inventory_counts
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