<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Stock Report</h1>

    <a href="/stock" class="btn btn-outline-secondary">
        Back to Stock
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/stock/report">
            <div class="row">
                <div class="col-md-4 mb-3">
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

                <div class="col-md-4 mb-3">
                    <label class="form-label">Category</label>

                    <select name="category_id" class="form-select">
                        <option value="">All categories</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= htmlspecialchars((string)$category['id']) ?>"
                                <?php if ((string)$filters['category_id'] === (string)$category['id']): ?>
                                selected
                                <?php endif; ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <a href="/stock/report" class="btn btn-outline-secondary">
                        Clear
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
                <h6 class="text-muted">Stock Value</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars(number_format((float)$summary['total_stock_value'], 2)) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Total Quantity</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars(number_format((float)$summary['total_quantity'], 3)) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Stock Records</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars((string)$summary['stock_records']) ?>
                </h3>

                <small class="text-muted">
                    <?= htmlspecialchars((string)$summary['products_with_stock']) ?> products with stock records
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Low / Out of Stock</h6>

                <h3 class="mb-0">
                    <?= htmlspecialchars((string)$summary['low_stock_count']) ?>
                    /
                    <?= htmlspecialchars((string)$summary['out_of_stock_count']) ?>
                </h3>

                <small class="text-muted">
                    low stock / out of stock
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Stock By Warehouse
            </div>

            <div class="card-body">
                <?php if (empty($stockByWarehouse)): ?>
                    <p class="text-muted mb-0">
                        No stock by warehouse found.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th>Products</th>
                                    <th>Qty</th>
                                    <th>Value</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($stockByWarehouse as $warehouse): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($warehouse['warehouse_code']) ?>
                                            </strong>

                                            <br>

                                            <?= htmlspecialchars($warehouse['warehouse_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string)$warehouse['products_count']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(number_format((float)$warehouse['total_quantity'], 3)) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(number_format((float)$warehouse['total_value'], 2)) ?>
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
                Stock By Category
            </div>

            <div class="card-body">
                <?php if (empty($stockByCategory)): ?>
                    <p class="text-muted mb-0">
                        No stock by category found.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Products</th>
                                    <th>Qty</th>
                                    <th>Value</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($stockByCategory as $category): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($category['category_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string)$category['products_count']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(number_format((float)$category['total_quantity'], 3)) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(number_format((float)$category['total_value'], 2)) ?>
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

<div class="card shadow-sm mb-4">
    <div class="card-header">
        Most Valuable Stock
    </div>

    <div class="card-body">
        <?php if (empty($mostValuableStock)): ?>
            <p class="text-muted mb-0">
                No valuable stock records found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Purchase Price</th>
                            <th>Value</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($mostValuableStock as $item): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($item['internal_code']) ?>
                                    </strong>

                                    <br>

                                    <?= htmlspecialchars($item['product_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['warehouse_code'] . ' - ' . $item['warehouse_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['category_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(number_format((float)$item['quantity'], 3)) ?>
                                    <?= htmlspecialchars($item['unit']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(number_format((float)$item['purchase_price'], 2)) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(number_format((float)$item['stock_value'], 2)) ?>
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

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Low Stock Items
            </div>

            <div class="card-body">
                <?php if (empty($lowStockItems)): ?>
                    <p class="text-muted mb-0">
                        No low stock items.
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
                                <?php foreach ($lowStockItems as $item): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($item['internal_code']) ?>
                                            </strong>

                                            <br>

                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['warehouse_code'] . ' - ' . $item['warehouse_name']) ?>
                                        </td>

                                        <td>
                                            <span class="badge text-bg-warning">
                                                <?= htmlspecialchars(number_format((float)$item['quantity'], 3)) ?>
                                                <?= htmlspecialchars($item['unit']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(number_format((float)$item['min_stock'], 3)) ?>
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
                Out Of Stock Items
            </div>

            <div class="card-body">
                <?php if (empty($outOfStockItems)): ?>
                    <p class="text-muted mb-0">
                        No out of stock items.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Warehouse</th>
                                    <th>Qty</th>
                                    <th>Category</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($outOfStockItems as $item): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($item['internal_code']) ?>
                                            </strong>

                                            <br>

                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['warehouse_code'] . ' - ' . $item['warehouse_name']) ?>
                                        </td>

                                        <td>
                                            <span class="badge text-bg-danger">
                                                <?= htmlspecialchars(number_format((float)$item['quantity'], 3)) ?>
                                                <?= htmlspecialchars($item['unit']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['category_name']) ?>
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