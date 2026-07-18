<div class="row justify-content-center">
    <div class="col-xl-8">
        <div
            class="d-flex justify-content-between
            align-items-start mb-4"
        >
            <div>
                <h1 class="h3 mb-1">
                    New Inventory Adjustment
                </h1>

                <p class="text-muted mb-0">
                    Create a draft manual stock
                    correction.
                </p>
            </div>

            <a
                href="/inventory-adjustments"
                class="btn btn-outline-secondary"
            >
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

        <div class="alert alert-info">
            This creates a draft only. Stock will
            change after products are added and the
            adjustment is completed.
        </div>

        <form
            method="POST"
            action="/inventory-adjustments/store"
        >
            <?= \App\Core\Csrf::field() ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label
                                for="warehouse_id"
                                class="form-label"
                            >
                                Warehouse
                            </label>

                            <select
                                id="warehouse_id"
                                name="warehouse_id"
                                class="form-select"
                                required
                            >
                                <option value="">
                                    Select Warehouse
                                </option>

                                <?php foreach (
                                    $warehouses as
                                    $warehouse
                                ): ?>
                                    <option
                                        value="<?= (int) $warehouse['id'] ?>"
                                        <?= (int) $old[
                                            'warehouse_id'
                                        ] ===
                                        (int) $warehouse[
                                            'id'
                                        ]
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            (string) $warehouse[
                                                'name'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                        —
                                        <?= htmlspecialchars(
                                            (string) $warehouse[
                                                'code'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label
                                for="adjustment_date"
                                class="form-label"
                            >
                                Adjustment Date
                            </label>

                            <input
                                type="date"
                                id="adjustment_date"
                                name="adjustment_date"
                                class="form-control"
                                max="<?= date('Y-m-d') ?>"
                                required
                                value="<?= htmlspecialchars(
                                    (string) $old[
                                        'adjustment_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-5">
                            <label
                                for="reason_type"
                                class="form-label"
                            >
                                Reason Type
                            </label>

                            <select
                                id="reason_type"
                                name="reason_type"
                                class="form-select"
                                required
                            >
                                <option value="">
                                    Select Reason
                                </option>

                                <?php foreach (
                                    $reasonTypes as
                                    $value => $label
                                ): ?>
                                    <option
                                        value="<?= htmlspecialchars(
                                            $value,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= $old[
                                            'reason_type'
                                        ] === $value
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $label,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-7">
                            <label
                                for="reason_description"
                                class="form-label"
                            >
                                Reason Description
                            </label>

                            <input
                                type="text"
                                id="reason_description"
                                name="reason_description"
                                class="form-control"
                                maxlength="500"
                                required
                                placeholder="Explain why the stock must be corrected..."
                                value="<?= htmlspecialchars(
                                    (string) $old[
                                        'reason_description'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-12">
                            <label
                                for="notes"
                                class="form-label"
                            >
                                Additional Notes
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                class="form-control"
                                rows="4"
                                maxlength="2000"
                            ><?= htmlspecialchars(
                                (string) $old['notes'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary mt-4"
                    >
                        Create Draft
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>