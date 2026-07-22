<?php

declare(strict_types=1);

use App\Services\AuthService;

$authService = new AuthService();

$canManageProducts =
    $authService->hasAnyRole([
        'administrator',
        'manager',
    ]);

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Products</h1>

        <div class="text-muted">
            Found:
            <strong><?= (int) $paginator->total() ?></strong>
            products
        </div>
    </div>

    <div class="d-flex gap-2">
        <?php
        $csvExportPath =
            '/exports/products.csv';

        require __DIR__ .
            '/../partials/csv_export_button.php';
        ?>

        <?php if ($canManageProducts): ?>
            <a
                href="/products/create"
                class="btn btn-primary"
            >
                Create Product
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/products">
            <input
                type="hidden"
                name="sort"
                value="<?= htmlspecialchars(
                    $sorter->key(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <input
                type="hidden"
                name="direction"
                value="<?= htmlspecialchars(
                    $sorter->direction(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <div class="row g-3">
                <div class="col-lg-4 col-md-6">
                    <label
                        for="search"
                        class="form-label"
                    >
                        Search
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        placeholder="Name, code, barcode, category or supplier..."
                        value="<?= htmlspecialchars(
                            $search,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div class="col-lg-2 col-md-6">
                    <label
                        for="category_id"
                        class="form-label"
                    >
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        class="form-select"
                    >
                        <option value="">
                            All categories
                        </option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?php if (
                                    (string) $filters['category_id'] ===
                                    (string) $category['id']
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars(
                                    $category['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label
                        for="supplier_id"
                        class="form-label"
                    >
                        Supplier
                    </label>

                    <select
                        id="supplier_id"
                        name="supplier_id"
                        class="form-select"
                    >
                        <option value="">
                            All suppliers
                        </option>

                        <?php foreach ($suppliers as $supplier): ?>
                            <option
                                value="<?= (int) $supplier['id'] ?>"
                                <?php if (
                                    (string) $filters['supplier_id'] ===
                                    (string) $supplier['id']
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars(
                                    $supplier['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label
                        for="unit"
                        class="form-label"
                    >
                        Unit
                    </label>

                    <select
                        id="unit"
                        name="unit"
                        class="form-select"
                    >
                        <option value="">
                            All units
                        </option>

                        <?php foreach ($units as $unit): ?>
                            <option
                                value="<?= htmlspecialchars(
                                    $unit,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?php if (
                                    $filters['unit'] === $unit
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars(
                                    ucfirst($unit),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label
                        for="status"
                        class="form-label"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-select"
                    >
                        <option value="">
                            All statuses
                        </option>

                        <option
                            value="active"
                            <?php if (
                                $filters['status'] === 'active'
                            ): ?>
                                selected
                            <?php endif; ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?php if (
                                $filters['status'] === 'inactive'
                            ): ?>
                                selected
                            <?php endif; ?>
                        >
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3">
                    <label
                        for="min_price"
                        class="form-label"
                    >
                        Min selling price
                    </label>

                    <input
                        type="number"
                        id="min_price"
                        name="min_price"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars(
                            (string) $filters['min_price'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div class="col-lg-2 col-md-3">
                    <label
                        for="max_price"
                        class="form-label"
                    >
                        Max selling price
                    </label>

                    <input
                        type="number"
                        id="max_price"
                        name="max_price"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars(
                            (string) $filters['max_price'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div
                    class="col-lg-8 col-md-6
                    d-flex align-items-end gap-2"
                >
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Apply Filters
                    </button>

                    <a
                        href="/products"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($products)): ?>
    <div class="alert alert-info">
        No products match the selected filters.
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="
                        table table-striped table-hover
                        align-middle mb-0
                    "
                >
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

                            <?php if ($canManageProducts): ?>
                                <th>
                                    <?php
                                    $sortKey = 'purchase_price';
                                    $sortLabel = 'Purchase';

                                    require __DIR__
                                        . '/../components/sort_link.php';
                                    ?>
                                </th>
                            <?php endif; ?>

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

                            <?php if ($canManageProducts): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if (
                                        !empty($product['image_path'])
                                    ): ?>
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
                                            class="rounded border"
                                        >
                                    <?php else: ?>
                                        <span class="text-muted">
                                            No image
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span
                                        class="
                                            badge text-bg-secondary
                                        "
                                    >
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

                                <?php if ($canManageProducts): ?>
                                    <td>
                                        <?= htmlspecialchars(
                                            (string) $product[
                                                'purchase_price'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $product[
                                            'selling_price'
                                        ],
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
                                    <?php if (
                                        (int) $product['is_active'] === 1
                                    ): ?>
                                        <span
                                            class="
                                                badge text-bg-success
                                            "
                                        >
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="
                                                badge text-bg-danger
                                            "
                                        >
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($canManageProducts): ?>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a
                                                href="/products/edit?id=<?= htmlspecialchars(
                                                    (string) $product['id'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                class="
                                                    btn btn-sm
                                                    btn-outline-primary
                                                "
                                            >
                                                Edit
                                            </a>

                                            <?php if (
                                                (int) $product[
                                                    'is_active'
                                                ] === 1
                                            ): ?>
                                                <form
                                                    action="/products/deactivate"
                                                    method="POST"
                                                    onsubmit="
                                                        return confirm(
                                                            'Are you sure you want to deactivate this product?'
                                                        );
                                                    "
                                                >
                                                    <?= \App\Core\Csrf::field() ?>

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= htmlspecialchars(
                                                            (string) $product[
                                                                'id'
                                                            ],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="
                                                            btn btn-sm
                                                            btn-outline-danger
                                                        "
                                                    >
                                                        Deactivate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            require __DIR__
                . '/../components/pagination.php';
            ?>
        </div>
    </div>
<?php endif; ?>