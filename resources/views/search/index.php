<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Global Search</h1>

    <a href="/dashboard" class="btn btn-outline-secondary">
        Back to Dashboard
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/search">
            <div class="input-group input-group-lg">
                <input 
                    type="text" 
                    name="q" 
                    class="form-control"
                    placeholder="Search products, clients, suppliers, sales, purchases..."
                    value="<?= htmlspecialchars($query) ?>"
                    autofocus
                >

                <button type="submit" class="btn btn-primary">
                    Search
                </button>
            </div>
        </form>

        <?php if ($query !== '' && strlen($query) < 2): ?>
            <div class="alert alert-warning mt-3 mb-0">
                Please enter at least 2 characters.
            </div>
        <?php endif; ?>

        <?php if ($query !== '' && strlen($query) >= 2): ?>
            <p class="text-muted mt-3 mb-0">
                Found <?= htmlspecialchars((string)$totalResults) ?> results for:
                <strong><?= htmlspecialchars($query) ?></strong>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php if ($query === ''): ?>
    <div class="alert alert-info">
        Enter a search term to find products, clients, suppliers, sales and purchases.
    </div>
<?php elseif (strlen($query) >= 2 && $totalResults === 0): ?>
    <div class="alert alert-warning">
        No results found.
    </div>
<?php endif; ?>

<?php if (!empty($results['products'])): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Products
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Barcode</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Prices</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($results['products'] as $product): ?>
                            <tr>
                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= htmlspecialchars($product['internal_code']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($product['barcode'])): ?>
                                        <?= htmlspecialchars($product['barcode']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Unit: <?= htmlspecialchars($product['unit']) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($product['supplier_name'])): ?>
                                        <?= htmlspecialchars($product['supplier_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">No supplier</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <small>
                                        Purchase:
                                        <?= htmlspecialchars(number_format((float)$product['purchase_price'], 2)) ?>
                                    </small>

                                    <br>

                                    <small>
                                        Selling:
                                        <?= htmlspecialchars(number_format((float)$product['selling_price'], 2)) ?>
                                    </small>
                                </td>

                                <td>
                                    <?php if ((int)$product['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a 
                                        href="/products/edit?id=<?= htmlspecialchars((string)$product['id']) ?>" 
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Open
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($results['clients'])): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Clients
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Contacts</th>
                            <th>EIK</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($results['clients'] as $client): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($client['name']) ?>
                                    </strong>

                                    <?php if (!empty($client['contact_person'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            Contact: <?= htmlspecialchars($client['contact_person']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($client['company_name'])): ?>
                                        <?= htmlspecialchars($client['company_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($client['phone'])): ?>
                                        <?= htmlspecialchars($client['phone']) ?>
                                    <?php endif; ?>

                                    <?php if (!empty($client['email'])): ?>
                                        <br>
                                        <?= htmlspecialchars($client['email']) ?>
                                    <?php endif; ?>

                                    <?php if (empty($client['phone']) && empty($client['email'])): ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($client['eik'])): ?>
                                        <?= htmlspecialchars($client['eik']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ((int)$client['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a 
                                        href="/clients/edit?id=<?= htmlspecialchars((string)$client['id']) ?>" 
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Open
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($results['suppliers'])): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Suppliers
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Contacts</th>
                            <th>EIK</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($results['suppliers'] as $supplier): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($supplier['name']) ?>
                                    </strong>

                                    <?php if (!empty($supplier['contact_person'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            Contact: <?= htmlspecialchars($supplier['contact_person']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($supplier['company_name'])): ?>
                                        <?= htmlspecialchars($supplier['company_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($supplier['phone'])): ?>
                                        <?= htmlspecialchars($supplier['phone']) ?>
                                    <?php endif; ?>

                                    <?php if (!empty($supplier['email'])): ?>
                                        <br>
                                        <?= htmlspecialchars($supplier['email']) ?>
                                    <?php endif; ?>

                                    <?php if (empty($supplier['phone']) && empty($supplier['email'])): ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($supplier['eik'])): ?>
                                        <?= htmlspecialchars($supplier['eik']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ((int)$supplier['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a 
                                        href="/suppliers/edit?id=<?= htmlspecialchars((string)$supplier['id']) ?>" 
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Open
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($results['sales'])): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Sales
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Sale Number</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Warehouse</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($results['sales'] as $sale): ?>
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
                                    <?php if (!empty($sale['payment_method'])): ?>
                                        <?= htmlspecialchars($sale['payment_method']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

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
                                        Open
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($results['purchases'])): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Purchases
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Purchase Number</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($results['purchases'] as $purchase): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($purchase['purchase_number']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($purchase['purchase_date']) ?>
                                </td>

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

                                <td>
                                    <?= htmlspecialchars($purchase['warehouse_code'] . ' - ' . $purchase['warehouse_name']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($purchase['payment_method'])): ?>
                                        <?= htmlspecialchars($purchase['payment_method']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

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

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(number_format((float)$purchase['total_amount'], 2)) ?>
                                    </strong>
                                </td>

                                <td>
                                    <a 
                                        href="/purchases/show?id=<?= htmlspecialchars((string)$purchase['id']) ?>" 
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Open
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>