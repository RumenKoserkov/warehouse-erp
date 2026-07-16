<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Invoice extends Model
{
    public function create(array $data): int
    {
        $saleId = null;

        if (
            isset($data['sale_id']) &&
            $data['sale_id'] !== null &&
            (int) $data['sale_id'] > 0
        ) {
            $saleId = (int) $data['sale_id'];
        }

        $status = 'draft';

        if (isset($data['status'])) {
            $status = (string) $data['status'];
        }

        $data['active_sale_id'] =
            $saleId !== null &&
            $status !== 'cancelled'
            ? $saleId
            : null;

        $sql = "
            INSERT INTO invoices
            (
                company_id,
                client_id,
                sale_id,
                related_invoice_id,
                active_sale_id,
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

                note,
                correction_reason
            )
            VALUES
            (
                :company_id,
                :client_id,
                :sale_id,
                :related_invoice_id,
                :active_sale_id,
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

                :note,
                :correction_reason
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function allByCompany(
        int $companyId,
        string $search = '',
        string $dueFilter = 'all'
    ): array {
        $creditExpression = "
            COALESCE(
                credit_totals.credit_total,
                0
            )
        ";

        $paidExpression = "
            COALESCE(
                payment_totals.paid_amount,
                0
            )
        ";

        $adjustedExpression = "
            GREATEST(
                invoices.total_amount -
                {$creditExpression},
                0
            )
        ";

        $balanceExpression = "
            GREATEST(
                {$adjustedExpression} -
                {$paidExpression},
                0
            )
        ";

        $sql = "
            SELECT
                invoices.id,
                invoices.document_type,
                invoices.related_invoice_id,
                invoices.invoice_number,
                invoices.invoice_date,
                invoices.supply_date,
                invoices.due_date,
                invoices.status,
                invoices.currency,

                invoices.client_display_name,
                invoices.client_legal_name,
                invoices.client_eik,
                invoices.client_vat_number,

                invoices.total_amount,
                invoices.correction_reason,
                invoices.cancelled_at,
                invoices.created_at,

                users.name
                    AS created_by_user_name,

                {$creditExpression}
                    AS credit_total,

                {$adjustedExpression}
                    AS adjusted_total,

                {$paidExpression}
                    AS paid_amount,

                {$balanceExpression}
                    AS balance_due

            FROM invoices

            LEFT JOIN users
                ON users.id =
                    invoices.created_by_user_id

            LEFT JOIN (
                SELECT
                    company_id,
                    related_invoice_id,

                    SUM(
                        ABS(total_amount)
                    ) AS credit_total

                FROM invoices

                WHERE document_type =
                    'credit_note'

                AND status = 'issued'

                GROUP BY
                    company_id,
                    related_invoice_id
            ) AS credit_totals
                ON credit_totals.company_id =
                    invoices.company_id

                AND credit_totals.related_invoice_id =
                    invoices.id

            LEFT JOIN (
                SELECT
                    company_id,
                    invoice_id,

                    SUM(amount)
                        AS paid_amount

                FROM payments

                WHERE status = 'completed'

                GROUP BY
                    company_id,
                    invoice_id
            ) AS payment_totals
                ON payment_totals.company_id =
                    invoices.company_id

                AND payment_totals.invoice_id =
                    invoices.id

            WHERE invoices.company_id =
                :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        if ($search !== '') {
            $searchTerm = '%' . $search . '%';

            $sql .= "
                AND (
                    invoices.invoice_number
                        LIKE :search_number

                    OR invoices.client_display_name
                        LIKE :search_display_name

                    OR invoices.client_legal_name
                        LIKE :search_legal_name

                    OR invoices.client_eik
                        LIKE :search_eik

                    OR invoices.client_vat_number
                        LIKE :search_vat
                )
            ";

            $parameters['search_number'] =
                $searchTerm;

            $parameters['search_display_name'] =
                $searchTerm;

            $parameters['search_legal_name'] =
                $searchTerm;

            $parameters['search_eik'] =
                $searchTerm;

            $parameters['search_vat'] =
                $searchTerm;
        }

        if ($dueFilter === 'overdue') {
            $sql .= "
                AND invoices.document_type =
                    'invoice'

                AND invoices.status = 'issued'

                AND invoices.due_date IS NOT NULL

                AND invoices.due_date < CURDATE()

                AND {$balanceExpression} > 0.009
            ";
        } elseif ($dueFilter === 'due_today') {
            $sql .= "
                AND invoices.document_type =
                    'invoice'

                AND invoices.status = 'issued'

                AND invoices.due_date = CURDATE()

                AND {$balanceExpression} > 0.009
            ";
        } elseif ($dueFilter === 'due_soon') {
            $sql .= "
                AND invoices.document_type =
                    'invoice'

                AND invoices.status = 'issued'

                AND invoices.due_date > CURDATE()

                AND invoices.due_date <=
                    DATE_ADD(
                        CURDATE(),
                        INTERVAL 7 DAY
                    )

                AND {$balanceExpression} > 0.009
            ";
        } elseif ($dueFilter === 'unpaid') {
            $sql .= "
                AND invoices.document_type =
                    'invoice'

                AND invoices.status = 'issued'

                AND {$paidExpression} <= 0.009

                AND {$balanceExpression} > 0.009
            ";
        } elseif (
            $dueFilter === 'partially_paid'
        ) {
            $sql .= "
                AND invoices.document_type =
                    'invoice'

                AND invoices.status = 'issued'

                AND {$paidExpression} > 0.009

                AND {$balanceExpression} > 0.009
            ";
        } elseif ($dueFilter === 'paid') {
            $sql .= "
                AND invoices.document_type =
                    'invoice'

                AND invoices.status = 'issued'

                AND {$balanceExpression} <= 0.009
            ";
        } elseif (
            $dueFilter === 'no_due_date'
        ) {
            $sql .= "
                AND invoices.document_type =
                    'invoice'

                AND invoices.status = 'issued'

                AND invoices.due_date IS NULL

                AND {$balanceExpression} > 0.009
            ";
        }

        $sql .= "
            ORDER BY
                invoices.invoice_date DESC,
                invoices.id DESC
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
                canceller.name AS cancelled_by_user_name,

                sales.sale_number AS source_sale_number,

                related_invoice.invoice_number
                    AS related_invoice_number,

                related_invoice.invoice_date
                    AS related_invoice_date,

                related_invoice.status
                    AS related_invoice_status

            FROM invoices

            LEFT JOIN users AS creator
                ON creator.id =
                    invoices.created_by_user_id

            LEFT JOIN users AS issuer
                ON issuer.id =
                    invoices.issued_by_user_id

            LEFT JOIN users AS canceller
                ON canceller.id =
                    invoices.cancelled_by_user_id

            LEFT JOIN sales
                ON sales.id = invoices.sale_id
                AND sales.company_id =
                    invoices.company_id

            LEFT JOIN invoices AS related_invoice
                ON related_invoice.id =
                    invoices.related_invoice_id
                AND related_invoice.company_id =
                    invoices.company_id

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
            WHERE active_sale_id = :sale_id
            AND company_id = :company_id
            ORDER BY id DESC
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

    public function creditNotesForInvoice(
        int $invoiceId,
        int $companyId
    ): array {
        $sql = "
            SELECT
                invoices.id,
                invoices.invoice_number,
                invoices.invoice_date,
                invoices.status,
                invoices.total_amount,
                invoices.currency,
                invoices.correction_reason,
                invoices.created_at
            FROM invoices
            WHERE invoices.company_id = :company_id
            AND invoices.related_invoice_id =
                :invoice_id
            AND invoices.document_type =
                'credit_note'
            ORDER BY invoices.id DESC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
            'invoice_id' => $invoiceId,
        ]);

        return $statement->fetchAll();
    }

    public function hasActiveCreditNotes(
        int $invoiceId,
        int $companyId
    ): bool {
        $sql = "
            SELECT id
            FROM invoices
            WHERE company_id = :company_id
            AND related_invoice_id = :invoice_id
            AND document_type = 'credit_note'
            AND status IN ('draft', 'issued')
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
            'invoice_id' => $invoiceId,
        ]);

        return $statement->fetch() !== false;
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

    public function markAsCancelled(
        int $id,
        int $companyId,
        int $userId,
        string $reason
    ): bool {
        $sql = "
            UPDATE invoices
            SET
                status = 'cancelled',
                active_sale_id = NULL,
                cancelled_at = NOW(),
                cancelled_by_user_id =
                    :cancelled_by_user_id,
                cancellation_reason =
                    :cancellation_reason,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND status IN ('draft', 'issued')
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'cancelled_by_user_id' => $userId,
            'cancellation_reason' => $reason,
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
            AND document_type = 'invoice'
            AND invoice_number IS NOT NULL
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function issuedCreditTotalForInvoice(
        int $invoiceId,
        int $companyId
    ): float {
        $sql = "
        SELECT COALESCE(
            SUM(ABS(total_amount)),
            0
        )
        FROM invoices
        WHERE company_id = :company_id
        AND related_invoice_id = :invoice_id
        AND document_type = 'credit_note'
        AND status = 'issued'
    ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
            'invoice_id' => $invoiceId,
        ]);

        return round(
            (float) $statement->fetchColumn(),
            2
        );
    }
}