<?php
$isCreditNote =
    (string) $invoice['document_type'] === 'credit_note';

$isCancelled =
    (string) $invoice['status'] === 'cancelled';

$documentLabel = 'Invoice';

if ($isCreditNote) {
    $documentLabel = 'Credit Note';
}

$reference =
    'DRAFT-' . (int) $invoice['id'];

if (
    isset($invoice['invoice_number']) &&
    trim(
        (string) $invoice['invoice_number']
    ) !== ''
) {
    $reference =
        (string) $invoice['invoice_number'];
}
?>

<div
    class="d-flex justify-content-between
    align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">
            <?= htmlspecialchars(
                $documentLabel,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

            <?= htmlspecialchars(
                $reference,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h1>

        <?php if ($isCancelled): ?>
            <span class="badge text-bg-danger">
                Cancelled
            </span>
        <?php elseif (
            (string) $invoice['status'] ===
            'issued'
        ): ?>
            <span class="badge text-bg-success">
                Issued
            </span>
        <?php else: ?>
            <span class="badge text-bg-warning">
                Draft
            </span>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <?php if (
            !$isCreditNote &&
            (string) $invoice['status'] ===
            'issued'
        ): ?>
            <a
                href="/invoices/credit-note/create?invoice_id=<?= (int) $invoice['id'] ?>"
                class="btn btn-outline-primary">
                Create Credit Note
            </a>
        <?php endif; ?>

        <?php if (
            (string) $invoice['status'] ===
            'draft'
        ): ?>
            <form
                method="POST"
                action="/invoices/issue"
                onsubmit="
                    return confirm(
                        'Issue this document? ' +
                        'An official number will be assigned.'
                    );
                ">
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="invoice_id"
                    value="<?= (int) $invoice['id'] ?>">

                <button
                    type="submit"
                    class="btn btn-success">
                    <?php if ($isCreditNote): ?>
                        Issue Credit Note
                    <?php else: ?>
                        Issue Invoice
                    <?php endif; ?>
                </button>
            </form>
        <?php endif; ?>

        <a
            href="/invoices/print?id=<?= (int) $invoice['id'] ?>"
            target="_blank"
            rel="noopener"
            class="btn btn-outline-dark">
            Print Preview
        </a>

        <a
            href="/invoices/pdf?id=<?= (int) $invoice['id'] ?>"
            class="btn btn-outline-primary">
            Download PDF
        </a>

        <a
            href="/invoices"
            class="btn btn-outline-secondary">
            Back to Invoices
        </a>
    </div>
</div>

<?php if (
    (string) $invoice['status'] ===
    'draft'
): ?>
    <div class="alert alert-warning">
        This document is still a draft.
        Review all supplier, client, item and tax
        information before issuing it.

        Once issued, it receives a permanent official
        number and must not be edited freely.
    </div>
<?php endif; ?>

<?php if (
    (string) $invoice['status'] ===
    'issued'
): ?>
    <div class="alert alert-success">
        <div>
            <strong>Official document number:</strong>

            <span class="font-monospace">
                <?= htmlspecialchars(
                    (string) $invoice['invoice_number'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </div>

        <?php if (
            isset($invoice['issued_at']) &&
            $invoice['issued_at'] !== null
        ): ?>
            <div>
                <strong>Issued at:</strong>

                <?= htmlspecialchars(
                    (string) $invoice['issued_at'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>

        <?php if (
            isset(
                $invoice['issued_by_user_name']
            ) &&
            trim(
                (string) $invoice['issued_by_user_name']
            ) !== ''
        ): ?>
            <div>
                <strong>Issued by:</strong>

                <?= htmlspecialchars(
                    (string) $invoice['issued_by_user_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isCancelled): ?>
    <div class="alert alert-danger">
        <h2 class="h5">
            Cancelled Document
        </h2>

        <?php if (
            trim(
                (string) $invoice['invoice_number']
            ) !== ''
        ): ?>
            <p class="mb-1">
                The official number
                <strong>
                    <?= htmlspecialchars(
                        (string) $invoice['invoice_number'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
                remains permanently used.
            </p>
        <?php endif; ?>

        <p class="mb-1">
            <strong>Reason:</strong>

            <?= htmlspecialchars(
                (string) $invoice['cancellation_reason'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <p class="mb-1">
            <strong>Cancelled at:</strong>

            <?= htmlspecialchars(
                (string) $invoice['cancelled_at'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <?php if (
            trim(
                (string) $invoice['cancelled_by_user_name']
            ) !== ''
        ): ?>
            <p class="mb-0">
                <strong>Cancelled by:</strong>

                <?= htmlspecialchars(
                    (string) $invoice['cancelled_by_user_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isCreditNote): ?>
    <div class="alert alert-light border">
        <div>
            <strong>
                Original invoice:
            </strong>

            <a
                href="/invoices/show?id=<?= (int) $invoice['related_invoice_id'] ?>">
                <?= htmlspecialchars(
                    (string) $invoice['related_invoice_number'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </a>
        </div>

        <div>
            <strong>
                Original invoice date:
            </strong>

            <?= htmlspecialchars(
                (string) $invoice['related_invoice_date'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

        <div>
            <strong>Reason:</strong>

            <?= htmlspecialchars(
                (string) $invoice['correction_reason'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>
    </div>
<?php endif; ?>

<?php if (
    isset($invoice['sale_id']) &&
    (int) $invoice['sale_id'] > 0
): ?>
    <div class="alert alert-light border">
        Generated from sale:

        <a
            href="/sales/show?id=<?= (int) $invoice['sale_id'] ?>"
            class="fw-semibold">
            <?php if (
                isset(
                    $invoice['source_sale_number']
                ) &&
                trim(
                    (string) $invoice['source_sale_number']
                ) !== ''
            ): ?>
                <?= htmlspecialchars(
                    (string) $invoice['source_sale_number'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            <?php else: ?>
                Sale #<?= (int) $invoice['sale_id'] ?>
            <?php endif; ?>
        </a>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Supplier</strong>
            </div>

            <div class="card-body">
                <h2 class="h5">
                    <?= htmlspecialchars(
                        (string) $invoice['supplier_legal_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <div>
                    EIK:
                    <?= htmlspecialchars(
                        (string) $invoice['supplier_eik'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

                <?php if (
                    trim(
                        (string) $invoice['supplier_vat_number']
                    ) !== ''
                ): ?>
                    <div>
                        VAT:
                        <?= htmlspecialchars(
                            (string) $invoice['supplier_vat_number'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                <?php endif; ?>

                <div class="mt-2">
                    <?= htmlspecialchars(
                        (string) $invoice['supplier_address'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>,
                    <?= htmlspecialchars(
                        (string) $invoice['supplier_city'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>,
                    <?= htmlspecialchars(
                        (string) $invoice['supplier_country'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Client</strong>
            </div>

            <div class="card-body">
                <h2 class="h5">
                    <?= htmlspecialchars(
                        (string) $invoice['client_legal_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <?php if (
                    trim(
                        (string) $invoice['client_eik']
                    ) !== ''
                ): ?>
                    <div>
                        EIK:
                        <?= htmlspecialchars(
                            (string) $invoice['client_eik'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                <?php endif; ?>

                <?php if (
                    trim(
                        (string) $invoice['client_vat_number']
                    ) !== ''
                ): ?>
                    <div>
                        VAT:
                        <?= htmlspecialchars(
                            (string) $invoice['client_vat_number'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                <?php endif; ?>

                <div class="mt-2">
                    <?= htmlspecialchars(
                        (string) $invoice['client_address'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>,
                    <?= htmlspecialchars(
                        (string) $invoice['client_city'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>,
                    <?= htmlspecialchars(
                        (string) $invoice['client_country'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <strong>Document date:</strong>

                <?= htmlspecialchars(
                    (string) $invoice['invoice_date'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <div class="col-md-4">
                <strong>Supply date:</strong>

                <?= htmlspecialchars(
                    (string) $invoice['supply_date'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <div class="col-md-4">
                <strong>Due date:</strong>

                <?php if (
                    trim(
                        (string) $invoice['due_date']
                    ) !== ''
                ): ?>
                    <?= htmlspecialchars(
                        (string) $invoice['due_date'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
                    <th>Net</th>
                    <th>VAT %</th>
                    <th>VAT</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(
                                (string) $item['description'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                (float) $item['quantity'],
                                3
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                (string) $item['unit'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                (float) $item['unit_price'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                (float) $item['discount_amount'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                (float) $item['net_amount'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                (float) $item['vat_rate'],
                                2
                            ) ?>%
                        </td>

                        <td>
                            <?= number_format(
                                (float) $item['tax_amount'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= number_format(
                                    (float) $item['total_amount'],
                                    2
                                ) ?>
                            </strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row justify-content-end">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div
                    class="d-flex
                    justify-content-between">
                    <span>Subtotal</span>

                    <strong>
                        <?= number_format(
                            (float) $invoice['subtotal'],
                            2
                        ) ?>

                        <?= htmlspecialchars(
                            (string) $invoice['currency'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

                <div
                    class="d-flex
                    justify-content-between">
                    <span>Discount</span>

                    <strong>
                        <?= number_format(
                            (float) $invoice['discount_amount'],
                            2
                        ) ?>
                    </strong>
                </div>

                <div
                    class="d-flex
                    justify-content-between">
                    <span>VAT</span>

                    <strong>
                        <?= number_format(
                            (float) $invoice['tax_amount'],
                            2
                        ) ?>
                    </strong>
                </div>

                <hr>

                <div
                    class="d-flex
                    justify-content-between fs-5">
                    <span>Total</span>

                    <strong>
                        <?= number_format(
                            (float) $invoice['total_amount'],
                            2
                        ) ?>

                        <?= htmlspecialchars(
                            (string) $invoice['currency'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (
    isset($invoice['note']) &&
    trim(
        (string) $invoice['note']
    ) !== ''
): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <strong>Note</strong>

            <p class="mb-0 mt-2">
                <?= nl2br(
                    htmlspecialchars(
                        (string) $invoice['note'],
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ) ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<?php if (
    (string) $invoice['status'] ===
    'draft' ||
    (string) $invoice['status'] ===
    'issued'
): ?>
    <div class="card border-danger mt-4">
        <div class="card-header text-danger">
            <strong>
                <?php if (
                    (string) $invoice['status'] === 'draft'
                ): ?>
                    Discard Draft
                <?php else: ?>
                    Cancel Issued Document
                <?php endif; ?>
            </strong>
        </div>

        <div class="card-body">
            <form
                method="POST"
                action="/invoices/cancel"
                onsubmit="
                    return confirm(
                        'Cancel this document? ' +
                        'This action cannot be reversed.'
                    );
                ">
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="document_id"
                    value="<?= (int) $invoice['id'] ?>">

                <label
                    for="cancellation_reason"
                    class="form-label">
                    Cancellation Reason
                </label>

                <textarea
                    id="cancellation_reason"
                    name="cancellation_reason"
                    class="form-control"
                    maxlength="500"
                    rows="3"
                    required></textarea>

                <button
                    type="submit"
                    class="btn btn-danger mt-3">
                    <?php if (
                        (string) $invoice['status'] === 'draft'
                    ): ?>
                        Discard Draft
                    <?php else: ?>
                        Cancel Document
                    <?php endif; ?>
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if (
    !$isCreditNote &&
    !empty($creditNotes)
): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">
                Credit Notes
            </h2>
        </div>

        <div class="table-responsive">
            <table
                class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $creditNotes as $creditNote
                    ): ?>
                        <?php
                        $creditReference =
                            'DRAFT-' .
                            (int) $creditNote['id'];

                        if (
                            trim(
                                (string) $creditNote['invoice_number']
                            ) !== ''
                        ) {
                            $creditReference =
                                (string) $creditNote['invoice_number'];
                        }
                        ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    $creditReference,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $creditNote['invoice_date'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    (string) $creditNote['status'] === 'cancelled'
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-danger">
                                        Cancelled
                                    </span>
                                <?php elseif (
                                    (string) $creditNote['status'] === 'draft'
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-warning">
                                        Draft
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge
                                        text-bg-success">
                                        Issued
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $creditNote['correction_reason'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $creditNote['total_amount'],
                                    2
                                ) ?>

                                <?= htmlspecialchars(
                                    (string) $creditNote['currency'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/invoices/show?id=<?= (int) $creditNote['id'] ?>"
                                    class="btn btn-sm btn-outline-primary">
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