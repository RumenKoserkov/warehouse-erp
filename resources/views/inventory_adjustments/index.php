<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Inventory Adjustments
        </h1>

        <p class="text-muted mb-0">
            Manual stock increases and decreases.
        </p>
    </div>

    <a
        href="/inventory-adjustments/create"
        class="btn btn-primary"
    >
        New Adjustment
    </a>
</div>

<form
    method="GET"
    action="/inventory-adjustments"
    class="card shadow-sm mb-4"
>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-4">
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
                    placeholder="Reference, reason, warehouse or user..."
                    value="<?= htmlspecialchars(
                        (string) $filters['search'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-lg-3">
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
                >
                    <option value="0">
                        All Warehouses
                    </option>

                    <?php foreach (
                        $warehouses as $warehouse
                    ): ?>
                        <option
                            value="<?= (int) $warehouse['id'] ?>"
                            <?php if (
                                (int) $filters[
                                    'warehouse_id'
                                ] ===
                                (int) $warehouse['id']
                            ): ?>
                                selected
                            <?php endif; ?>
                        >
                            <?= htmlspecialchars(
                                (string) $warehouse['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2">
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
                        All
                    </option>

                    <?php foreach (
                        [
                            'draft' => 'Draft',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ] as $value => $label
                    ): ?>
                        <option
                            value="<?= $value ?>"
                            <?= $filters['status'] ===
                                $value
                                    ? 'selected'
                                    : '' ?>
                        >
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-3">
                <label
                    for="reason_type"
                    class="form-label"
                >
                    Reason
                </label>

                <select
                    id="reason_type"
                    name="reason_type"
                    class="form-select"
                >
                    <option value="">
                        All Reasons
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
                            <?= $filters[
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

            <div class="col-12">
                <button
                    type="submit"
                    class="btn btn-outline-primary"
                >
                    Apply Filters
                </button>

                <a
                    href="/inventory-adjustments"
                    class="btn btn-outline-secondary"
                >
                    Clear
                </a>
            </div>
        </div>
    </div>
</form>

<?php if (empty($adjustments)): ?>
    <div class="alert alert-info">
        No inventory adjustments found.
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Warehouse</th>
                        <th>Reason</th>
                        <th>Items</th>
                        <th>Increase</th>
                        <th>Decrease</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $adjustments as $adjustment
                    ): ?>
                        <tr>
                            <td class="font-monospace fw-semibold">
                                <?= htmlspecialchars(
                                    (string) $adjustment[
                                        'adjustment_number'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $adjustment[
                                        'adjustment_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    <?= htmlspecialchars(
                                        (string) $adjustment[
                                            'warehouse_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                                <div class="small text-muted">
                                    <?= htmlspecialchars(
                                        (string) $adjustment[
                                            'warehouse_code'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reasonTypes[
                                        $adjustment[
                                            'reason_type'
                                        ]
                                    ] ??
                                    (string) $adjustment[
                                        'reason_type'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $adjustment[
                                    'item_count'
                                ] ?>
                            </td>

                            <td class="text-success">
                                +<?= number_format(
                                    (float) $adjustment[
                                        'total_increase'
                                    ],
                                    3
                                ) ?>
                            </td>

                            <td class="text-danger">
                                -<?= number_format(
                                    (float) $adjustment[
                                        'total_decrease'
                                    ],
                                    3
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    $adjustment['status'] ===
                                    'completed'
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-success"
                                    >
                                        Completed
                                    </span>
                                <?php elseif (
                                    $adjustment['status'] ===
                                    'cancelled'
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-danger"
                                    >
                                        Cancelled
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge
                                        text-bg-warning"
                                    >
                                        Draft
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $adjustment[
                                        'created_by_user_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/inventory-adjustments/show?id=<?= (int) $adjustment['id'] ?>"
                                    class="btn btn-sm
                                    btn-outline-primary"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>