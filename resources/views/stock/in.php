<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Stock In</h1>

    <a href="/warehouses" class="btn btn-outline-secondary">
        Back to Warehouses
    </a>
</div>

<div class="row">
    <div class="col-md-7">
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

                <form action="/stock/in/store" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Product *</label>

                        <select name="product_id" class="form-select" required>
                            <option value="">Select product</option>

                            <?php foreach ($products as $product): ?>
                                <option
                                    value="<?= htmlspecialchars((string)$product['id']) ?>"
                                    <?php if ((string)$old['product_id'] === (string)$product['id']): ?>
                                        selected
                                    <?php endif; ?>
                                >
                                    <?= htmlspecialchars($product['internal_code'] . ' - ' . $product['name'] . ' (' . $product['unit'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Warehouse *</label>

                        <select name="warehouse_id" class="form-select" required>
                            <option value="">Select warehouse</option>

                            <?php foreach ($warehouses as $warehouse): ?>
                                <option
                                    value="<?= htmlspecialchars((string)$warehouse['id']) ?>"
                                    <?php if ((string)$old['warehouse_id'] === (string)$warehouse['id']): ?>
                                        selected
                                    <?php endif; ?>
                                >
                                    <?= htmlspecialchars($warehouse['code'] . ' - ' . $warehouse['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantity *</label>

                        <input
                            type="number"
                            step="0.001"
                            name="quantity"
                            class="form-control"
                            value="<?= htmlspecialchars((string)$old['quantity']) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note</label>

                        <textarea
                            name="note"
                            class="form-control"
                            rows="3"
                        ><?= htmlspecialchars((string)$old['note']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Add Stock
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>