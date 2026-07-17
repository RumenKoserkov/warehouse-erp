<div class="row justify-content-center">
    <div class="col-xl-8">
        <div
            class="d-flex justify-content-between
            align-items-start mb-4">
            <div>
                <h1 class="h3 mb-1">
                    New Inventory Count
                </h1>

                <p class="text-muted mb-0">
                    Create a physical stock
                    count for one warehouse.
                </p>
            </div>

            <a
                href="/inventory-counts"
                class="btn btn-outline-secondary">
                Back
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (
                        $errors as $error
                    ): ?>
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

        <div class="alert alert-warning">
            After the inventory count is created,
            avoid stock in, stock out, transfers,
            sales and purchases for the selected
            warehouse until the count is completed.
        </div>

        <form
            method="POST"
            action="/inventory-counts/store">
            <?= \App\Core\Csrf::field() ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label
                                for="warehouse_id"
                                class="form-label">
                                Warehouse
                            </label>

                            <select
                                id="warehouse_id"
                                name="warehouse_id"
                                class="form-select"
                                required>
                                <option value="">
                                    Select Warehouse
                                </option>

                                <?php foreach (
                                    $warehouses as
                                    $warehouse
                                ): ?>
                                    <option
                                        value="<?= (int) $warehouse['id'] ?>"
                                        <?php if (
                                            (int) $old['warehouse_id'] ===
                                            (int) $warehouse['id']
                                        ): ?>
                                        selected
                                        <?php endif; ?>>
                                        <?= htmlspecialchars(
                                            (string) $warehouse['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                        —
                                        <?= htmlspecialchars(
                                            (string) $warehouse['code'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label
                                for="count_date"
                                class="form-label">
                                Count Date
                            </label>

                            <input
                                type="date"
                                id="count_date"
                                name="count_date"
                                class="form-control"
                                max="<?= date('Y-m-d') ?>"
                                required
                                value="<?= htmlspecialchars(
                                            (string) $old['count_date'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">
                        </div>

                        <div class="col-12">
                            <label
                                for="notes"
                                class="form-label">
                                Notes
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                class="form-control"
                                rows="4"
                                maxlength="2000"
                                placeholder="Reason, responsible team or counting instructions..."><?= htmlspecialchars(
                                                                                                        (string) $old['notes'],
                                                                                                        ENT_QUOTES,
                                                                                                        'UTF-8'
                                                                                                    ) ?></textarea>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary mt-4">
                        Create Inventory Count
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>