<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Dashboard</h1>

    <div class="d-flex gap-2">
        <a href="/sales/create" class="btn btn-success">
            New Sale
        </a>

        <a href="/purchases/create" class="btn btn-primary">
            New Purchase
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Products</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars((string)$stats['total_products']) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Stock Value</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars(number_format((float)$stats['total_stock_value'], 2)) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Today Sales</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars(number_format((float)$stats['today_sales_amount'], 2)) ?>
                </h3>

                <small class="text-muted">
                    <?= htmlspecialchars((string)$stats['today_sales_count']) ?> sales today
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Today Purchases</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars(number_format((float)$stats['today_purchases_amount'], 2)) ?>
                </h3>

                <small class="text-muted">
                    <?= htmlspecialchars((string)$stats['today_purchases_count']) ?> purchases today
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Low Stock Products</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars((string)$stats['low_stock_count']) ?>
                </h3>

                <a href="/stock" class="btn btn-sm btn-outline-warning mt-3">
                    View Stock
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-8 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Quick Actions
            </div>

            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="/stock/in" class="btn btn-outline-success">
                        Stock In
                    </a>

                    <a href="/stock/out" class="btn btn-outline-danger">
                        Stock Out
                    </a>

                    <a href="/stock/transfer" class="btn btn-outline-primary">
                        Transfer Stock
                    </a>

                    <a href="/stock/history" class="btn btn-outline-secondary">
                        Stock History
                    </a>

                    <a href="/products" class="btn btn-outline-secondary">
                        Products
                    </a>

                    <a href="/clients" class="btn btn-outline-secondary">
                        Clients
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Low Stock</span>

                <a href="/stock" class="btn btn-sm btn-outline-secondary">
                    View All
                </a>
            </div>

            <div class="card-body">
                <?php if (empty($lowStockProducts)): ?>
                    <p class="text-muted mb-0">
                        No low stock products.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Warehouse</th>
                                    <th>Qty</th>
                                    <th>Min</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($product['internal_code']) ?>
                                            </strong>

                                            <br>

                                            <?= htmlspecialchars($product['product_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($product['warehouse_code'] . ' - ' . $product['warehouse_name']) ?>
                                        </td>

                                        <td>
                                            <span class="badge text-bg-warning">
                                                <?= htmlspecialchars((string)$product['quantity']) ?>
                                                <?= htmlspecialchars($product['unit']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string)$product['min_stock']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Stock Movements</span>

                <a href="/stock/history" class="btn btn-sm btn-outline-secondary">
                    View All
                </a>
            </div>

            <div class="card-body">
                <?php if (empty($recentTransactions)): ?>
                    <p class="text-muted mb-0">
                        No recent stock movements.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <?php if ($transaction['type'] === 'in' || $transaction['type'] === 'purchase'): ?>
                                                <span class="badge text-bg-success">
                                                    <?= htmlspecialchars($transaction['type']) ?>
                                                </span>
                                            <?php elseif ($transaction['type'] === 'out' || $transaction['type'] === 'sale'): ?>
                                                <span class="badge text-bg-danger">
                                                    <?= htmlspecialchars($transaction['type']) ?>
                                                </span>
                                            <?php elseif ($transaction['type'] === 'transfer'): ?>
                                                <span class="badge text-bg-primary">
                                                    <?= htmlspecialchars($transaction['type']) ?>
                                                </span>
                                            <?php elseif ($transaction['type'] === 'sale_cancel' || $transaction['type'] === 'purchase_cancel'): ?>
                                                <span class="badge text-bg-warning">
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
                                            <?= htmlspecialchars((string)$transaction['quantity']) ?>
                                            <?= htmlspecialchars($transaction['unit']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($transaction['created_at']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>