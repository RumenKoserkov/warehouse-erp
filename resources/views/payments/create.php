<div class="row justify-content-center">
    <div class="col-xl-8">
        <div
            class="d-flex justify-content-between
            align-items-start mb-4">
            <div>
                <h1 class="h3 mb-1">
                    Record Payment
                </h1>

                <p class="text-muted mb-0">
                    Invoice
                    <?= htmlspecialchars(
                        (string) $invoice['invoice_number'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            </div>

            <a
                href="/invoices/show?id=<?= (int) $invoice['id'] ?>"
                class="btn btn-outline-secondary">
                Back to Invoice
            </a>
        </div>

        <div class="alert alert-info">
            Enter any amount up to the outstanding
            balance. You can record additional
            payments later until the invoice is
            fully paid.
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
                                (string) $invoice['client_legal_name'],
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
                                (float) $summary['invoice_total'],
                                2
                            ) ?>

                            <?= htmlspecialchars(
                                (string) $summary['currency'],
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
                                (float) $summary['credit_total'],
                                2
                            ) ?>

                            <?= htmlspecialchars(
                                (string) $summary['currency'],
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
                                (float) $summary['adjusted_total'],
                                2
                            ) ?>

                            <?= htmlspecialchars(
                                (string) $summary['currency'],
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
                                (float) $summary['paid_amount'],
                                2
                            ) ?>

                            <?= htmlspecialchars(
                                (string) $summary['currency'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <span class="text-muted">
                            Outstanding Balance
                        </span>

                        <div class="fs-4 fw-bold text-primary">
                            <?= number_format(
                                (float) $summary['balance_due'],
                                2
                            ) ?>

                            <?= htmlspecialchars(
                                (string) $summary['currency'],
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
            action="/payments/store">
            <?= \App\Core\Csrf::field() ?>

            <input
                type="hidden"
                name="invoice_id"
                value="<?= (int) $invoice['id'] ?>">

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label
                                for="payment_date"
                                class="form-label">
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
                                            (string) $old['payment_date'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">
                        </div>

                        <div class="col-md-4">
                            <label
                                for="payment_method"
                                class="form-label">
                                Payment Method
                            </label>

                            <select
                                id="payment_method"
                                name="payment_method"
                                class="form-select"
                                required>
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
                                            (string) $old['payment_method'] === $value
                                        ): ?>
                                        selected
                                        <?php endif; ?>>
                                        <?= htmlspecialchars(
                                            $label,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label
                                for="amount"
                                class="form-label">
                                Payment Amount
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="amount"
                                    name="amount"
                                    class="form-control"
                                    min="0.01"
                                    max="<?= htmlspecialchars(
                                                number_format(
                                                    (float) $summary['balance_due'],
                                                    2,
                                                    '.',
                                                    ''
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                    step="0.01"
                                    required
                                    value="<?= htmlspecialchars(
                                                (string) $old['amount'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                <span class="input-group-text">
                                    <?= htmlspecialchars(
                                        (string) $summary['currency'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </div>

                            <button
                                type="button"
                                id="use-full-balance"
                                class="btn btn-sm
                                btn-link px-0">
                                Use Full Balance
                            </button>
                        </div>

                        <div class="col-12">
                            <label
                                for="external_reference"
                                class="form-label">
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
                                            (string) $old['external_reference'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">
                        </div>

                        <div class="col-12">
                            <label
                                for="note"
                                class="form-label">
                                Note
                            </label>

                            <textarea
                                id="note"
                                name="note"
                                class="form-control"
                                rows="3"><?= htmlspecialchars(
                                                (string) $old['note'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?></textarea>
                        </div>
                    </div>

                    <div class="card bg-light mt-4">
                        <div class="card-body">
                            <div
                                class="d-flex
                                justify-content-between">
                                <span>
                                    Current Outstanding Balance
                                </span>

                                <strong>
                                    <?= number_format(
                                        (float) $summary['balance_due'],
                                        2
                                    ) ?>

                                    <?= htmlspecialchars(
                                        (string) $summary['currency'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>

                            <div
                                class="d-flex
                                justify-content-between mt-2">
                                <span>
                                    Balance After This Payment
                                </span>

                                <strong id="remaining-preview">
                                    <?= number_format(
                                        max(
                                            0,
                                            (float) $summary['balance_due'] -
                                                (float) $old['amount']
                                        ),
                                        2
                                    ) ?>

                                    <?= htmlspecialchars(
                                        (string) $summary['currency'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-success mt-4">
                        Record Payment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function() {
            const amountInput =
                document.getElementById(
                    'amount'
                );

            const fullBalanceButton =
                document.getElementById(
                    'use-full-balance'
                );

            const remainingPreview =
                document.getElementById(
                    'remaining-preview'
                );

            if (
                amountInput === null ||
                fullBalanceButton === null ||
                remainingPreview === null
            ) {
                return;
            }

            const balanceDue =
                <?= json_encode(
                    round(
                        (float) $summary['balance_due'],
                        2
                    )
                ) ?>;

            const currency =
                <?= json_encode(
                    (string) $summary['currency'],
                    JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                ) ?>;

            function updateRemainingPreview() {
                let paymentAmount =
                    Number(amountInput.value);

                if (
                    !Number.isFinite(
                        paymentAmount
                    ) ||
                    paymentAmount < 0
                ) {
                    paymentAmount = 0;
                }

                let remaining =
                    balanceDue - paymentAmount;

                if (remaining < 0) {
                    remaining = 0;
                }

                remainingPreview.textContent =
                    remaining.toFixed(2) +
                    ' ' +
                    currency;
            }

            fullBalanceButton.addEventListener(
                'click',
                function() {
                    amountInput.value =
                        balanceDue.toFixed(2);

                    updateRemainingPreview();

                    amountInput.focus();
                }
            );

            amountInput.addEventListener(
                'input',
                updateRemainingPreview
            );

            updateRemainingPreview();
        }
    );
</script>