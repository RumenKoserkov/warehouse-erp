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
                source_invoice_item_id,

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
                :source_invoice_item_id,

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

    public function creditUsageByOriginalInvoice(
        int $originalInvoiceId,
        int $companyId
    ): array {
        $sql = "
            SELECT
                invoice_items.source_invoice_item_id,

                COALESCE(
                    SUM(invoice_items.quantity),
                    0
                ) AS credited_quantity,

                COALESCE(
                    SUM(invoice_items.discount_amount),
                    0
                ) AS credited_discount_amount,

                COALESCE(
                    SUM(invoice_items.net_amount),
                    0
                ) AS credited_net_amount,

                COALESCE(
                    SUM(invoice_items.tax_amount),
                    0
                ) AS credited_tax_amount,

                COALESCE(
                    SUM(invoice_items.total_amount),
                    0
                ) AS credited_total_amount

            FROM invoice_items

            INNER JOIN invoices
                ON invoices.id =
                    invoice_items.invoice_id
                AND invoices.company_id =
                    invoice_items.company_id

            WHERE invoices.company_id = :company_id

            AND invoices.related_invoice_id =
                :original_invoice_id

            AND invoices.document_type =
                'credit_note'

            AND invoices.status IN (
                'draft',
                'issued'
            )

            AND invoice_items.source_invoice_item_id
                IS NOT NULL

            GROUP BY
                invoice_items.source_invoice_item_id
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
            'original_invoice_id' =>
            $originalInvoiceId,
        ]);

        $rows = $statement->fetchAll();

        $usage = [];

        foreach ($rows as $row) {
            $sourceItemId =
                (int) $row['source_invoice_item_id'];

            $usage[$sourceItemId] = $row;
        }

        return $usage;
    }
}
