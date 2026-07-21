<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SaleItem extends Model
{
    public function create(array $data): bool
    {
        $sql = "
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

            unit_cost,
            total_cost,
            gross_profit,
            gross_margin_percent,

            discount_amount,
            promotion_discount_amount,
            vat_rate,
            net_amount,
            tax_amount,
            total_price
        )
        VALUES
        (
            :sale_id,
            :company_id,
            :product_id,
            :product_name,
            :product_internal_code,
            :quantity,
            :unit,
            :unit_price,

            :unit_cost,
            :total_cost,
            :gross_profit,
            :gross_margin_percent,

            :discount_amount,
            :promotion_discount_amount,
            :vat_rate,
            :net_amount,
            :tax_amount,
            :total_price
        )
    ";

        $statement =
            $this->db->prepare($sql);

        return $statement->execute([
            'sale_id' =>
            $data['sale_id'],

            'company_id' =>
            $data['company_id'],

            'product_id' =>
            $data['product_id'],

            'product_name' =>
            $data['product_name'],

            'product_internal_code' =>
            $data['product_internal_code'],

            'quantity' =>
            $data['quantity'],

            'unit' =>
            $data['unit'],

            'unit_price' =>
            $data['unit_price'],

            'unit_cost' =>
            $data['unit_cost'],

            'total_cost' =>
            $data['total_cost'],

            'gross_profit' =>
            $data['gross_profit'],

            'gross_margin_percent' =>
            $data['gross_margin_percent'],

            'discount_amount' =>
            $data['discount_amount'],

            'promotion_discount_amount' =>
            $data['promotion_discount_amount'] ?? 0,

            'vat_rate' =>
            $data['vat_rate'],

            'net_amount' =>
            $data['net_amount'],

            'tax_amount' =>
            $data['tax_amount'],

            'total_price' =>
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
