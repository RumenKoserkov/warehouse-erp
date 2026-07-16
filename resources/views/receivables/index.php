<?php

$escape = static function (
    mixed $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$money = static function (
    mixed $value
): string {
    return number_format(
        (float) $value,
        2,
        '.',
        ' '
    );
};

$dateText = static function (
    mixed $value
): string {
    if ($value === null) {
        return '—';
    }

    $value = trim(
        (string) $value
    );

    if ($value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return date(
        'd.m.Y',
        $timestamp
    );
};
?>

<div
    class="d-flex flex-column
    flex-lg-row justify-content-between
    align-items-lg-start gap-3 mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Receivables Report
        </h1>

        <p class="text-muted mb-0">
            Outstanding customer balances
            as of
            <strong>
                <?= $escape(
                    $dateText($asOfDate)
                ) ?>
            </strong>.
        </p>
    </div>

    <a
        href="/invoices"
        class="btn btn-outline-secondary"
    >
        Back to Invoices
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach (
                $errors as $error
            ): ?>
                <li>
                    <?= $escape($error) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="alert alert-light border">
    This report contains only outstanding
    issued invoices. Issued credit notes and
    completed payments up to the selected
    report date reduce the outstanding balance.
</div>

<form
    method="GET"
    action="/receivables"
    class="card shadow-sm mb-4"
>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-2 col-md-4">
                <label
                    for="as_of_date"
                    class="form-label"
                >
                    Report Date
                </label>

                <input
                    type="date"
                    id="as_of_date"
                    name="as_of_date"
                    class="form-control"
                    max="<?= date('Y-m-d') ?>"
                    required
                    value="<?= $escape(
                        $asOfDate
                    ) ?>"
                >
            </div>

            <div class="col-lg-3 col-md-8">
                <label
                    for="client_id"
                    class="form-label"
                >
                    Client
                </label>

                <select
                    id="client_id"
                    name="client_id"
                    class="form-select"
                >
                    <option value="0">
                        All Clients
                    </option>

                    <?php foreach (
                        $clients as $client
                    ): ?>
                        <?php
                        $clientLabel = trim(
                            (string) (
                                $client[
                                    'company_name'
                                ] ?? ''
                            )
                        );

                        if (
                            $clientLabel === ''
                        ) {
                            $clientLabel = trim(
                                (string) $client[
                                    'name'
                                ]
                            );
                        }

                        if (
                            (int) $client[
                                'is_active'
                            ] !== 1
                        ) {
                            $clientLabel .=
                                ' (inactive)';
                        }
                        ?>

                        <option
                            value="<?= (int) $client['id'] ?>"
                            <?php if (
                                $clientId ===
                                (int) $client['id']
                            ): ?>
                                selected
                            <?php endif; ?>
                        >
                            <?= $escape(
                                $clientLabel
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <label
                    for="aging_filter"
                    class="form-label"
                >
                    Aging
                </label>

                <select
                    id="aging_filter"
                    name="aging_filter"
                    class="form-select"
                >
                    <?php foreach (
                        $agingFilters as
                        $value => $label
                    ): ?>
                        <option
                            value="<?= $escape(
                                $value
                            ) ?>"
                            <?php if (
                                $agingFilter ===
                                $value
                            ): ?>
                                selected
                            <?php endif; ?>
                        >
                            <?= $escape(
                                $label
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-4 col-md-6">
                <label
                    for="search"
                    class="form-label"
                >
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    maxlength="255"
                    placeholder="Invoice number, client, EIK or VAT number..."
                    value="<?= $escape(
                        $search
                    ) ?>"
                >
            </div>

            <div class="col-12">
                <div
                    class="d-flex flex-wrap
                    gap-2"
                >
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Generate Report
                    </button>

                    <a
                        href="/receivables"
                        class="btn
                        btn-outline-secondary"
                    >
                        Today / Clear Filters
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if (
    empty(
        $summary[
            'currency_summaries'
        ]
    )
): ?>
    <div class="alert alert-info">
        No outstanding receivables were found
        for the selected filters and report date.
    </div>
<?php else: ?>
    <?php foreach (
        $summary[
            'currency_summaries'
        ] as $currencySummary
    ): ?>
        <?php
        $currency =
            (string) $currencySummary[
                'currency'
            ];
        ?>

        <div class="mb-5">
            <div
                class="d-flex justify-content-between
                align-items-center mb-3"
            >
                <h2 class="h4 mb-0">
                    Summary —
                    <?= $escape($currency) ?>
                </h2>

                <span
                    class="badge
                    text-bg-secondary"
                >
                    <?= (int) $currencySummary[
                        'invoice_count'
                    ] ?>
                    open invoice(s)
                </span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div
                        class="card shadow-sm h-100"
                    >
                        <div class="card-body">
                            <div
                                class="text-muted
                                small"
                            >
                                Outstanding
                            </div>

                            <div
                                class="fs-3
                                fw-bold"
                            >
                                <?= $money(
                                    $currencySummary[
                                        'outstanding'
                                    ]
                                ) ?>

                                <?= $escape(
                                    $currency
                                ) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div
                        class="card shadow-sm h-100"
                    >
                        <div class="card-body">
                            <div
                                class="text-muted
                                small"
                            >
                                Overdue
                            </div>

                            <div
                                class="fs-3 fw-bold
                                text-danger"
                            >
                                <?= $money(
                                    $currencySummary[
                                        'overdue_balance'
                                    ]
                                ) ?>

                                <?= $escape(
                                    $currency
                                ) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div
                        class="card shadow-sm h-100"
                    >
                        <div class="card-body">
                            <div
                                class="text-muted
                                small"
                            >
                                Credit Notes Applied
                            </div>

                            <div
                                class="fs-3
                                fw-bold"
                            >
                                <?= $money(
                                    $currencySummary[
                                        'credit_total'
                                    ]
                                ) ?>

                                <?= $escape(
                                    $currency
                                ) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div
                        class="card shadow-sm h-100"
                    >
                        <div class="card-body">
                            <div
                                class="text-muted
                                small"
                            >
                                Payments Applied
                            </div>

                            <div
                                class="fs-3
                                fw-bold"
                            >
                                <?= $money(
                                    $currencySummary[
                                        'paid_amount'
                                    ]
                                ) ?>

                                <?= $escape(
                                    $currency
                                ) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="h5 mb-0">
                        Aging Summary
                    </h3>
                </div>

                <div class="table-responsive">
                    <table
                        class="table
                        align-middle mb-0"
                    >
                        <thead>
                            <tr>
                                <?php foreach (
                                    $agingBucketLabels as
                                    $bucketLabel
                                ): ?>
                                    <th>
                                        <?= $escape(
                                            $bucketLabel
                                        ) ?>
                                    </th>
                                <?php endforeach; ?>

                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <?php foreach (
                                    $agingBucketLabels as
                                    $bucket =>
                                    $bucketLabel
                                ): ?>
                                    <?php
                                    $bucketAmount =
                                        (float)
                                        $currencySummary[
                                            'buckets'
                                        ][$bucket];
                                    ?>

                                    <td>
                                        <strong
                                            class="<?=
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
                                                    ? 'text-danger'
                                                    : ''
                                            ?>"
                                        >
                                            <?= $money(
                                                $bucketAmount
                                            ) ?>

                                            <?= $escape(
                                                $currency
                                            ) ?>
                                        </strong>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <strong>
                                        <?= $money(
                                            $currencySummary[
                                                'outstanding'
                                            ]
                                        ) ?>

                                        <?= $escape(
                                            $currency
                                        ) ?>
                                    </strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">
                Outstanding by Client
            </h2>
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Currency</th>
                        <th>Open Invoices</th>
                        <th>Oldest Due Date</th>
                        <th>Overdue</th>
                        <th>Outstanding</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $summary[
                            'client_summaries'
                        ] as $clientSummary
                    ): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= $escape(
                                        $clientSummary[
                                            'client_name'
                                        ]
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $escape(
                                    $clientSummary[
                                        'currency'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $clientSummary[
                                    'invoice_count'
                                ] ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $dateText(
                                        $clientSummary[
                                            'oldest_due_date'
                                        ]
                                    )
                                ) ?>
                            </td>

                            <td>
                                <span
                                    class="text-danger
                                    fw-semibold"
                                >
                                    <?= $money(
                                        $clientSummary[
                                            'overdue_balance'
                                        ]
                                    ) ?>

                                    <?= $escape(
                                        $clientSummary[
                                            'currency'
                                        ]
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <strong>
                                    <?= $money(
                                        $clientSummary[
                                            'outstanding'
                                        ]
                                    ) ?>

                                    <?= $escape(
                                        $clientSummary[
                                            'currency'
                                        ]
                                    ) ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div
            class="card-header d-flex
            justify-content-between
            align-items-center"
        >
            <h2 class="h5 mb-0">
                Open Invoice Details
            </h2>

            <span class="text-muted">
                <?= (int) $summary[
                    'invoice_count'
                ] ?>
                result(s)
            </span>
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th>Aging</th>
                        <th>Original</th>
                        <th>Credits</th>
                        <th>Paid</th>
                        <th>Outstanding</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $receivables as
                        $receivable
                    ): ?>
                        <?php
                        $bucket =
                            (string) $receivable[
                                'aging_bucket'
                            ];

                        $bucketLabel =
                            $agingBucketLabels[
                                $bucket
                            ] ??
                            'Unknown';

                        $isOverdue =
                            in_array(
                                $bucket,
                                [
                                    '1_30',
                                    '31_60',
                                    '61_90',
                                    '91_plus',
                                ],
                                true
                            );
                        ?>

                        <tr>
                            <td>
                                <a
                                    href="/invoices/show?id=<?= (int) $receivable['id'] ?>"
                                    class="fw-semibold
                                    font-monospace"
                                >
                                    <?= $escape(
                                        $receivable[
                                            'invoice_number'
                                        ]
                                    ) ?>
                                </a>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    <?= $escape(
                                        $receivable[
                                            'client_legal_name'
                                        ]
                                    ) ?>
                                </div>

                                <?php if (
                                    trim(
                                        (string) $receivable[
                                            'client_eik'
                                        ]
                                    ) !== ''
                                ): ?>
                                    <div
                                        class="small
                                        text-muted"
                                    >
                                        EIK:
                                        <?= $escape(
                                            $receivable[
                                                'client_eik'
                                            ]
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $dateText(
                                        $receivable[
                                            'invoice_date'
                                        ]
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $dateText(
                                        $receivable[
                                            'due_date'
                                        ]
                                    )
                                ) ?>
                            </td>

                            <td>
                                <span
                                    class="badge <?=
                                        $isOverdue
                                            ? 'text-bg-danger'
                                            : (
                                                $bucket ===
                                                'current'
                                                    ? 'text-bg-info'
                                                    : 'text-bg-secondary'
                                            )
                                    ?>"
                                >
                                    <?= $escape(
                                        $bucketLabel
                                    ) ?>
                                </span>

                                <?php if (
                                    $isOverdue
                                ): ?>
                                    <div
                                        class="small
                                        text-danger mt-1"
                                    >
                                        <?= (int) $receivable[
                                            'days_overdue'
                                        ] ?>
                                        day(s) overdue
                                    </div>
                                <?php elseif (
                                    $bucket ===
                                    'current' &&
                                    (int) $receivable[
                                        'days_until_due'
                                    ] > 0
                                ): ?>
                                    <div
                                        class="small
                                        text-muted mt-1"
                                    >
                                        <?= (int) $receivable[
                                            'days_until_due'
                                        ] ?>
                                        day(s) remaining
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= $money(
                                    $receivable[
                                        'total_amount'
                                    ]
                                ) ?>

                                <?= $escape(
                                    $receivable[
                                        'currency'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $receivable[
                                        'credit_total'
                                    ]
                                ) ?>

                                <?= $escape(
                                    $receivable[
                                        'currency'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $receivable[
                                        'paid_amount'
                                    ]
                                ) ?>

                                <?= $escape(
                                    $receivable[
                                        'currency'
                                    ]
                                ) ?>

                                <?php if (
                                    trim(
                                        (string) (
                                            $receivable[
                                                'last_payment_date'
                                            ] ?? ''
                                        )
                                    ) !== ''
                                ): ?>
                                    <div
                                        class="small
                                        text-muted"
                                    >
                                        Last:
                                        <?= $escape(
                                            $dateText(
                                                $receivable[
                                                    'last_payment_date'
                                                ]
                                            )
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong
                                    class="<?=
                                        $isOverdue
                                            ? 'text-danger'
                                            : ''
                                    ?>"
                                >
                                    <?= $money(
                                        $receivable[
                                            'balance_due'
                                        ]
                                    ) ?>

                                    <?= $escape(
                                        $receivable[
                                            'currency'
                                        ]
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <a
                                    href="/invoices/show?id=<?= (int) $receivable['id'] ?>"
                                    class="btn btn-sm
                                    btn-outline-primary"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>