<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SaleItem extends Model
{
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
        INSERT INTO sale_items
        (
            sale_id,
            company_id,
            product_id,
            product_name,
            product_internal_code,
            quantity,
            unit,
            unit_price,
            discount_amount,
            promotion_discount_amount,
            vat_rate,
            net_amount,
            tax_amount,
            total_price
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

        return $stmt->execute([
            $data['sale_id'],
            $data['company_id'],
            $data['product_id'],
            $data['product_name'],
            $data['product_internal_code'],
            $data['quantity'],
            $data['unit'],
            $data['unit_price'],
            $data['discount_amount'],
            $data['promotion_discount_amount'] ?? 0,
            $data['vat_rate'],
            $data['net_amount'],
            $data['tax_amount'],
            $data['total_price'],
        ]);
    }

    public function allBySale(int $saleId, int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sale_items.*,
                products.barcode,
                products.image_path
            FROM sale_items
            INNER JOIN products ON sale_items.product_id = products.id
            WHERE sale_items.sale_id = ?
            AND sale_items.company_id = ?
            ORDER BY sale_items.id ASC
        ");

        $stmt->execute([$saleId, $companyId]);

        return $stmt->fetchAll();
    }

    public function deleteBySale(int $saleId, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM sale_items
            WHERE sale_id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $saleId,
            $companyId,
        ]);
    }

    public function calculateSubtotalBySale(int $saleId, int $companyId): float
    {
        $stmt = $this->db->prepare("
            SELECT SUM(total_price) AS subtotal
            FROM sale_items
            WHERE sale_id = ?
            AND company_id = ?
        ");

        $stmt->execute([
            $saleId,
            $companyId,
        ]);

        $result = $stmt->fetch();

        if (!$result || $result['subtotal'] === null) {
            return 0;
        }

        return (float)$result['subtotal'];
    }
}
