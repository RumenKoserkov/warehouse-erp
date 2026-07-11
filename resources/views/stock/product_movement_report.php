<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Product Movement Report</h1>

    <a href="/stock/report" class="btn btn-outline-secondary">
        Back to Stock Report
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/product-movement/report">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Product</label>

                    <select name="product_id" class="form-select" required>
                        <option value="">Select product</option>

                        <?php foreach ($products as $product): ?>
                            <option
                                value="<?= htmlspecialchars((string)$product['id']) ?>"
                                <?php if ((int)$productId === (int)$product['id']): ?>
                                selected
                                <?php endif; ?>>
                                <?= htmlspecialchars($product['internal_code'] . ' - ' . $product['name'] . ' (' . $product['unit'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Date From</label>

                    <input
                        type="date"
                        name="date_from"
                        class="form-control"
                        value="<?= htmlspecialchars($dateFrom) ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Date To</label>

                    <input
                        type="date"
                        name="date_to"
                        class="form-control"
                        value="<?= htmlspecialchars($dateTo) ?>">
                </div>

                <div class="col-md-2 mb-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <a href="/product-movement/report" class="btn btn-outline-secondary">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($productId > 0 && $selectedProduct === null): ?>
    <div class="alert alert-danger">
        Selected product was not found.
    </div>
<?php endif; ?>

<?php if ($selectedProduct === null): ?>
    <div class="alert alert-info">
        Select a product to see its movement report.
    </div>
<?php else: ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Selected Product
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <strong>Code:</strong>
                    <?= htmlspecialchars($selectedProduct['internal_code']) ?>
                </div>

                <div class="col-md-3 mb-2">
                    <strong>Name:</strong>
                    <?= htmlspecialchars($selectedProduct['name']) ?>
                </div>

                <div class="col-md-2 mb-2">
                    <strong>Unit:</strong>
                    <?= htmlspecialchars($selectedProduct['unit']) ?>
                </div>

                <div class="col-md-2 mb-2">
                    <strong>Purchase Price:</strong>
                    <?= htmlspecialchars(number_format((float)$selectedProduct['purchase_price'], 2)) ?>
                </div>

                <div class="col-md-2 mb-2">
                    <strong>Selling Price:</strong>
                    <?= htmlspecialchars(number_format((float)$selectedProduct['selling_price'], 2)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Incoming</h6>

                    <h3 class="mb-0">
                        <?= htmlspecialchars(number_format((float)$summary['incoming_quantity'], 3)) ?>
                    </h3>

                    <small class="text-muted">
                        <?= htmlspecialchars($selectedProduct['unit']) ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Outgoing</h6>

                    <h3 class="mb-0">
                        <?= htmlspecialchars(number_format((float)$summary['outgoing_quantity'], 3)) ?>
                    </h3>

                    <small class="text-muted">
                        <?= htmlspecialchars($selectedProduct['unit']) ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Transfers</h6>

                    <h3 class="mb-0">
                        <?= htmlspecialchars(number_format((float)$summary['transfer_quantity'], 3)) ?>
                    </h3>

                    <small class="text-muted">
                        <?= htmlspecialchars($selectedProduct['unit']) ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Net Change</h6>

                    <h3 class="mb-0">
                        <?= htmlspecialchars(number_format((float)$summary['net_change'], 3)) ?>
                    </h3>

                    <small class="text-muted">
                        <?= htmlspecialchars((string)$summary['movements_count']) ?> movements
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Movement By Warehouse
        </div>

        <div class="card-body">
            <?php if (empty($warehouseSummary)): ?>
                <p class="text-muted mb-0">
                    No warehouse movements for this period.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Incoming</th>
                                <th>Outgoing</th>
                                <th>Transfer In</th>
                                <th>Transfer Out</th>
                                <th>Net Change</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($warehouseSummary as $warehouse): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($warehouse['warehouse_code']) ?>
                                        </strong>

                                        <br>

                                        <?= htmlspecialchars($warehouse['warehouse_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(number_format((float)$warehouse['incoming_quantity'], 3)) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(number_format((float)$warehouse['outgoing_quantity'], 3)) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(number_format((float)$warehouse['transfer_in_quantity'], 3)) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(number_format((float)$warehouse['transfer_out_quantity'], 3)) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(number_format((float)$warehouse['net_change'], 3)) ?>
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

    <div class="card shadow-sm">
        <div class="card-header">
            Movement History
        </div>

        <div class="card-body">
            <?php if (empty($movements)): ?>
                <p class="text-muted mb-0">
                    No movements for this product in the selected period.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>From Warehouse</th>
                                <th>To Warehouse</th>
                                <th>Quantity</th>
                                <th>User</th>
                                <th>Reference</th>
                                <th>Note</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($movements as $movement): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($movement['created_at']) ?>
                                    </td>

                                    <td>
                                        <?php if ($movement['type'] === 'in' || $movement['type'] === 'purchase'): ?>
                                            <span class="badge text-bg-success">
                                                <?= htmlspecialchars($movement['type']) ?>
                                            </span>
                                        <?php elseif ($movement['type'] === 'out' || $movement['type'] === 'sale'): ?>
                                            <span class="badge text-bg-danger">
                                                <?= htmlspecialchars($movement['type']) ?>
                                            </span>
                                        <?php elseif ($movement['type'] === 'transfer'): ?>
                                            <span class="badge text-bg-primary">
                                                <?= htmlspecialchars($movement['type']) ?>
                                            </span>
                                        <?php elseif ($movement['type'] === 'sale_cancel' || $movement['type'] === 'purchase_cancel'): ?>
                                            <span class="badge text-bg-warning">
                                                <?= htmlspecialchars($movement['type']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">
                                                <?= htmlspecialchars($movement['type']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($movement['from_warehouse_name'])): ?>
                                            <?= htmlspecialchars($movement['from_warehouse_code'] . ' - ' . $movement['from_warehouse_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($movement['to_warehouse_name'])): ?>
                                            <?= htmlspecialchars($movement['to_warehouse_code'] . ' - ' . $movement['to_warehouse_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(number_format((float)$movement['quantity'], 3)) ?>
                                            <?= htmlspecialchars($movement['unit']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php if (!empty($movement['user_name'])): ?>
                                            <?= htmlspecialchars($movement['user_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($movement['reference_type'])): ?>
                                            <?= htmlspecialchars($movement['reference_type']) ?>

                                            <?php if (!empty($movement['reference_id'])): ?>
                                                #<?= htmlspecialchars((string)$movement['reference_id']) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($movement['note'])): ?>
                                            <?= htmlspecialchars($movement['note']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>