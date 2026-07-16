<?php

declare(strict_types=1);

namespace App\Services;

class ReceivablesReportService
{
    public function agingFilters(): array
    {
        return [
            'all' =>
                'All Open Receivables',

            'current' =>
                'Current / Not Yet Due',

            'overdue' =>
                'All Overdue',

            '1_30' =>
                '1–30 Days Overdue',

            '31_60' =>
                '31–60 Days Overdue',

            '61_90' =>
                '61–90 Days Overdue',

            '91_plus' =>
                '91+ Days Overdue',

            'no_due_date' =>
                'No Due Date',
        ];
    }

    public function agingBucketLabels(): array
    {
        return [
            'current' =>
                'Current / Not Yet Due',

            '1_30' =>
                '1–30 Days',

            '31_60' =>
                '31–60 Days',

            '61_90' =>
                '61–90 Days',

            '91_plus' =>
                '91+ Days',

            'no_due_date' =>
                'No Due Date',
        ];
    }

    public function summarize(
        array $receivables
    ): array {
        $currencySummaries = [];
        $clientSummaries = [];

        $bucketLabels =
            $this->agingBucketLabels();

        foreach ($receivables as $row) {
            $currency = trim(
                (string) (
                    $row['currency'] ?? ''
                )
            );

            if ($currency === '') {
                $currency = 'N/A';
            }

            $bucket = (string) (
                $row['aging_bucket'] ??
                'no_due_date'
            );

            if (
                !array_key_exists(
                    $bucket,
                    $bucketLabels
                )
            ) {
                $bucket =
                    'no_due_date';
            }

            if (
                !isset(
                    $currencySummaries[
                        $currency
                    ]
                )
            ) {
                $currencySummaries[
                    $currency
                ] = [
                    'currency' =>
                        $currency,

                    'invoice_count' =>
                        0,

                    'invoice_total' =>
                        0.00,

                    'credit_total' =>
                        0.00,

                    'paid_amount' =>
                        0.00,

                    'outstanding' =>
                        0.00,

                    'current_balance' =>
                        0.00,

                    'overdue_balance' =>
                        0.00,

                    'buckets' => [
                        'current' => 0.00,
                        '1_30' => 0.00,
                        '31_60' => 0.00,
                        '61_90' => 0.00,
                        '91_plus' => 0.00,
                        'no_due_date' => 0.00,
                    ],
                ];
            }

            $balanceDue = round(
                (float) $row[
                    'balance_due'
                ],
                2
            );

            $currencySummaries[
                $currency
            ]['invoice_count']++;

            $currencySummaries[
                $currency
            ]['invoice_total'] +=
                (float) $row[
                    'total_amount'
                ];

            $currencySummaries[
                $currency
            ]['credit_total'] +=
                (float) $row[
                    'credit_total'
                ];

            $currencySummaries[
                $currency
            ]['paid_amount'] +=
                (float) $row[
                    'paid_amount'
                ];

            $currencySummaries[
                $currency
            ]['outstanding'] +=
                $balanceDue;

            $currencySummaries[
                $currency
            ]['buckets'][$bucket] +=
                $balanceDue;

            if ($bucket === 'current') {
                $currencySummaries[
                    $currency
                ]['current_balance'] +=
                    $balanceDue;
            }

            if (
                in_array(
                    $bucket,
                    [
                        '1_30',
                        '31_60',
                        '61_90',
                        '91_plus',
                    ],
                    true
                )
            ) {
                $currencySummaries[
                    $currency
                ]['overdue_balance'] +=
                    $balanceDue;
            }

            $clientId = (int) (
                $row['client_id'] ?? 0
            );

            $clientKey =
                $clientId .
                '|' .
                $currency;

            if (
                !isset(
                    $clientSummaries[
                        $clientKey
                    ]
                )
            ) {
                $clientName = trim(
                    (string) (
                        $row[
                            'client_legal_name'
                        ] ?? ''
                    )
                );

                if ($clientName === '') {
                    $clientName = trim(
                        (string) (
                            $row[
                                'client_display_name'
                            ] ?? ''
                        )
                    );
                }

                if ($clientName === '') {
                    $clientName =
                        'Unknown Client';
                }

                $clientSummaries[
                    $clientKey
                ] = [
                    'client_id' =>
                        $clientId,

                    'client_name' =>
                        $clientName,

                    'currency' =>
                        $currency,

                    'invoice_count' =>
                        0,

                    'outstanding' =>
                        0.00,

                    'overdue_balance' =>
                        0.00,

                    'oldest_due_date' =>
                        null,
                ];
            }

            $clientSummaries[
                $clientKey
            ]['invoice_count']++;

            $clientSummaries[
                $clientKey
            ]['outstanding'] +=
                $balanceDue;

            if (
                in_array(
                    $bucket,
                    [
                        '1_30',
                        '31_60',
                        '61_90',
                        '91_plus',
                    ],
                    true
                )
            ) {
                $clientSummaries[
                    $clientKey
                ]['overdue_balance'] +=
                    $balanceDue;
            }

            $dueDate = trim(
                (string) (
                    $row['due_date'] ?? ''
                )
            );

            if ($dueDate !== '') {
                $currentOldest =
                    $clientSummaries[
                        $clientKey
                    ]['oldest_due_date'];

                if (
                    $currentOldest === null ||
                    $dueDate < $currentOldest
                ) {
                    $clientSummaries[
                        $clientKey
                    ]['oldest_due_date'] =
                        $dueDate;
                }
            }
        }

        foreach (
            $currencySummaries as
            &$currencySummary
        ) {
            $this->roundSummary(
                $currencySummary
            );

            foreach (
                $currencySummary[
                    'buckets'
                ] as &$bucketAmount
            ) {
                $bucketAmount = round(
                    (float) $bucketAmount,
                    2
                );
            }

            unset($bucketAmount);
        }

        unset($currencySummary);

        foreach (
            $clientSummaries as
            &$clientSummary
        ) {
            $clientSummary[
                'outstanding'
            ] = round(
                (float) $clientSummary[
                    'outstanding'
                ],
                2
            );

            $clientSummary[
                'overdue_balance'
            ] = round(
                (float) $clientSummary[
                    'overdue_balance'
                ],
                2
            );
        }

        unset($clientSummary);

        ksort($currencySummaries);

        $clientSummaries =
            array_values(
                $clientSummaries
            );

        usort(
            $clientSummaries,
            static function (
                array $first,
                array $second
            ): int {
                $currencyComparison =
                    strcmp(
                        (string) $first[
                            'currency'
                        ],
                        (string) $second[
                            'currency'
                        ]
                    );

                if (
                    $currencyComparison !== 0
                ) {
                    return $currencyComparison;
                }

                $balanceComparison =
                    (float) $second[
                        'outstanding'
                    ]
                    <=>
                    (float) $first[
                        'outstanding'
                    ];

                if (
                    $balanceComparison !== 0
                ) {
                    return $balanceComparison;
                }

                return strcmp(
                    (string) $first[
                        'client_name'
                    ],
                    (string) $second[
                        'client_name'
                    ]
                );
            }
        );

        return [
            'currency_summaries' =>
                $currencySummaries,

            'client_summaries' =>
                $clientSummaries,

            'invoice_count' =>
                count($receivables),
        ];
    }

    private function roundSummary(
        array &$summary
    ): void {
        $fields = [
            'invoice_total',
            'credit_total',
            'paid_amount',
            'outstanding',
            'current_balance',
            'overdue_balance',
        ];

        foreach ($fields as $field) {
            $summary[$field] = round(
                (float) $summary[$field],
                2
            );
        }
    }
}