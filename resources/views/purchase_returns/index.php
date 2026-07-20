<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Purchase Returns
        </h1>

        <p class="text-muted mb-0">
            Products returned to suppliers.
        </p>
    </div>

    <a
        href="/purchases"
        class="btn btn-outline-secondary"
    >
        Open Purchases
    </a>
</div>

<form
    method="GET"
    action="/purchase-returns"
    class="card shadow-sm mb-4"
>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-6">
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
                    placeholder="Return, purchase, supplier, reason..."
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
                            <?= (int) $filters[
                                'warehouse_id'
                            ] ===
                            (int) $warehouse['id']
                                ? 'selected'
                                : '' ?>
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

            <div class="col-lg-3">
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

            <div class="col-12">
                <button
                    class="btn btn-outline-primary"
                >
                    Apply Filters
                </button>

                <a
                    href="/purchase-returns"
                    class="btn btn-outline-secondary"
                >
                    Clear
                </a>
            </div>
        </div>
    </div>
</form>

<?php if (empty($purchaseReturns)): ?>
    <div class="alert alert-info">
        No purchase returns found.
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
                        <th>Return</th>
                        <th>Purchase</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>Items</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $purchaseReturns as
                        $purchaseReturn
                    ): ?>
                        <?php
                        $supplierName = trim(
                            (string) (
                                $purchaseReturn[
                                    'supplier_company_name'
                                ] ?? ''
                            )
                        );

                        if ($supplierName === '') {
                            $supplierName = trim(
                                (string) (
                                    $purchaseReturn[
                                        'supplier_name'
                                    ] ?? ''
                                )
                            );
                        }

                        if ($supplierName === '') {
                            $supplierName =
                                'No Supplier';
                        }

                        $status =
                            (string) $purchaseReturn[
                                'status'
                            ];

                        $statusClass = match ($status) {
                            'completed' =>
                                'text-bg-success',

                            'cancelled' =>
                                'text-bg-danger',

                            default =>
                                'text-bg-warning',
                        };
                        ?>

                        <tr>
                            <td class="font-monospace">
                                <?= htmlspecialchars(
                                    (string) $purchaseReturn[
                                        'return_number'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/purchases/show?id=<?= (int) $purchaseReturn['purchase_id'] ?>"
                                >
                                    <?= htmlspecialchars(
                                        (string) $purchaseReturn[
                                            'purchase_number'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </a>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $purchaseReturn[
                                        'return_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $supplierName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $purchaseReturn[
                                        'warehouse_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $purchaseReturn[
                                    'item_count'
                                ] ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $purchaseReturn[
                                        'returned_quantity'
                                    ],
                                    3
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $purchaseReturn[
                                        'total_amount'
                                    ],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <span
                                    class="badge <?= $statusClass ?>"
                                >
                                    <?= htmlspecialchars(
                                        ucfirst($status),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <a
                                    href="/purchase-returns/show?id=<?= (int) $purchaseReturn['id'] ?>"
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