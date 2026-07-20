<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Purchase extends Model
{
    public function generateNextPurchaseNumber(int $companyId): string
    {
        $stmt = $this->db->prepare("
            SELECT purchase_number
            FROM purchases
            WHERE company_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([$companyId]);

        $lastPurchase = $stmt->fetch();

        if (!$lastPurchase) {
            return 'PUR-000001';
        }

        $lastNumber = $lastPurchase['purchase_number'];
        $number = (int) str_replace('PUR-', '', $lastNumber);
        $nextNumber = $number + 1;

        return 'PUR-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
        INSERT INTO purchases
        (
            company_id,
            supplier_id,
            warehouse_id,
            user_id,
            purchase_number,
            purchase_date,
            status,
            vat_registered,
            prices_include_vat,
            default_vat_rate,
            subtotal,
            discount_amount,
            tax_amount,
            total_amount,
            payment_method,
            note
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

        $stmt->execute([
            $data['company_id'],
            $data['supplier_id'],
            $data['warehouse_id'],
            $data['user_id'],
            $data['purchase_number'],
            $data['purchase_date'],
            $data['status'],
            $data['vat_registered'],
            $data['prices_include_vat'],
            $data['default_vat_rate'],
            $data['subtotal'],
            $data['discount_amount'],
            $data['tax_amount'],
            $data['total_amount'],
            $data['payment_method'],
            $data['note'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function allByCompany(int $companyId, string $search = ''): array
    {
        $sql = "
            SELECT
                purchases.id,
                purchases.purchase_number,
                purchases.purchase_date,
                purchases.status,
                purchases.subtotal,
                purchases.discount_amount,
                purchases.tax_amount,
                purchases.total_amount,
                purchases.payment_method,
                purchases.created_at,

                suppliers.name AS supplier_name,
                suppliers.company_name AS supplier_company_name,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                users.name AS user_name

            FROM purchases

            LEFT JOIN suppliers
                ON purchases.supplier_id = suppliers.id

            INNER JOIN warehouses
                ON purchases.warehouse_id = warehouses.id

            LEFT JOIN users
                ON purchases.user_id = users.id

            WHERE purchases.company_id = ?
        ";

        $params = [$companyId];

        if ($search !== '') {

            $sql .= "
                AND
                (
                    purchases.purchase_number LIKE ?
                    OR suppliers.name LIKE ?
                    OR suppliers.company_name LIKE ?
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
            ORDER BY purchases.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findByIdAndCompany(int $id, int $companyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                purchases.*,

                suppliers.name AS supplier_name,
                suppliers.phone AS supplier_phone,
                suppliers.email AS supplier_email,
                suppliers.company_name AS supplier_company_name,
                suppliers.eik AS supplier_eik,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                users.name AS user_name

            FROM purchases

            LEFT JOIN suppliers
                ON purchases.supplier_id = suppliers.id

            INNER JOIN warehouses
                ON purchases.warehouse_id = warehouses.id

            LEFT JOIN users
                ON purchases.user_id = users.id

            WHERE purchases.id = ?
            AND purchases.company_id = ?

            LIMIT 1
        ");

        $stmt->execute([$id, $companyId]);

        $purchase = $stmt->fetch();

        if (!$purchase) {
            return null;
        }

        return $purchase;
    }

    public function findForUpdate(
        int $id,
        int $companyId
    ): ?array {
        $statement = $this->db->prepare(
            "
        SELECT *
        FROM purchases
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

        $purchase = $statement->fetch();

        if ($purchase === false) {
            return null;
        }

        return $purchase;
    }

    public function updateTotals(int $id, int $companyId, array $totals): bool
    {
        $stmt = $this->db->prepare("
            UPDATE purchases
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
            UPDATE purchases
            SET status = 'cancelled'
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $id,
            $companyId,
        ]);
    }
}
