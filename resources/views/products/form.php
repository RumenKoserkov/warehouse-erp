<?php
$isEdit = false;
$action = '/products/store';
$buttonText = 'Create Product';

if (isset($product)) {
    $isEdit = true;
    $action = '/products/update';
    $buttonText = 'Update Product';
}
?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($action) ?>" method="POST" enctype="multipart/form-data">
                    <?= \App\Core\Csrf::field() ?>

                    <?php if ($isEdit): ?>
                        <input
                            type="hidden"
                            name="id"
                            value="<?= htmlspecialchars((string)$product['id']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">
                            Product Name *
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars((string)$old['name']) ?>"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Barcode
                        </label>

                        <input
                            type="text"
                            name="barcode"
                            class="form-control"
                            value="<?= htmlspecialchars((string)$old['barcode']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Category *
                        </label>

                        <select
                            name="category_id"
                            class="form-select"
                            required>
                            <option value="">
                                Select category
                            </option>

                            <?php foreach ($categories as $category): ?>
                                <option
                                    value="<?= htmlspecialchars((string)$category['id']) ?>"
                                    <?php if ((string)$old['category_id'] === (string)$category['id']): ?>
                                        selected
                                    <?php endif; ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Supplier
                        </label>

                        <select
                            name="supplier_id"
                            class="form-select">
                            <option value="">
                                No supplier
                            </option>

                            <?php foreach ($suppliers as $supplier): ?>
                                <option
                                    value="<?= htmlspecialchars((string)$supplier['id']) ?>"
                                    <?php if ((string)$old['supplier_id'] === (string)$supplier['id']): ?>
                                        selected
                                    <?php endif; ?>>
                                    <?= htmlspecialchars($supplier['name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Unit *
                        </label>

                        <select
                            name="unit"
                            class="form-select"
                            required>
                            <?php foreach ($units as $unit): ?>
                                <option
                                    value="<?= htmlspecialchars($unit) ?>"
                                    <?php if ((string)$old['unit'] === $unit): ?>
                                        selected
                                    <?php endif; ?>>
                                    <?= htmlspecialchars($unit) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Purchase Price
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="purchase_price"
                                class="form-control"
                                value="<?= htmlspecialchars((string)$old['purchase_price']) ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Selling Price
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="selling_price"
                                class="form-control"
                                value="<?= htmlspecialchars((string)$old['selling_price']) ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Minimum Stock
                            </label>

                            <input
                                type="number"
                                step="0.001"
                                name="min_stock"
                                class="form-control"
                                value="<?= htmlspecialchars((string)$old['min_stock']) ?>">
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"><?= htmlspecialchars((string)$old['description']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Product Image
                        </label>

                        <?php if (isset($product) && !empty($product['image_path'])): ?>
                            <div class="mb-2">
                                <img
                                    src="<?= htmlspecialchars($product['image_path']) ?>"
                                    alt="Product image"
                                    style="max-width: 120px; max-height: 120px;"
                                    class="img-thumbnail">
                            </div>
                        <?php endif; ?>

                        <input
                            type="file"
                            name="image"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp">

                        <div class="form-text">
                            Allowed formats: jpg, jpeg, png, webp. Max size: 2MB.
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input
                            type="checkbox"
                            name="is_active"
                            class="form-check-input"
                            value="1"
                            <?php if ((string)$old['is_active'] === '1'): ?>
                                checked
                            <?php endif; ?>>

                        <label class="form-check-label">
                            Active product
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        <?= htmlspecialchars($buttonText) ?>
                    </button>

                </form>

            </div>
        </div>
    </div>
</div>