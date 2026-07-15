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

                creator.name AS created_by_user_name,
                issuer.name AS issued_by_user_name,

                sales.sale_number AS source_sale_number

            FROM invoices

            LEFT JOIN users AS creator
                ON creator.id = invoices.created_by_user_id

            LEFT JOIN users AS issuer
                ON issuer.id = invoices.issued_by_user_id

            LEFT JOIN sales
                ON sales.id = invoices.sale_id
                AND sales.company_id = invoices.company_id

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

    public function findBySaleAndCompany(
        int $saleId,
        int $companyId
    ): ?array {
        $sql = "
            SELECT *
            FROM invoices
            WHERE sale_id = :sale_id
            AND company_id = :company_id
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'sale_id' => $saleId,
            'company_id' => $companyId,
        ]);

        $invoice = $statement->fetch();

        if ($invoice === false) {
            return null;
        }

        return $invoice;
    }

    public function findForUpdate(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT *
            FROM invoices
            WHERE id = :id
            AND company_id = :company_id
            LIMIT 1
            FOR UPDATE
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

    public function markAsIssued(
        int $id,
        int $companyId,
        string $invoiceNumber,
        string $invoiceDate,
        int $userId
    ): bool {
        $sql = "
            UPDATE invoices
            SET
                invoice_number = :invoice_number,
                invoice_date = :invoice_date,
                status = 'issued',
                issued_at = NOW(),
                issued_by_user_id = :issued_by_user_id,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND status = 'draft'
            AND invoice_number IS NULL
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'issued_by_user_id' => $userId,
            'id' => $id,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function countIssuedByCompany(
        int $companyId
    ): int {
        $sql = "
            SELECT COUNT(*)
            FROM invoices
            WHERE company_id = :company_id
            AND invoice_number IS NOT NULL
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return (int) $statement->fetchColumn();
    }
}
