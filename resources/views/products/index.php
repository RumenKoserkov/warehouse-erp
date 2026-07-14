<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Products</h1>

    <a href="/products/create" class="btn btn-primary">
        Create Product
    </a>
</div>

<form method="GET" action="/products" class="mb-3">
    <input
        type="hidden"
        name="sort"
        value="<?= htmlspecialchars(
                    $sorter->key(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">

    <input
        type="hidden"
        name="direction"
        value="<?= htmlspecialchars(
                    $sorter->direction(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">

    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by name, code, barcode, category, supplier..."
            value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

        <button type="submit" class="btn btn-outline-secondary">
            Search
        </button>

        <a href="/products" class="btn btn-outline-secondary">
            Reset
        </a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($products)): ?>
            <p class="text-muted mb-0">
                No products found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>

                            <th>
                                <?php
                                $sortKey = 'code';
                                $sortLabel = 'Code';

                                require __DIR__
                                    . '/../components/sort_link.php';
                                ?>
                            </th>

                            <th>Barcode</th>

                            <th>
                                <?php
                                $sortKey = 'name';
                                $sortLabel = 'Name';

                                require __DIR__
                                    . '/../components/sort_link.php';
                                ?>
                            </th>

                            <th>
                                <?php
                                $sortKey = 'category';
                                $sortLabel = 'Category';

                                require __DIR__
                                    . '/../components/sort_link.php';
                                ?>
                            </th>

                            <th>
                                <?php
                                $sortKey = 'supplier';
                                $sortLabel = 'Supplier';

                                require __DIR__
                                    . '/../components/sort_link.php';
                                ?>
                            </th>

                            <th>Unit</th>

                            <th>
                                <?php
                                $sortKey = 'purchase_price';
                                $sortLabel = 'Purchase';

                                require __DIR__
                                    . '/../components/sort_link.php';
                                ?>
                            </th>

                            <th>
                                <?php
                                $sortKey = 'selling_price';
                                $sortLabel = 'Selling';

                                require __DIR__
                                    . '/../components/sort_link.php';
                                ?>
                            </th>

                            <th>
                                <?php
                                $sortKey = 'min_stock';
                                $sortLabel = 'Min Stock';

                                require __DIR__
                                    . '/../components/sort_link.php';
                                ?>
                            </th>

                            <th>
                                <?php
                                $sortKey = 'status';
                                $sortLabel = 'Status';

                                require __DIR__
                                    . '/../components/sort_link.php';
                                ?>
                            </th>

                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img
                                            src="<?= htmlspecialchars(
                                                        $product['image_path'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                            alt="Product image"
                                            style="
                                                width: 50px;
                                                height: 50px;
                                                object-fit: cover;
                                            "
                                            class="rounded border">
                                    <?php else: ?>
                                        <span class="text-muted">
                                            No image
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= htmlspecialchars(
                                            $product['internal_code'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $product['barcode'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $product['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $product['category_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $product['supplier_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $product['unit'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $product['purchase_price'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $product['selling_price'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $product['min_stock'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ((int) $product['is_active'] === 1): ?>
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
                                            href="/products/edit?id=<?= htmlspecialchars(
                                                                        (string) $product['id'],
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        <?php if ((int) $product['is_active'] === 1): ?>
                                            <form
                                                action="/products/deactivate"
                                                method="POST"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to deactivate this product?'
                                                    );
                                                ">
                                                <?= \App\Core\Csrf::field() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= htmlspecialchars(
                                                                (string) $product['id'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>">

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

            <?php
            require __DIR__ . '/../components/pagination.php';
            ?>
        <?php endif; ?>
    </div>
</div>