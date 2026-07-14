<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Products</h1>

    <a href="/products/create" class="btn btn-primary">
        Create Product
    </a>
</div>

<form method="GET" action="/products" class="mb-3">
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by name, code, barcode, category, supplier..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit" class="btn btn-outline-secondary">
            Search
        </button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($products)): ?>
            <p class="text-muted mb-0">No products found.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Code</th>
                            <th>Barcode</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Unit</th>
                            <th>Purchase</th>
                            <th>Selling</th>
                            <th>Min Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>

                                <td>
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img
                                            src="<?= htmlspecialchars($product['image_path']) ?>"
                                            alt="Product image"
                                            style="width: 50px; height: 50px; object-fit: cover;"
                                            class="rounded border">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= htmlspecialchars($product['internal_code']) ?>
                                    </span>
                                </td>

                                <td><?= htmlspecialchars($product['barcode'] ?? '') ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td><?= htmlspecialchars($product['supplier_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($product['unit']) ?></td>
                                <td><?= htmlspecialchars((string)$product['purchase_price']) ?></td>
                                <td><?= htmlspecialchars((string)$product['selling_price']) ?></td>
                                <td><?= htmlspecialchars((string)$product['min_stock']) ?></td>

                                <td>
                                    <?php if ((int)$product['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a
                                            href="/products/edit?id=<?= htmlspecialchars((string)$product['id']) ?>"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        <?php if ((int)$product['is_active'] === 1): ?>
                                            <form
                                                action="/products/deactivate"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to deactivate this product?');">
                                                <?= \App\Core\Csrf::field() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= htmlspecialchars((string)$product['id']) ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger">
                                                    Deactivate
                                                </button>

                                            </form>
                                        <?php endif; ?>

                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        <?php endif; ?>
    </div>
</div>