<?php
$method =
    (string) $payment['payment_method'];

$methodLabel = ucfirst(
    str_replace('_', ' ', $method)
);

if (isset($methods[$method])) {
    $methodLabel = $methods[$method];
}
?>

<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Payment
            <?= htmlspecialchars(
                $paymentReference,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h1>

        <?php if (
            (string) $payment['status'] ===
            'completed'
        ): ?>
            <span class="badge text-bg-success">
                Completed
            </span>
        <?php else: ?>
            <span class="badge text-bg-danger">
                Cancelled
            </span>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2">
        <a
            href="/invoices/show?id=<?= (int) $payment['invoice_id'] ?>"
            class="btn btn-outline-primary"
        >
            View Invoice
        </a>

        <a
            href="/payments"
            class="btn btn-outline-secondary"
        >
            Back to Payments
        </a>
    </div>
</div>

<?php if (
    (string) $payment['status'] ===
    'cancelled'
): ?>
    <div class="alert alert-danger">
        <h2 class="h5">
            Cancelled Payment
        </h2>

        <div>
            <strong>Reason:</strong>

            <?= htmlspecialchars(
                (string) $payment[
                    'cancellation_reason'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

        <div>
            <strong>Cancelled at:</strong>

            <?= htmlspecialchars(
                (string) $payment[
                    'cancelled_at'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

        <?php if (
            trim(
                (string) $payment[
                    'cancelled_by_user_name'
                ]
            ) !== ''
        ): ?>
            <div>
                <strong>Cancelled by:</strong>

                <?= htmlspecialchars(
                    (string) $payment[
                        'cancelled_by_user_name'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Payment Information</strong>
            </div>

            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">
                        Invoice
                    </dt>

                    <dd class="col-sm-8">
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
                    </dd>

                    <dt class="col-sm-4">
                        Client
                    </dt>

                    <dd class="col-sm-8">
                        <?= htmlspecialchars(
                            (string) $payment[
                                'client_legal_name'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>

                    <dt class="col-sm-4">
                        Payment Date
                    </dt>

                    <dd class="col-sm-8">
                        <?= htmlspecialchars(
                            (string) $payment[
                                'payment_date'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>

                    <dt class="col-sm-4">
                        Amount
                    </dt>

                    <dd class="col-sm-8">
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
                    </dd>

                    <dt class="col-sm-4">
                        Method
                    </dt>

                    <dd class="col-sm-8">
                        <?= htmlspecialchars(
                            $methodLabel,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>

                    <dt class="col-sm-4">
                        External Reference
                    </dt>

                    <dd class="col-sm-8">
                        <?php if (
                            trim(
                                (string) $payment[
                                    'external_reference'
                                ]
                            ) !== ''
                        ): ?>
                            <?= htmlspecialchars(
                                (string) $payment[
                                    'external_reference'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">
                        Received By
                    </dt>

                    <dd class="col-sm-8">
                        <?= htmlspecialchars(
                            (string) $payment[
                                'received_by_user_name'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>

                    <dt class="col-sm-4">
                        Note
                    </dt>

                    <dd class="col-sm-8">
                        <?php if (
                            trim(
                                (string) $payment[
                                    'note'
                                ]
                            ) !== ''
                        ): ?>
                            <?= nl2br(
                                htmlspecialchars(
                                    (string) $payment[
                                        'note'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if ($summary !== null): ?>
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Current Invoice Balance</strong>
                </div>

                <div class="card-body">
                    <div
                        class="d-flex
                        justify-content-between"
                    >
                        <span>Adjusted Total</span>

                        <strong>
                            <?= number_format(
                                (float) $summary[
                                    'adjusted_total'
                                ],
                                2
                            ) ?>
                        </strong>
                    </div>

                    <div
                        class="d-flex
                        justify-content-between"
                    >
                        <span>Active Payments</span>

                        <strong>
                            <?= number_format(
                                (float) $summary[
                                    'paid_amount'
                                ],
                                2
                            ) ?>
                        </strong>
                    </div>

                    <hr>

                    <div
                        class="d-flex
                        justify-content-between fs-5"
                    >
                        <span>Balance Due</span>

                        <strong>
                            <?= number_format(
                                (float) $summary[
                                    'balance_due'
                                ],
                                2
                            ) ?>

                            <?= htmlspecialchars(
                                (string) $summary[
                                    'currency'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (
    (string) $payment['status'] ===
    'completed'
): ?>
    <div class="card border-danger mt-4">
        <div class="card-header text-danger">
            <strong>Cancel Payment</strong>
        </div>

        <div class="card-body">
            <form
                method="POST"
                action="/payments/cancel"
                onsubmit="
                    return confirm(
                        'Cancel this payment?'
                    );
                "
            >
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="payment_id"
                    value="<?= (int) $payment['id'] ?>"
                >

                <label
                    for="cancellation_reason"
                    class="form-label"
                >
                    Cancellation Reason
                </label>

                <textarea
                    id="cancellation_reason"
                    name="cancellation_reason"
                    class="form-control"
                    maxlength="500"
                    rows="3"
                    required
                ></textarea>

                <button
                    type="submit"
                    class="btn btn-danger mt-3"
                >
                    Cancel Payment
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>