<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class WarehouseTransaction extends Model
{
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO warehouse_transactions
                (
                    company_id,
                    product_id,
                    from_warehouse_id,
                    to_warehouse_id,
                    user_id,
                    type,
                    quantity,
                    reference_type,
                    reference_id,
                    note
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['company_id'],
            $data['product_id'],
            $data['from_warehouse_id'],
            $data['to_warehouse_id'],
            $data['user_id'],
            $data['type'],
            $data['quantity'],
            $data['reference_type'],
            $data['reference_id'],
            $data['note'],
        ]);
    }

    public function allByCompany(int $companyId, array $filters = []): array
    {
        $sql = "
            SELECT
                warehouse_transactions.id,
                warehouse_transactions.type,
                warehouse_transactions.quantity,
                warehouse_transactions.reference_type,
                warehouse_transactions.reference_id,
                warehouse_transactions.note,
                warehouse_transactions.created_at,

                products.name AS product_name,
                products.internal_code,
                products.barcode,
                products.unit,

                from_warehouse.name AS from_warehouse_name,
                from_warehouse.code AS from_warehouse_code,

                to_warehouse.name AS to_warehouse_name,
                to_warehouse.code AS to_warehouse_code,

                users.name AS user_name

            FROM warehouse_transactions

            INNER JOIN products
                ON warehouse_transactions.product_id = products.id

            LEFT JOIN warehouses AS from_warehouse
                ON warehouse_transactions.from_warehouse_id = from_warehouse.id

            LEFT JOIN warehouses AS to_warehouse
                ON warehouse_transactions.to_warehouse_id = to_warehouse.id

            LEFT JOIN users
                ON warehouse_transactions.user_id = users.id

            WHERE warehouse_transactions.company_id = ?
        ";

        $params = [$companyId];

        if (!empty($filters['search'])) {
            $sql .= "
                AND (
                    products.name LIKE ?
                    OR products.internal_code LIKE ?
                    OR products.barcode LIKE ?
                    OR from_warehouse.name LIKE ?
                    OR from_warehouse.code LIKE ?
                    OR to_warehouse.name LIKE ?
                    OR to_warehouse.code LIKE ?
                    OR users.name LIKE ?
                )
            ";

            $searchTerm = '%' . $filters['search'] . '%';

            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['type'])) {
            $sql .= " AND warehouse_transactions.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['product_id'])) {
            $sql .= " AND warehouse_transactions.product_id = ?";
            $params[] = $filters['product_id'];
        }

        if (!empty($filters['warehouse_id'])) {
            $sql .= "
                AND (
                    warehouse_transactions.from_warehouse_id = ?
                    OR warehouse_transactions.to_warehouse_id = ?
                )
            ";

            $params[] = $filters['warehouse_id'];
            $params[] = $filters['warehouse_id'];
        }

        $sql .= "
            ORDER BY warehouse_transactions.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findByIdAndCompany(int $id, int $companyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                warehouse_transactions.*,

                products.name AS product_name,
                products.internal_code,
                products.unit,

                from_warehouse.name AS from_warehouse_name,
                to_warehouse.name AS to_warehouse_name,

                users.name AS user_name

            FROM warehouse_transactions

            INNER JOIN products
                ON warehouse_transactions.product_id = products.id

            LEFT JOIN warehouses AS from_warehouse
                ON warehouse_transactions.from_warehouse_id = from_warehouse.id

            LEFT JOIN warehouses AS to_warehouse
                ON warehouse_transactions.to_warehouse_id = to_warehouse.id

            LEFT JOIN users
                ON warehouse_transactions.user_id = users.id

            WHERE warehouse_transactions.id = ?
              AND warehouse_transactions.company_id = ?

            LIMIT 1
        ");

        $stmt->execute([$id, $companyId]);

        $transaction = $stmt->fetch();

        if (!$transaction) {
            return null;
        }

        return $transaction;
    }

    public function types(): array
    {
        return [
            'purchase',
            'sale',
            'sale_cancel',
            'in',
            'out',
            'transfer',
            'adjustment',
        ];
    }
}