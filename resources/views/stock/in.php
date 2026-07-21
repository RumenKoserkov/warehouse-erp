<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Stock In</h1>

    <a
        href="/warehouses"
        class="btn btn-outline-secondary"
    >
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
                                <li>
                                    <?= htmlspecialchars(
                                        (string) $error,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form
                    action="/stock/in/store"
                    method="POST"
                >
                    <?= \App\Core\Csrf::field() ?>

                    <div class="mb-3">
                        <label
                            for="product_id"
                            class="form-label"
                        >
                            Product *
                        </label>

                        <select
                            id="product_id"
                            name="product_id"
                            class="form-select"
                            required
                        >
                            <option value="">
                                Select product
                            </option>

                            <?php foreach ($products as $product): ?>
                                <option
                                    value="<?= htmlspecialchars(
                                        (string) $product['id'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    <?php if (
                                        (string) (
                                            $old['product_id'] ?? ''
                                        ) ===
                                        (string) $product['id']
                                    ): ?>
                                        selected
                                    <?php endif; ?>
                                >
                                    <?= htmlspecialchars(
                                        (string) (
                                            $product['internal_code'] .
                                            ' - ' .
                                            $product['name'] .
                                            ' (' .
                                            $product['unit'] .
                                            ')'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label
                            for="warehouse_id"
                            class="form-label"
                        >
                            Warehouse *
                        </label>

                        <select
                            id="warehouse_id"
                            name="warehouse_id"
                            class="form-select"
                            required
                        >
                            <option value="">
                                Select warehouse
                            </option>

                            <?php foreach ($warehouses as $warehouse): ?>
                                <option
                                    value="<?= htmlspecialchars(
                                        (string) $warehouse['id'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    <?php if (
                                        (string) (
                                            $old['warehouse_id'] ?? ''
                                        ) ===
                                        (string) $warehouse['id']
                                    ): ?>
                                        selected
                                    <?php endif; ?>
                                >
                                    <?= htmlspecialchars(
                                        (string) (
                                            $warehouse['code'] .
                                            ' - ' .
                                            $warehouse['name']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label
                            for="quantity"
                            class="form-label"
                        >
                            Quantity *
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            class="form-control"
                            min="0.001"
                            step="0.001"
                            required
                            value="<?= htmlspecialchars(
                                (string) (
                                    $old['quantity'] ?? ''
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label
                            for="unit_cost"
                            class="form-label"
                        >
                            Unit Cost *
                        </label>

                        <input
                            type="number"
                            id="unit_cost"
                            name="unit_cost"
                            class="form-control"
                            min="0"
                            step="0.0001"
                            required
                            value="<?= htmlspecialchars(
                                (string) (
                                    $old['unit_cost'] ?? ''
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                        <div class="form-text">
                            Cost without VAT for one unit.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label
                            for="note"
                            class="form-label"
                        >
                            Note
                        </label>

                        <textarea
                            id="note"
                            name="note"
                            class="form-control"
                            rows="3"
                        ><?= htmlspecialchars(
                            (string) (
                                $old['note'] ?? ''
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Add Stock
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>