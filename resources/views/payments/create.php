<div class="row justify-content-center">
    <div class="col-xl-8">
        <div
            class="d-flex justify-content-between
            align-items-start mb-4"
        >
            <div>
                <h1 class="h3 mb-1">
                    Record Full Payment
                </h1>

                <p class="text-muted mb-0">
                    Invoice
                    <?= htmlspecialchars(
                        (string) $invoice[
                            'invoice_number'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            </div>

            <a
                href="/invoices/show?id=<?= (int) $invoice['id'] ?>"
                class="btn btn-outline-secondary"
            >
                Back to Invoice
            </a>
        </div>

        <div class="alert alert-info">
            This step records the full outstanding
            balance. Partial payments will be added
            in the next step.
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="text-muted">
                            Client
                        </span>

                        <div class="fw-semibold">
                            <?= htmlspecialchars(
                                (string) $invoice[
                                    'client_legal_name'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <span class="text-muted">
                            Invoice Total
                        </span>

                        <div class="fw-semibold">
                            <?= number_format(
                                (float) $summary[
                                    'invoice_total'
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
                        </div>
                    </div>

                    <div class="col-md-3">
                        <span class="text-muted">
                            Credit Notes
                        </span>

                        <div class="fw-semibold">
                            <?= number_format(
                                (float) $summary[
                                    'credit_total'
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
                        </div>
                    </div>

                    <div class="col-md-4">
                        <span class="text-muted">
                            Adjusted Total
                        </span>

                        <div class="fw-semibold">
                            <?= number_format(
                                (float) $summary[
                                    'adjusted_total'
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
                        </div>
                    </div>

                    <div class="col-md-4">
                        <span class="text-muted">
                            Already Paid
                        </span>

                        <div class="fw-semibold">
                            <?= number_format(
                                (float) $summary[
                                    'paid_amount'
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
                        </div>
                    </div>

                    <div class="col-md-4">
                        <span class="text-muted">
                            Payment Amount
                        </span>

                        <div class="fs-4 fw-bold text-primary">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form
            method="POST"
            action="/payments/store"
        >
            <?= \App\Core\Csrf::field() ?>

            <input
                type="hidden"
                name="invoice_id"
                value="<?= (int) $invoice['id'] ?>"
            >

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label
                                for="payment_date"
                                class="form-label"
                            >
                                Payment Date
                            </label>

                            <input
                                type="date"
                                id="payment_date"
                                name="payment_date"
                                class="form-control"
                                max="<?= date('Y-m-d') ?>"
                                required
                                value="<?= htmlspecialchars(
                                    (string) $old[
                                        'payment_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-6">
                            <label
                                for="payment_method"
                                class="form-label"
                            >
                                Payment Method
                            </label>

                            <select
                                id="payment_method"
                                name="payment_method"
                                class="form-select"
                                required
                            >
                                <?php foreach (
                                    $methods as
                                    $value => $label
                                ): ?>
                                    <option
                                        value="<?= htmlspecialchars(
                                            $value,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?php if (
                                            (string) $old[
                                                'payment_method'
                                            ] === $value
                                        ): ?>
                                            selected
                                        <?php endif; ?>
                                    >
                                        <?= htmlspecialchars(
                                            $label,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label
                                for="external_reference"
                                class="form-label"
                            >
                                External Reference
                            </label>

                            <input
                                type="text"
                                id="external_reference"
                                name="external_reference"
                                class="form-control"
                                maxlength="100"
                                placeholder="Bank transaction, receipt or terminal reference..."
                                value="<?= htmlspecialchars(
                                    (string) $old[
                                        'external_reference'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-12">
                            <label
                                for="note"
                                class="form-label"
                            >
                                Note
                            </label>

                            <textarea
                                id="note"
                                name="note"
                                class="form-control"
                                rows="3"
                            ><?= htmlspecialchars(
                                (string) $old['note'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-success mt-4"
                    >
                        Record Full Payment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>