<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">
        Sale <?= htmlspecialchars(
                    (string) $sale['sale_number'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
    </h1>

    <div class="d-flex gap-2">
        <?php if ($existingInvoice !== null): ?>
            <a
                href="/invoices/show?id=<?= (int) $existingInvoice['id'] ?>"
                class="btn btn-outline-primary">
                View Invoice
            </a>
        <?php elseif (
            (string) $sale['status'] === 'completed' &&
            isset($sale['client_id']) &&
            (int) $sale['client_id'] > 0
        ): ?>
            <form
                method="POST"
                action="/invoices/from-sale"
                class="d-inline">
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="sale_id"
                    value="<?= (int) $sale['id'] ?>">

                <button
                    type="submit"
                    class="btn btn-primary">
                    Generate Invoice
                </button>
            </form>
        <?php endif; ?>

        <?php if ($sale['status'] === 'completed'): ?>
            <form
                action="/sales/cancel"
                method="POST"
                onsubmit="return confirm(
                    'Are you sure you want to cancel this sale and return the stock?'
                );">
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= htmlspecialchars(
                                (string) $sale['id'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">

                <button
                    type="submit"
                    class="btn btn-danger">
                    Cancel Sale
                </button>
            </form>
        <?php endif; ?>

        <a
            href="/sales"
            class="btn btn-outline-secondary">
            Back to Sales
        </a>
    </div>
</div>

<?php if (
    $existingInvoice === null &&
    (string) $sale['status'] === 'completed' &&
    (
        !isset($sale['client_id']) ||
        (int) $sale['client_id'] <= 0
    )
): ?>
    <div class="alert alert-warning">
        This sale does not have a client.
        An invoice cannot be generated.
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Sale Information
            </div>

            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Sale Number</th>

                        <td>
                            <?= htmlspecialchars(
                                (string) $sale['sale_number'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Sale Date</th>

                        <td>
                            <?= htmlspecialchars(
                                (string) $sale['sale_date'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Status</th>

                        <td>
                            <?php if (
                                $sale['status'] ===
                                'completed'
                            ): ?>
                                <span
                                    class="badge text-bg-success">
                                    Completed
                                </span>
                            <?php elseif (
                                $sale['status'] ===
                                'cancelled'
                            ): ?>
                                <span
                                    class="badge text-bg-danger">
                                    Cancelled
                                </span>
                            <?php else: ?>
                                <span
                                    class="badge text-bg-secondary">
                                    <?= htmlspecialchars(
                                        (string) $sale['status'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Payment Method</th>

                        <td>
                            <?php if (
                                !empty($sale['payment_method'])
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $sale['payment_method'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>VAT Registered</th>

                        <td>
                            <?php if (
                                (int) $sale['vat_registered'] === 1
                            ): ?>
                                Yes
                            <?php else: ?>
                                No
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Price Mode</th>

                        <td>
                            <?php if (
                                (int) $sale['vat_registered'] !== 1
                            ): ?>
                                VAT not charged
                            <?php elseif (
                                (int) $sale['prices_include_vat'] === 1
                            ): ?>
                                VAT included
                            <?php else: ?>
                                VAT excluded
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Default VAT Rate</th>

                        <td>
                            <?= number_format(
                                (float) $sale['default_vat_rate'],
                                2
                            ) ?>%
                        </td>
                    </tr>

                    <tr>
                        <th>Created By</th>

                        <td>
                            <?php if (
                                !empty($sale['user_name'])
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $sale['user_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    System
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Created At</th>

                        <td>
                            <?= htmlspecialchars(
                                (string) $sale['created_at'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Client & Warehouse
            </div>

            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Client</th>

                        <td>
                            <?php if (
                                !empty($sale['client_name'])
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $sale['client_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                <?php if (
                                    !empty($sale['client_company_name'])
                                ): ?>
                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            (string) $sale['client_company_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    No client
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Client Phone</th>

                        <td>
                            <?php if (
                                !empty($sale['client_phone'])
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $sale['client_phone'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Client Email</th>

                        <td>
                            <?php if (
                                !empty($sale['client_email'])
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $sale['client_email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Client EIK</th>

                        <td>
                            <?php if (
                                !empty($sale['client_eik'])
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $sale['client_eik'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Warehouse</th>

                        <td>
                            <?= htmlspecialchars(
                                (string) $sale['warehouse_code'] .
                                    ' - ' .
                                    (string) $sale['warehouse_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        Sale Items
    </div>

    <div class="card-body">
        <?php if (empty($items)): ?>
            <p class="text-muted mb-0">
                No sale items found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table
                    class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Code</th>
                            <th>Product</th>
                            <th>Barcode</th>
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
                        <?php foreach (
                            $items as $item
                        ): ?>
                            <tr>
                                <td>
                                    <?php if (
                                        !empty($item['image_path'])
                                    ): ?>
                                        <img
                                            src="<?= htmlspecialchars(
                                                        (string) $item['image_path'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                            alt="Product image"
                                            style="width: 50px; height: 50px; object-fit: cover;"
                                            class="rounded border">
                                    <?php else: ?>
                                        <span class="text-muted">
                                            No image
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span
                                        class="badge text-bg-secondary">
                                        <?= htmlspecialchars(
                                            (string) $item['product_internal_code'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $item['product_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        !empty($item['barcode'])
                                    ): ?>
                                        <?= htmlspecialchars(
                                            (string) $item['barcode'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            -
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $item['quantity'],
                                        ENT_QUOTES,
                                        'UTF-8'
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
                                            (float) $item['total_price'],
                                            2
                                        ) ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row justify-content-end">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                Totals
            </div>

            <div class="card-body">
                <table class="table mb-0">
                    <tr>
                        <th>Net Subtotal</th>

                        <td class="text-end">
                            <?= number_format(
                                (float) $sale['subtotal'],
                                2
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Net Discount</th>

                        <td class="text-end">
                            <?= number_format(
                                (float) $sale['discount_amount'],
                                2
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>VAT</th>

                        <td class="text-end">
                            <?= number_format(
                                (float) $sale['tax_amount'],
                                2
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Total with VAT</th>

                        <td class="text-end">
                            <strong>
                                <?= number_format(
                                    (float) $sale['total_amount'],
                                    2
                                ) ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($sale['note'])): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            Note
        </div>

        <div class="card-body">
            <?= nl2br(
                htmlspecialchars(
                    (string) $sale['note'],
                    ENT_QUOTES,
                    'UTF-8'
                )
            ) ?>
        </div>
    </div>
<?php endif; ?>