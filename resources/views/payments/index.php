<div
    class="d-flex justify-content-between
    align-items-center mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Payments
        </h1>

        <p class="text-muted mb-0">
            Recorded invoice payments.
        </p>
    </div>
</div>

<form
    method="GET"
    action="/payments"
    class="row g-2 mb-4"
>
    <div class="col-md-10">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by invoice, client, method or external reference..."
            value="<?= htmlspecialchars(
                $search,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    </div>

    <div class="col-md-2 d-grid">
        <button
            type="submit"
            class="btn btn-outline-primary"
        >
            Search
        </button>
    </div>
</form>

<?php if (empty($payments)): ?>
    <div class="alert alert-info">
        No payments found.
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Received By</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $payments as $payment
                    ): ?>
                        <?php
                        $reference =
                            'PAY-' .
                            str_pad(
                                (string) $payment[
                                    'id'
                                ],
                                8,
                                '0',
                                STR_PAD_LEFT
                            );

                        $method =
                            (string) $payment[
                                'payment_method'
                            ];

                        $methodLabel =
                            ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $method
                                )
                            );

                        if (
                            isset($methods[$method])
                        ) {
                            $methodLabel =
                                $methods[$method];
                        }
                        ?>

                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        $reference,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $payment[
                                        'payment_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/invoices/show?id=<?= (int) $payment['invoice_id'] ?>"
                                >
                                    <?= htmlspecialchars(
                                        (string) $payment[
                                            'invoice_number'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </a>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $payment[
                                        'client_legal_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $methodLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= number_format(
                                        (float) $payment[
                                            'amount'
                                        ],
                                        2
                                    ) ?>

                                    <?= htmlspecialchars(
                                        (string) $payment[
                                            'currency'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?php if (
                                    (string) $payment[
                                        'status'
                                    ] === 'completed'
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-success"
                                    >
                                        Completed
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge
                                        text-bg-danger"
                                    >
                                        Cancelled
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $payment[
                                        'received_by_user_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/payments/show?id=<?= (int) $payment['id'] ?>"
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