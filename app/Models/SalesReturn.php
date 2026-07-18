<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SalesReturn extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO sales_returns
            (
                company_id,
                sale_id,
                warehouse_id,
                return_number,
                return_date,
                reason_type,
                reason_description,
                status,
                subtotal_amount,
                discount_amount,
                net_amount,
                tax_amount,
                total_amount,
                notes,
                created_by_user_id
            )
            VALUES
            (
                :company_id,
                :sale_id,
                :warehouse_id,
                NULL,
                :return_date,
                :reason_type,
                :reason_description,
                'draft',
                0,
                0,
                0,
                0,
                0,
                :notes,
                :created_by_user_id
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' =>
                $data['company_id'],

            'sale_id' =>
                $data['sale_id'],

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
        $sql = "
            UPDATE sales_returns
            SET
                return_number =
                    :return_number,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND return_number IS NULL
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'return_number' =>
                $returnNumber,

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
        $sql = "
            UPDATE sales_returns
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
        ";

        $statement = $this->db->prepare($sql);

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
                sales_returns.*,

                sales.sale_number,
                sales.sale_date,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                clients.name
                    AS client_name,

                clients.company_name
                    AS client_company_name,

                creator.name
                    AS created_by_user_name,

                completer.name
                    AS completed_by_user_name,

                COALESCE(
                    item_totals.item_count,
                    0
                ) AS item_count,

                COALESCE(
                    item_totals.return_quantity,
                    0
                ) AS returned_quantity,

                COALESCE(
                    item_totals.restock_quantity,
                    0
                ) AS restocked_quantity

            FROM sales_returns

            INNER JOIN sales
                ON sales.id =
                    sales_returns.sale_id

                AND sales.company_id =
                    sales_returns.company_id

            INNER JOIN warehouses
                ON warehouses.id =
                    sales_returns.warehouse_id

                AND warehouses.company_id =
                    sales_returns.company_id

            LEFT JOIN clients
                ON clients.id =
                    sales.client_id

                AND clients.company_id =
                    sales.company_id

            INNER JOIN users AS creator
                ON creator.id =
                    sales_returns.created_by_user_id

            LEFT JOIN users AS completer
                ON completer.id =
                    sales_returns.completed_by_user_id

            LEFT JOIN (
                SELECT
                    sales_return_id,

                    COUNT(*) AS item_count,

                    SUM(return_quantity)
                        AS return_quantity,

                    SUM(restock_quantity)
                        AS restock_quantity

                FROM sales_return_items

                GROUP BY sales_return_id
            ) AS item_totals
                ON item_totals.sales_return_id =
                    sales_returns.id

            WHERE sales_returns.company_id =
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
                AND sales_returns.status =
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
                AND sales_returns.warehouse_id =
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
            $searchTerm =
                '%' . $filters['search'] . '%';

            $sql .= "
                AND (
                    sales_returns.return_number
                        LIKE :search_return_number

                    OR sales.sale_number
                        LIKE :search_sale_number

                    OR sales_returns.reason_description
                        LIKE :search_reason

                    OR clients.name
                        LIKE :search_client_name

                    OR clients.company_name
                        LIKE :search_company_name

                    OR warehouses.name
                        LIKE :search_warehouse
                )
            ";

            $parameters[
                'search_return_number'
            ] = $searchTerm;

            $parameters[
                'search_sale_number'
            ] = $searchTerm;

            $parameters['search_reason'] =
                $searchTerm;

            $parameters[
                'search_client_name'
            ] = $searchTerm;

            $parameters[
                'search_company_name'
            ] = $searchTerm;

            $parameters[
                'search_warehouse'
            ] = $searchTerm;
        }

        $sql .= "
            ORDER BY
                sales_returns.return_date DESC,
                sales_returns.id DESC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function allBySale(
        int $saleId,
        int $companyId
    ): array {
        $sql = "
            SELECT
                sales_returns.*,

                creator.name
                    AS created_by_user_name

            FROM sales_returns

            INNER JOIN users AS creator
                ON creator.id =
                    sales_returns.created_by_user_id

            WHERE sales_returns.sale_id =
                :sale_id

            AND sales_returns.company_id =
                :company_id

            ORDER BY sales_returns.id DESC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'sale_id' => $saleId,
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function findByIdAndCompany(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT
                sales_returns.*,

                sales.sale_number,
                sales.sale_date,
                sales.status AS sale_status,

                sales.vat_registered,
                sales.prices_include_vat,
                sales.default_vat_rate,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                clients.name
                    AS client_name,

                clients.company_name
                    AS client_company_name,

                clients.phone
                    AS client_phone,

                clients.email
                    AS client_email,

                creator.name
                    AS created_by_user_name,

                completer.name
                    AS completed_by_user_name,

                canceller.name
                    AS cancelled_by_user_name

            FROM sales_returns

            INNER JOIN sales
                ON sales.id =
                    sales_returns.sale_id

                AND sales.company_id =
                    sales_returns.company_id

            INNER JOIN warehouses
                ON warehouses.id =
                    sales_returns.warehouse_id

                AND warehouses.company_id =
                    sales_returns.company_id

            LEFT JOIN clients
                ON clients.id =
                    sales.client_id

                AND clients.company_id =
                    sales.company_id

            INNER JOIN users AS creator
                ON creator.id =
                    sales_returns.created_by_user_id

            LEFT JOIN users AS completer
                ON completer.id =
                    sales_returns.completed_by_user_id

            LEFT JOIN users AS canceller
                ON canceller.id =
                    sales_returns.cancelled_by_user_id

            WHERE sales_returns.id = :id

            AND sales_returns.company_id =
                :company_id

            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $salesReturn = $statement->fetch();

        if ($salesReturn === false) {
            return null;
        }

        return $salesReturn;
    }

    public function findForUpdate(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT *
            FROM sales_returns
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

        $salesReturn = $statement->fetch();

        if ($salesReturn === false) {
            return null;
        }

        return $salesReturn;
    }

    public function hasActiveForSale(
        int $saleId,
        int $companyId
    ): bool {
        $sql = "
            SELECT id
            FROM sales_returns
            WHERE sale_id = :sale_id
            AND company_id = :company_id
            AND status IN (
                'draft',
                'completed'
            )
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'sale_id' => $saleId,
            'company_id' => $companyId,
        ]);

        return $statement->fetch() !== false;
    }

    public function hasDraftForSale(
        int $saleId,
        int $companyId
    ): bool {
        $sql = "
            SELECT id
            FROM sales_returns
            WHERE sale_id = :sale_id
            AND company_id = :company_id
            AND status = 'draft'
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'sale_id' => $saleId,
            'company_id' => $companyId,
        ]);

        return $statement->fetch() !== false;
    }

    public function markCompleted(
        int $id,
        int $companyId,
        int $userId
    ): bool {
        $sql = "
            UPDATE sales_returns
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
            UPDATE sales_returns
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