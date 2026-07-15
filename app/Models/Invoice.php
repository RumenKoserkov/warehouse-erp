<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Invoice extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO invoices
            (
                company_id,
                client_id,
                sale_id,
                created_by_user_id,

                document_type,
                invoice_number,
                invoice_date,
                supply_date,
                due_date,
                status,
                currency,

                vat_registered,
                prices_include_vat,
                default_vat_rate,

                supplier_legal_name,
                supplier_eik,
                supplier_vat_number,
                supplier_manager_name,

                supplier_address,
                supplier_city,
                supplier_postal_code,
                supplier_country,
                supplier_phone,
                supplier_email,

                supplier_bank_name,
                supplier_iban,
                supplier_bic,

                client_type,
                client_display_name,
                client_legal_name,
                client_eik,
                client_vat_number,

                client_address,
                client_city,
                client_postal_code,
                client_country,
                client_email,

                subtotal,
                discount_amount,
                tax_amount,
                total_amount,
                note
            )
            VALUES
            (
                :company_id,
                :client_id,
                :sale_id,
                :created_by_user_id,

                :document_type,
                :invoice_number,
                :invoice_date,
                :supply_date,
                :due_date,
                :status,
                :currency,

                :vat_registered,
                :prices_include_vat,
                :default_vat_rate,

                :supplier_legal_name,
                :supplier_eik,
                :supplier_vat_number,
                :supplier_manager_name,

                :supplier_address,
                :supplier_city,
                :supplier_postal_code,
                :supplier_country,
                :supplier_phone,
                :supplier_email,

                :supplier_bank_name,
                :supplier_iban,
                :supplier_bic,

                :client_type,
                :client_display_name,
                :client_legal_name,
                :client_eik,
                :client_vat_number,

                :client_address,
                :client_city,
                :client_postal_code,
                :client_country,
                :client_email,

                :subtotal,
                :discount_amount,
                :tax_amount,
                :total_amount,
                :note
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function allByCompany(
        int $companyId,
        string $search = ''
    ): array {
        $sql = "
            SELECT
                invoices.id,
                invoices.invoice_number,
                invoices.invoice_date,
                invoices.supply_date,
                invoices.due_date,
                invoices.status,
                invoices.currency,
                invoices.client_display_name,
                invoices.client_legal_name,
                invoices.total_amount,
                invoices.created_at,
                users.name AS created_by_user_name
            FROM invoices
            LEFT JOIN users
                ON users.id = invoices.created_by_user_id
            WHERE invoices.company_id = :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        if ($search !== '') {
            $sql .= "
                AND (
                    invoices.invoice_number LIKE :search
                    OR invoices.client_display_name LIKE :search
                    OR invoices.client_legal_name LIKE :search
                    OR invoices.client_eik LIKE :search
                    OR invoices.client_vat_number LIKE :search
                )
            ";

            $parameters['search'] =
                '%' . $search . '%';
        }

        $sql .= "
            ORDER BY invoices.id DESC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findByIdAndCompany(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT
                invoices.*,
                users.name AS created_by_user_name
            FROM invoices
            LEFT JOIN users
                ON users.id = invoices.created_by_user_id
            WHERE invoices.id = :id
            AND invoices.company_id = :company_id
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $invoice = $statement->fetch();

        if ($invoice === false) {
            return null;
        }

        return $invoice;
    }
}