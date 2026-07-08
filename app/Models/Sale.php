<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Sale extends Model
{
    public function generateNextSaleNumber(int $companyId): string
    {
        $stmt = $this->db->prepare("
            SELECT sale_number
            FROM sales
            WHERE company_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([$companyId]);

        $lastSale = $stmt->fetch();

        if (!$lastSale) {
            return 'SALE-000001';
        }

        $lastNumber = $lastSale['sale_number'];
        $number = (int)str_replace('SALE-', '', $lastNumber);
        $nextNumber = $number + 1;

        return 'SALE-' . str_pad((string)$nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO sales
                (
                    company_id,
                    client_id,
                    warehouse_id,
                    user_id,
                    sale_number,
                    sale_date,
                    status,
                    subtotal,
                    discount_amount,
                    tax_amount,
                    total_amount,
                    payment_method,
                    note
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['company_id'],
            $data['client_id'],
            $data['warehouse_id'],
            $data['user_id'],
            $data['sale_number'],
            $data['sale_date'],
            $data['status'],
            $data['subtotal'],
            $data['discount_amount'],
            $data['tax_amount'],
            $data['total_amount'],
            $data['payment_method'],
            $data['note'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function allByCompany(int $companyId, string $search = ''): array
    {
        $sql = "
            SELECT
                sales.id,
                sales.sale_number,
                sales.sale_date,
                sales.status,
                sales.subtotal,
                sales.discount_amount,
                sales.tax_amount,
                sales.total_amount,
                sales.payment_method,
                sales.created_at,

                clients.name AS client_name,
                clients.company_name AS client_company_name,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                users.name AS user_name
            FROM sales
            LEFT JOIN clients ON sales.client_id = clients.id
            INNER JOIN warehouses ON sales.warehouse_id = warehouses.id
            LEFT JOIN users ON sales.user_id = users.id
            WHERE sales.company_id = ?
        ";

        $params = [$companyId];

        if ($search !== '') {
            $sql .= "
                AND (
                    sales.sale_number LIKE ?
                    OR clients.name LIKE ?
                    OR clients.company_name LIKE ?
                    OR warehouses.name LIKE ?
                    OR warehouses.code LIKE ?
                    OR users.name LIKE ?
                )
            ";

            $searchTerm = '%' . $search . '%';

            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= "
            ORDER BY sales.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findByIdAndCompany(int $id, int $companyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                sales.*,

                clients.name AS client_name,
                clients.phone AS client_phone,
                clients.email AS client_email,
                clients.company_name AS client_company_name,
                clients.eik AS client_eik,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                users.name AS user_name
            FROM sales
            LEFT JOIN clients ON sales.client_id = clients.id
            INNER JOIN warehouses ON sales.warehouse_id = warehouses.id
            LEFT JOIN users ON sales.user_id = users.id
            WHERE sales.id = ?
            AND sales.company_id = ?
            LIMIT 1
        ");

        $stmt->execute([$id, $companyId]);

        $sale = $stmt->fetch();

        if (!$sale) {
            return null;
        }

        return $sale;
    }

    public function updateTotals(int $id, int $companyId, array $totals): bool
    {
        $stmt = $this->db->prepare("
            UPDATE sales
            SET
                subtotal = ?,
                discount_amount = ?,
                tax_amount = ?,
                total_amount = ?
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $totals['subtotal'],
            $totals['discount_amount'],
            $totals['tax_amount'],
            $totals['total_amount'],
            $id,
            $companyId,
        ]);
    }

    public function cancel(int $id, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE sales
            SET status = 'cancelled'
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([$id, $companyId]);
    }
}