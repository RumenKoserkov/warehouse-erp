<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PurchaseItem extends Model
{
    public function create(array $data): bool
    {
        $sql = "
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

            inventory_unit_cost,
            inventory_total_cost,

            discount_amount,
            vat_rate,
            net_amount,
            tax_amount,
            total_price
        )
        VALUES
        (
            :purchase_id,
            :company_id,
            :product_id,
            :product_name,
            :product_internal_code,
            :quantity,
            :unit,
            :unit_cost,

            :inventory_unit_cost,
            :inventory_total_cost,

            :discount_amount,
            :vat_rate,
            :net_amount,
            :tax_amount,
            :total_price
        )
    ";

        $statement =
            $this->db->prepare($sql);

        return $statement->execute([
            'purchase_id' =>
            $data['purchase_id'],

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

            'unit_cost' =>
            $data['unit_cost'],

            'inventory_unit_cost' =>
            $data['inventory_unit_cost'],

            'inventory_total_cost' =>
            $data['inventory_total_cost'],

            'discount_amount' =>
            $data['discount_amount'],

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
