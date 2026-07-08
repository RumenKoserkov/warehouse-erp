<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">
        Sale <?= htmlspecialchars($sale['sale_number']) ?>
    </h1>

    <a href="/sales" class="btn btn-outline-secondary">
        Back to Sales
    </a>
</div>

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
                        <td><?= htmlspecialchars($sale['sale_number']) ?></td>
                    </tr>

                    <tr>
                        <th>Sale Date</th>
                        <td><?= htmlspecialchars($sale['sale_date']) ?></td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ($sale['status'] === 'completed'): ?>
                                <span class="badge text-bg-success">Completed</span>
                            <?php elseif ($sale['status'] === 'cancelled'): ?>
                                <span class="badge text-bg-danger">Cancelled</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">
                                    <?= htmlspecialchars($sale['status']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Payment Method</th>
                        <td>
                            <?php if (!empty($sale['payment_method'])): ?>
                                <?= htmlspecialchars($sale['payment_method']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Created By</th>
                        <td>
                            <?php if (!empty($sale['user_name'])): ?>
                                <?= htmlspecialchars($sale['user_name']) ?>
                            <?php else: ?>
                                <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Created At</th>
                        <td><?= htmlspecialchars($sale['created_at']) ?></td>
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
                            <?php if (!empty($sale['client_name'])): ?>
                                <?= htmlspecialchars($sale['client_name']) ?>

                                <?php if (!empty($sale['client_company_name'])): ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($sale['client_company_name']) ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No client</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Client Phone</th>
                        <td>
                            <?php if (!empty($sale['client_phone'])): ?>
                                <?= htmlspecialchars($sale['client_phone']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Client Email</th>
                        <td>
                            <?php if (!empty($sale['client_email'])): ?>
                                <?= htmlspecialchars($sale['client_email']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Client EIK</th>
                        <td>
                            <?php if (!empty($sale['client_eik'])): ?>
                                <?= htmlspecialchars($sale['client_eik']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Warehouse</th>
                        <td>
                            <?= htmlspecialchars($sale['warehouse_code'] . ' - ' . $sale['warehouse_name']) ?>
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
            <p class="text-muted mb-0">No sale items found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
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
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img 
                                            src="<?= htmlspecialchars($item['image_path']) ?>" 
                                            alt="Product image"
                                            style="width: 50px; height: 50px; object-fit: cover;"
                                            class="rounded border"
                                        >
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= htmlspecialchars($item['product_internal_code']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($item['barcode'])): ?>
                                        <?= htmlspecialchars($item['barcode']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars((string)$item['quantity']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['unit']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars((string)$item['unit_price']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars((string)$item['discount_amount']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars((string)$item['total_price']) ?>
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
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">
                Totals
            </div>

            <div class="card-body">
                <table class="table mb-0">
                    <tr>
                        <th>Subtotal</th>
                        <td class="text-end">
                            <?= htmlspecialchars((string)$sale['subtotal']) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Discount</th>
                        <td class="text-end">
                            <?= htmlspecialchars((string)$sale['discount_amount']) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Tax</th>
                        <td class="text-end">
                            <?= htmlspecialchars((string)$sale['tax_amount']) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Total</th>
                        <td class="text-end">
                            <strong>
                                <?= htmlspecialchars((string)$sale['total_amount']) ?>
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
            <?= nl2br(htmlspecialchars($sale['note'])) ?>
        </div>
    </div>
<?php endif; ?>