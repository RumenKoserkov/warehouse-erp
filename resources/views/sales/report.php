<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Sales Report</h1>

    <a href="/sales" class="btn btn-outline-secondary">
        Back to Sales
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/sales/report">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date From</label>

                    <input 
                        type="date" 
                        name="date_from" 
                        class="form-control"
                        value="<?= htmlspecialchars($dateFrom) ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Date To</label>

                    <input 
                        type="date" 
                        name="date_to" 
                        class="form-control"
                        value="<?= htmlspecialchars($dateTo) ?>"
                    >
                </div>

                <div class="col-md-4 mb-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <a href="/sales/report" class="btn btn-outline-secondary">
                        This Month
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Total Sales</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars(number_format((float)$summary['total_sales'], 2)) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Sales Count</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars((string)$summary['sales_count']) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Average Sale</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars(number_format((float)$summary['average_sale'], 2)) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Total Discount</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars(number_format((float)$summary['total_discount'], 2)) ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Top Selling Products
            </div>

            <div class="card-body">
                <?php if (empty($topProducts)): ?>
                    <p class="text-muted mb-0">
                        No sold products for this period.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($product['product_internal_code']) ?>
                                            </strong>

                                            <br>

                                            <?= htmlspecialchars($product['product_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string)$product['total_quantity']) ?>
                                            <?= htmlspecialchars($product['unit']) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(number_format((float)$product['total_amount'], 2)) ?>
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
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Sales By Day
            </div>

            <div class="card-body">
                <?php if (empty($salesByDay)): ?>
                    <p class="text-muted mb-0">
                        No sales for this period.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Sales Count</th>
                                    <th>Total</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($salesByDay as $day): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($day['sale_date']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string)$day['sales_count']) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(number_format((float)$day['total_amount'], 2)) ?>
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
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        Recent Sales In Period
    </div>

    <div class="card-body">
        <?php if (empty($recentSales)): ?>
            <p class="text-muted mb-0">
                No recent sales for this period.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Sale Number</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Warehouse</th>
                            <th>User</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($sale['sale_number']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($sale['sale_date']) ?>
                                </td>

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

                                <td>
                                    <?= htmlspecialchars($sale['warehouse_code'] . ' - ' . $sale['warehouse_name']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($sale['user_name'])): ?>
                                        <?= htmlspecialchars($sale['user_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($sale['payment_method'])): ?>
                                        <?= htmlspecialchars($sale['payment_method']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(number_format((float)$sale['total_amount'], 2)) ?>
                                    </strong>
                                </td>

                                <td>
                                    <a 
                                        href="/sales/show?id=<?= htmlspecialchars((string)$sale['id']) ?>" 
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>