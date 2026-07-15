<?php
$invoiceNumber =
    (string) $originalInvoice['invoice_number'];
?>

<div class="row justify-content-center">
    <div class="col-xl-11">
        <div
            class="d-flex justify-content-between
            align-items-start mb-4">
            <div>
                <h1 class="h3 mb-1">
                    Create Credit Note
                </h1>

                <p class="text-muted mb-0">
                    Original invoice:
                    <strong>
                        <?= htmlspecialchars(
                            $invoiceNumber,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </p>
            </div>

            <a
                href="/invoices/show?id=<?= (int) $originalInvoice['id'] ?>"
                class="btn btn-outline-secondary">
                Back to Invoice
            </a>
        </div>

        <div class="alert alert-info">
            Enter the quantities that must be credited.
            The values, VAT rates and billing snapshots
            will be copied from the original invoice.

            This operation does not change warehouse stock.
        </div>

        <form
            method="POST"
            action="/invoices/credit-note/store">
            <?= \App\Core\Csrf::field() ?>

            <input
                type="hidden"
                name="invoice_id"
                value="<?= (int) $originalInvoice['id'] ?>">

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <label
                        for="reason"
                        class="form-label">
                        Credit Note Reason
                    </label>

                    <textarea
                        id="reason"
                        name="reason"
                        class="form-control"
                        maxlength="500"
                        rows="3"
                        required
                        placeholder="Example: Returned goods, price reduction, cancelled delivery..."></textarea>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div
                    class="card-header d-flex
                    justify-content-between
                    align-items-center">
                    <h2 class="h5 mb-0">
                        Items
                    </h2>

                    <button
                        type="button"
                        id="credit-all-remaining"
                        class="btn btn-sm
                        btn-outline-primary">
                        Credit All Remaining
                    </button>
                </div>

                <div class="table-responsive">
                    <table
                        class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Original Qty</th>
                                <th>Already Credited</th>
                                <th>Remaining</th>
                                <th>Unit Price</th>
                                <th style="width: 170px;">
                                    Credit Qty
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(
                                                (string) $item['description'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>

                                        <?php if (
                                            trim(
                                                (string) $item['product_internal_code']
                                            ) !== ''
                                        ): ?>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars(
                                                    (string) $item['product_internal_code'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $item['quantity'],
                                            3
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $item['credited_quantity'],
                                            3
                                        ) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format(
                                                (float) $item['remaining_quantity'],
                                                3
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $item['unit_price'],
                                            2
                                        ) ?>

                                        <?= htmlspecialchars(
                                            (string) $originalInvoice['currency'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?php if (
                                            (float) $item['remaining_quantity'] > 0
                                        ): ?>
                                            <input
                                                type="number"
                                                name="credit_quantity[<?= (int) $item['id'] ?>]"
                                                class="form-control credit-quantity"
                                                min="0"
                                                max="<?= htmlspecialchars(
                                                            (string) $item['remaining_quantity'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                step="0.001"
                                                value="0"
                                                data-remaining="<?= htmlspecialchars(
                                                                    (string) $item['remaining_quantity'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">
                                        <?php else: ?>
                                            <span
                                                class="badge
                                                text-bg-secondary">
                                                Fully credited
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <button
                type="submit"
                class="btn btn-primary">
                Create Credit Note Draft
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function() {
            const button =
                document.getElementById(
                    'credit-all-remaining'
                );

            if (button === null) {
                return;
            }

            button.addEventListener(
                'click',
                function() {
                    const inputs =
                        document.querySelectorAll(
                            '.credit-quantity'
                        );

                    inputs.forEach(
                        function(input) {
                            input.value =
                                input.dataset.remaining;
                        }
                    );
                }
            );
        }
    );
</script>