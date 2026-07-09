<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">
        Purchase <?= htmlspecialchars($purchase['purchase_number']) ?>
    </h1>

    <a href="/purchases" class="btn btn-outline-secondary">
        Back to Purchases
    </a>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Purchase Information
            </div>

            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Purchase Number</th>
                        <td><?= htmlspecialchars($purchase['purchase_number']) ?></td>
                    </tr>

                    <tr>
                        <th>Purchase Date</th>
                        <td><?= htmlspecialchars($purchase['purchase_date']) ?></td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ($purchase['status'] === 'completed'): ?>
                                <span class="badge text-bg-success">Completed</span>
                            <?php elseif ($purchase['status'] === 'cancelled'): ?>
                                <span class="badge text-bg-danger">Cancelled</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">
                                    <?= htmlspecialchars($purchase['status']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Payment Method</th>
                        <td>
                            <?php if (!empty($purchase['payment_method'])): ?>
                                <?= htmlspecialchars($purchase['payment_method']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Created By</th>
                        <td>
                            <?php if (!empty($purchase['user_name'])): ?>
                                <?= htmlspecialchars($purchase['user_name']) ?>
                            <?php else: ?>
                                <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Created At</th>
                        <td><?= htmlspecialchars($purchase['created_at']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Supplier & Warehouse
            </div>

            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Supplier</th>
                        <td>
                            <?php if (!empty($purchase['supplier_name'])): ?>
                                <?= htmlspecialchars($purchase['supplier_name']) ?>

                                <?php if (!empty($purchase['supplier_company_name'])): ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($purchase['supplier_company_name']) ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No supplier</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Supplier Phone</th>
                        <td>
                            <?php if (!empty($purchase['supplier_phone'])): ?>
                                <?= htmlspecialchars($purchase['supplier_phone']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Supplier Email</th>
                        <td>
                            <?php if (!empty($purchase['supplier_email'])): ?>
                                <?= htmlspecialchars($purchase['supplier_email']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Supplier EIK</th>
                        <td>
                            <?php if (!empty($purchase['supplier_eik'])): ?>
                                <?= htmlspecialchars($purchase['supplier_eik']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Warehouse</th>
                        <td>
                            <?= htmlspecialchars($purchase['warehouse_code'] . ' - ' . $purchase['warehouse_name']) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        Purchase Items
    </div>

    <div class="card-body">
        <?php if (empty($items)): ?>
            <p class="text-muted mb-0">No purchase items found.</p>
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
                            <th>Unit Cost</th>
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
                                    <?= htmlspecialchars((string)$item['unit_cost']) ?>
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
                            <?= htmlspecialchars((string)$purchase['subtotal']) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Discount</th>
                        <td class="text-end">
                            <?= htmlspecialchars((string)$purchase['discount_amount']) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Tax</th>
                        <td class="text-end">
                            <?= htmlspecialchars((string)$purchase['tax_amount']) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Total</th>
                        <td class="text-end">
                            <strong>
                                <?= htmlspecialchars((string)$purchase['total_amount']) ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($purchase['note'])): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            Note
        </div>

        <div class="card-body">
            <?= nl2br(htmlspecialchars($purchase['note'])) ?>
        </div>
    </div>
<?php endif; ?>