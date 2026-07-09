<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Stock History</h1>

    <a href="/stock" class="btn btn-outline-secondary">
        Back to Stock
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/stock/history">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Search</label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Product, code, warehouse, user..."
                        value="<?= htmlspecialchars($filters['search']) ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Type</label>

                    <select name="type" class="form-select">
                        <option value="">All types</option>

                        <?php foreach ($types as $type): ?>
                            <option
                                value="<?= htmlspecialchars($type) ?>"
                                <?php if ($filters['type'] === $type): ?>
                                selected
                                <?php endif; ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Product</label>

                    <select name="product_id" class="form-select">
                        <option value="">All products</option>

                        <?php foreach ($products as $product): ?>
                            <option
                                value="<?= htmlspecialchars((string)$product['id']) ?>"
                                <?php if ((string)$filters['product_id'] === (string)$product['id']): ?>
                                selected
                                <?php endif; ?>>
                                <?= htmlspecialchars($product['internal_code'] . ' - ' . $product['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Warehouse</label>

                    <select name="warehouse_id" class="form-select">
                        <option value="">All warehouses</option>

                        <?php foreach ($warehouses as $warehouse): ?>
                            <option
                                value="<?= htmlspecialchars((string)$warehouse['id']) ?>"
                                <?php if ((string)$filters['warehouse_id'] === (string)$warehouse['id']): ?>
                                selected
                                <?php endif; ?>>
                                <?= htmlspecialchars($warehouse['code'] . ' - ' . $warehouse['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Filter
                </button>

                <a href="/stock/history" class="btn btn-outline-secondary">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <p class="text-muted mb-0">
                No stock history found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Product</th>
                            <th>From Warehouse</th>
                            <th>To Warehouse</th>
                            <th>Quantity</th>
                            <th>User</th>
                            <th>Reference</th>
                            <th>Note</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($transaction['created_at']) ?>
                                </td>

                                <td>
                                    <?php if ($transaction['type'] === 'in' || $transaction['type'] === 'purchase'): ?>
                                        <span class="badge text-bg-success">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    <?php elseif ($transaction['type'] === 'out' || $transaction['type'] === 'sale'): ?>
                                        <span class="badge text-bg-danger">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    <?php elseif ($transaction['type'] === 'sale_cancel' || $transaction['type'] === 'purchase_cancel'): ?>
                                        <span class="badge text-bg-warning">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    <?php elseif ($transaction['type'] === 'transfer'): ?>
                                        <span class="badge text-bg-primary">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($transaction['internal_code']) ?>
                                    </strong>

                                    <br>

                                    <?= htmlspecialchars($transaction['product_name']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($transaction['from_warehouse_name'])): ?>
                                        <?= htmlspecialchars($transaction['from_warehouse_code'] . ' - ' . $transaction['from_warehouse_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($transaction['to_warehouse_name'])): ?>
                                        <?= htmlspecialchars($transaction['to_warehouse_code'] . ' - ' . $transaction['to_warehouse_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars((string)$transaction['quantity']) ?>
                                    </strong>

                                    <?= htmlspecialchars($transaction['unit']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($transaction['user_name'])): ?>
                                        <?= htmlspecialchars($transaction['user_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($transaction['reference_type'])): ?>
                                        <?= htmlspecialchars($transaction['reference_type']) ?>

                                        <?php if (!empty($transaction['reference_id'])): ?>
                                            #<?= htmlspecialchars((string)$transaction['reference_id']) ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($transaction['note'])): ?>
                                        <?= htmlspecialchars($transaction['note']) ?>
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