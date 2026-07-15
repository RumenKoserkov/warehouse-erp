<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InvoiceItem extends Model
{
    public function create(array $data): bool
    {
        $sql = "
            INSERT INTO invoice_items
            (
                invoice_id,
                company_id,
                product_id,
                description,
                product_internal_code,
                quantity,
                unit,
                unit_price,
                discount_amount,
                vat_rate,
                net_amount,
                tax_amount,
                total_amount
            )
            VALUES
            (
                :invoice_id,
                :company_id,
                :product_id,
                :description,
                :product_internal_code,
                :quantity,
                :unit,
                :unit_price,
                :discount_amount,
                :vat_rate,
                :net_amount,
                :tax_amount,
                :total_amount
            )
        ";

        $statement = $this->db->prepare($sql);

        return $statement->execute($data);
    }

    public function allByInvoice(
        int $invoiceId,
        int $companyId
    ): array {
        $sql = "
            SELECT *
            FROM invoice_items
            WHERE invoice_id = :invoice_id
            AND company_id = :company_id
            ORDER BY id ASC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'invoice_id' => $invoiceId,
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }
}