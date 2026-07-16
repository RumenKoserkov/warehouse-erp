<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO payments
            (
                company_id,
                invoice_id,
                received_by_user_id,
                payment_date,
                amount,
                currency,
                payment_method,
                external_reference,
                note,
                status
            )
            VALUES
            (
                :company_id,
                :invoice_id,
                :received_by_user_id,
                :payment_date,
                :amount,
                :currency,
                :payment_method,
                :external_reference,
                :note,
                'completed'
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' =>
                $data['company_id'],

            'invoice_id' =>
                $data['invoice_id'],

            'received_by_user_id' =>
                $data['received_by_user_id'],

            'payment_date' =>
                $data['payment_date'],

            'amount' =>
                $data['amount'],

            'currency' =>
                $data['currency'],

            'payment_method' =>
                $data['payment_method'],

            'external_reference' =>
                $data['external_reference'],

            'note' =>
                $data['note'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function allByCompany(
        int $companyId,
        string $search = ''
    ): array {
        $sql = "
            SELECT
                payments.*,

                invoices.invoice_number,
                invoices.client_legal_name,

                receiver.name
                    AS received_by_user_name,

                canceller.name
                    AS cancelled_by_user_name

            FROM payments

            INNER JOIN invoices
                ON invoices.id =
                    payments.invoice_id
                AND invoices.company_id =
                    payments.company_id

            LEFT JOIN users AS receiver
                ON receiver.id =
                    payments.received_by_user_id

            LEFT JOIN users AS canceller
                ON canceller.id =
                    payments.cancelled_by_user_id

            WHERE payments.company_id =
                :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        if ($search !== '') {
            $sql .= "
                AND (
                    invoices.invoice_number
                        LIKE :search_invoice

                    OR invoices.client_legal_name
                        LIKE :search_client

                    OR payments.external_reference
                        LIKE :search_reference

                    OR payments.payment_method
                        LIKE :search_method
                )
            ";

            $searchTerm = '%' . $search . '%';

            $parameters['search_invoice'] =
                $searchTerm;

            $parameters['search_client'] =
                $searchTerm;

            $parameters['search_reference'] =
                $searchTerm;

            $parameters['search_method'] =
                $searchTerm;
        }

        $sql .= "
            ORDER BY
                payments.payment_date DESC,
                payments.id DESC
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
                payments.*,

                invoices.invoice_number,
                invoices.invoice_date,

                invoices.status
                    AS invoice_status,

                invoices.client_legal_name,

                invoices.total_amount
                    AS invoice_total_amount,

                receiver.name
                    AS received_by_user_name,

                canceller.name
                    AS cancelled_by_user_name

            FROM payments

            INNER JOIN invoices
                ON invoices.id =
                    payments.invoice_id
                AND invoices.company_id =
                    payments.company_id

            LEFT JOIN users AS receiver
                ON receiver.id =
                    payments.received_by_user_id

            LEFT JOIN users AS canceller
                ON canceller.id =
                    payments.cancelled_by_user_id

            WHERE payments.id = :id
            AND payments.company_id = :company_id

            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $payment = $statement->fetch();

        if ($payment === false) {
            return null;
        }

        return $payment;
    }

    public function findForUpdate(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT *
            FROM payments
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

        $payment = $statement->fetch();

        if ($payment === false) {
            return null;
        }

        return $payment;
    }

    public function allByInvoice(
        int $invoiceId,
        int $companyId
    ): array {
        $sql = "
            SELECT
                payments.*,

                receiver.name
                    AS received_by_user_name,

                canceller.name
                    AS cancelled_by_user_name

            FROM payments

            LEFT JOIN users AS receiver
                ON receiver.id =
                    payments.received_by_user_id

            LEFT JOIN users AS canceller
                ON canceller.id =
                    payments.cancelled_by_user_id

            WHERE payments.invoice_id =
                :invoice_id

            AND payments.company_id =
                :company_id

            ORDER BY
                payments.payment_date DESC,
                payments.id DESC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'invoice_id' => $invoiceId,
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function totalCompletedForInvoice(
        int $invoiceId,
        int $companyId
    ): float {
        $sql = "
            SELECT COALESCE(
                SUM(amount),
                0
            )
            FROM payments
            WHERE invoice_id = :invoice_id
            AND company_id = :company_id
            AND status = 'completed'
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'invoice_id' => $invoiceId,
            'company_id' => $companyId,
        ]);

        return round(
            (float) $statement->fetchColumn(),
            2
        );
    }

    public function hasCompletedForInvoice(
        int $invoiceId,
        int $companyId
    ): bool {
        $sql = "
            SELECT id
            FROM payments
            WHERE invoice_id = :invoice_id
            AND company_id = :company_id
            AND status = 'completed'
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'invoice_id' => $invoiceId,
            'company_id' => $companyId,
        ]);

        return $statement->fetch() !== false;
    }

    public function markAsCancelled(
        int $id,
        int $companyId,
        int $userId,
        string $reason
    ): bool {
        $sql = "
            UPDATE payments
            SET
                status = 'cancelled',
                cancelled_at = NOW(),
                cancelled_by_user_id =
                    :cancelled_by_user_id,
                cancellation_reason =
                    :cancellation_reason,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND status = 'completed'
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
}