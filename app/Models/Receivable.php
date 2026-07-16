<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Receivable extends Model
{
    public function reportByCompany(
        int $companyId,
        string $asOfDate,
        string $search = '',
        int $clientId = 0,
        string $agingFilter = 'all'
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

        $adjustedTotalExpression = "
            GREATEST(
                invoices.total_amount -
                {$creditExpression},
                0
            )
        ";

        $balanceExpression = "
            GREATEST(
                {$adjustedTotalExpression} -
                {$paidExpression},
                0
            )
        ";

        $agingExpression = "
            CASE
                WHEN invoices.due_date IS NULL
                    THEN 'no_due_date'

                WHEN invoices.due_date >=
                    report_dates.as_of_date
                    THEN 'current'

                WHEN DATEDIFF(
                    report_dates.as_of_date,
                    invoices.due_date
                ) BETWEEN 1 AND 30
                    THEN '1_30'

                WHEN DATEDIFF(
                    report_dates.as_of_date,
                    invoices.due_date
                ) BETWEEN 31 AND 60
                    THEN '31_60'

                WHEN DATEDIFF(
                    report_dates.as_of_date,
                    invoices.due_date
                ) BETWEEN 61 AND 90
                    THEN '61_90'

                ELSE '91_plus'
            END
        ";

        $sql = "
            SELECT
                invoices.id,
                invoices.client_id,
                invoices.invoice_number,
                invoices.invoice_date,
                invoices.due_date,
                invoices.currency,

                invoices.client_display_name,
                invoices.client_legal_name,
                invoices.client_eik,
                invoices.client_vat_number,

                invoices.total_amount,

                {$creditExpression}
                    AS credit_total,

                {$adjustedTotalExpression}
                    AS adjusted_total,

                {$paidExpression}
                    AS paid_amount,

                {$balanceExpression}
                    AS balance_due,

                payment_totals.last_payment_date,

                CASE
                    WHEN invoices.due_date IS NULL
                        THEN 0

                    WHEN invoices.due_date >=
                        report_dates.as_of_date
                        THEN 0

                    ELSE DATEDIFF(
                        report_dates.as_of_date,
                        invoices.due_date
                    )
                END AS days_overdue,

                CASE
                    WHEN invoices.due_date IS NULL
                        THEN 0

                    WHEN invoices.due_date <=
                        report_dates.as_of_date
                        THEN 0

                    ELSE DATEDIFF(
                        invoices.due_date,
                        report_dates.as_of_date
                    )
                END AS days_until_due,

                {$agingExpression}
                    AS aging_bucket

            FROM invoices

            CROSS JOIN (
                SELECT
                    CAST(
                        :report_as_of_date
                        AS DATE
                    ) AS as_of_date
            ) AS report_dates

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

                AND invoice_date <=
                    :credit_as_of_date

                AND (
                    status = 'issued'

                    OR (
                        status = 'cancelled'

                        AND cancelled_at
                            IS NOT NULL

                        AND DATE(cancelled_at) >
                            :credit_cancel_as_of_date
                    )
                )

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
                        AS paid_amount,

                    MAX(payment_date)
                        AS last_payment_date

                FROM payments

                WHERE payment_date <=
                    :payment_as_of_date

                AND (
                    status = 'completed'

                    OR (
                        status = 'cancelled'

                        AND cancelled_at
                            IS NOT NULL

                        AND DATE(cancelled_at) >
                            :payment_cancel_as_of_date
                    )
                )

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

            AND invoices.document_type =
                'invoice'

            AND invoices.invoice_date <=
                :invoice_as_of_date

            AND (
                invoices.status = 'issued'

                OR (
                    invoices.status =
                        'cancelled'

                    AND invoices.cancelled_at
                        IS NOT NULL

                    AND DATE(
                        invoices.cancelled_at
                    ) >
                        :invoice_cancel_as_of_date
                )
            )
        ";

        $parameters = [
            'report_as_of_date' =>
                $asOfDate,

            'credit_as_of_date' =>
                $asOfDate,

            'credit_cancel_as_of_date' =>
                $asOfDate,

            'payment_as_of_date' =>
                $asOfDate,

            'payment_cancel_as_of_date' =>
                $asOfDate,

            'company_id' =>
                $companyId,

            'invoice_as_of_date' =>
                $asOfDate,

            'invoice_cancel_as_of_date' =>
                $asOfDate,
        ];

        if ($clientId > 0) {
            $sql .= "
                AND invoices.client_id =
                    :client_id
            ";

            $parameters['client_id'] =
                $clientId;
        }

        if ($search !== '') {
            $searchTerm =
                '%' . $search . '%';

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

            $parameters[
                'search_display_name'
            ] = $searchTerm;

            $parameters[
                'search_legal_name'
            ] = $searchTerm;

            $parameters['search_eik'] =
                $searchTerm;

            $parameters['search_vat'] =
                $searchTerm;
        }

        $havingConditions = [
            'balance_due > 0.009',
        ];

        if ($agingFilter === 'overdue') {
            $havingConditions[] = "
                aging_bucket IN (
                    '1_30',
                    '31_60',
                    '61_90',
                    '91_plus'
                )
            ";
        } elseif (
            in_array(
                $agingFilter,
                [
                    'current',
                    '1_30',
                    '31_60',
                    '61_90',
                    '91_plus',
                    'no_due_date',
                ],
                true
            )
        ) {
            $havingConditions[] = "
                aging_bucket =
                    :aging_bucket
            ";

            $parameters['aging_bucket'] =
                $agingFilter;
        }

        $sql .= "
            HAVING " .
            implode(
                ' AND ',
                $havingConditions
            );

        $sql .= "
            ORDER BY
                CASE
                    WHEN due_date IS NULL
                        THEN 1
                    ELSE 0
                END ASC,

                due_date ASC,
                invoice_date ASC,
                invoices.id ASC
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function clientsByCompany(
        int $companyId
    ): array {
        $sql = "
            SELECT
                id,
                name,
                company_name,
                client_type,
                is_active
            FROM clients
            WHERE company_id = :company_id
            ORDER BY
                CASE
                    WHEN company_name IS NULL
                        OR company_name = ''
                    THEN name
                    ELSE company_name
                END ASC
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }
}