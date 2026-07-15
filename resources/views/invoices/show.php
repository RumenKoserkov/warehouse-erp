<?php
$reference = 'DRAFT-' . (int) $invoice['id'];

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
            Invoice <?= htmlspecialchars(
                        $reference,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
        </h1>

        <span class="badge text-bg-warning">
            <?= htmlspecialchars(
                ucfirst(
                    (string) $invoice['status']
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>
    </div>

    <a
        href="/invoices"
        class="btn btn-outline-secondary">
        Back to Invoices
    </a>
</div>

<?php if ($invoice['status'] === 'draft'): ?>
    <div class="alert alert-warning">
        This document is a draft and does not yet have
        an official invoice number.
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
                <strong>Invoice date:</strong>
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
        <table
            class="table align-middle mb-0">
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
    trim((string) $invoice['note']) !== ''
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