<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Inventory Counts
        </h1>

        <p class="text-muted mb-0">
            Physical warehouse inventory
            and stock adjustments.
        </p>
    </div>

    <?php if ($canManage): ?>
        <a
            href="/inventory-counts/create"
            class="btn btn-primary"
        >
            New Inventory Count
        </a>
    <?php endif; ?>
</div>

<form
    method="GET"
    action="/inventory-counts"
    class="card shadow-sm mb-4"
>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-5">
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
                    placeholder="Count number, warehouse or user..."
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
                        All Statuses
                    </option>

                    <option
                        value="draft"
                        <?= $filters['status'] ===
                            'draft'
                                ? 'selected'
                                : '' ?>
                    >
                        Draft
                    </option>

                    <option
                        value="completed"
                        <?= $filters['status'] ===
                            'completed'
                                ? 'selected'
                                : '' ?>
                    >
                        Completed
                    </option>

                    <option
                        value="cancelled"
                        <?= $filters['status'] ===
                            'cancelled'
                                ? 'selected'
                                : '' ?>
                    >
                        Cancelled
                    </option>
                </select>
            </div>

            <div
                class="col-lg-2 d-flex
                align-items-end"
            >
                <button
                    type="submit"
                    class="btn btn-outline-primary w-100"
                >
                    Filter
                </button>
            </div>
        </div>
    </div>
</form>

<?php if (empty($inventoryCounts)): ?>
    <div class="alert alert-info">
        No inventory counts found.
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
                        <th>Warehouse</th>
                        <th>Count Date</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Differences</th>
                        <th>Created By</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $inventoryCounts as
                        $inventoryCount
                    ): ?>
                        <tr>
                            <td>
                                <strong
                                    class="font-monospace"
                                >
                                    <?= htmlspecialchars(
                                        (string) $inventoryCount[
                                            'count_number'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    <?= htmlspecialchars(
                                        (string) $inventoryCount[
                                            'warehouse_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                                <div
                                    class="small text-muted"
                                >
                                    <?= htmlspecialchars(
                                        (string) $inventoryCount[
                                            'warehouse_code'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $inventoryCount[
                                        'count_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    $inventoryCount[
                                        'status'
                                    ] === 'completed'
                                ): ?>
                                    <span
                                        class="badge text-bg-success"
                                    >
                                        Completed
                                    </span>
                                <?php elseif (
                                    $inventoryCount[
                                        'status'
                                    ] === 'cancelled'
                                ): ?>
                                    <span
                                        class="badge text-bg-danger"
                                    >
                                        Cancelled
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge text-bg-warning"
                                    >
                                        Draft
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= (int) $inventoryCount[
                                    'counted_items'
                                ] ?>
                                /
                                <?= (int) $inventoryCount[
                                    'total_items'
                                ] ?>
                            </td>

                            <td>
                                <?php if (
                                    (int) $inventoryCount[
                                        'difference_items'
                                    ] > 0
                                ): ?>
                                    <span
                                        class="badge text-bg-warning"
                                    >
                                        <?= (int) $inventoryCount[
                                            'difference_items'
                                        ] ?>
                                    </span>
                                <?php else: ?>
                                    0
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $inventoryCount[
                                        'created_by_user_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/inventory-counts/show?id=<?= (int) $inventoryCount['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
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