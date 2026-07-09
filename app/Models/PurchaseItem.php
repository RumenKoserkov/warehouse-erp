<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PurchaseItem extends Model
{
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO purchase_items
            (
                purchase_id,
                company_id,
                product_id,
                product_name,
                product_internal_code,
                quantity,
                unit,
                unit_cost,
                discount_amount,
                total_price
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['purchase_id'],
            $data['company_id'],
            $data['product_id'],
            $data['product_name'],
            $data['product_internal_code'],
            $data['quantity'],
            $data['unit'],
            $data['unit_cost'],
            $data['discount_amount'],
            $data['total_price'],
        ]);
    }

    public function allByPurchase(int $purchaseId, int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                purchase_items.*,
                products.barcode,
                products.image_path

            FROM purchase_items

            INNER JOIN products
                ON purchase_items.product_id = products.id

            WHERE purchase_items.purchase_id = ?
            AND purchase_items.company_id = ?

            ORDER BY purchase_items.id ASC
        ");

        $stmt->execute([
            $purchaseId,
            $companyId,
        ]);

        return $stmt->fetchAll();
    }

    public function deleteByPurchase(int $purchaseId, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM purchase_items
            WHERE purchase_id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $purchaseId,
            $companyId,
        ]);
    }

    public function calculateSubtotalByPurchase(int $purchaseId, int $companyId): float
    {
        $stmt = $this->db->prepare("
            SELECT
                SUM(total_price) AS subtotal
            FROM purchase_items
            WHERE purchase_id = ?
            AND company_id = ?
        ");

        $stmt->execute([
            $purchaseId,
            $companyId,
        ]);

        $result = $stmt->fetch();

        if (!$result || $result['subtotal'] === null) {
            return 0;
        }

        return (float) $result['subtotal'];
    }
}