<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Sales Returns
        </h1>

        <p class="text-muted mb-0">
            Customer returns and restocked goods.
        </p>
    </div>

    <a
        href="/sales"
        class="btn btn-outline-secondary"
    >
        Open Sales
    </a>
</div>

<form
    method="GET"
    action="/sales-returns"
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
                    placeholder="Return, sale, client, reason or warehouse..."
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

            <div class="col-12">
                <button
                    type="submit"
                    class="btn btn-outline-primary"
                >
                    Apply Filters
                </button>

                <a
                    href="/sales-returns"
                    class="btn btn-outline-secondary"
                >
                    Clear
                </a>
            </div>
        </div>
    </div>
</form>

<?php if (empty($salesReturns)): ?>
    <div class="alert alert-info">
        No sales returns found.
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
                        <th>Sale</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Warehouse</th>
                        <th>Items</th>
                        <th>Returned</th>
                        <th>Restocked</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $salesReturns as
                        $salesReturn
                    ): ?>
                        <?php
                        $clientName = trim(
                            (string) (
                                $salesReturn[
                                    'client_company_name'
                                ] ?? ''
                            )
                        );

                        if ($clientName === '') {
                            $clientName = trim(
                                (string) (
                                    $salesReturn[
                                        'client_name'
                                    ] ?? ''
                                )
                            );
                        }

                        if ($clientName === '') {
                            $clientName =
                                'No Client';
                        }
                        ?>

                        <tr>
                            <td class="font-monospace fw-semibold">
                                <?= htmlspecialchars(
                                    (string) $salesReturn[
                                        'return_number'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/sales/show?id=<?= (int) $salesReturn['sale_id'] ?>"
                                >
                                    <?= htmlspecialchars(
                                        (string) $salesReturn[
                                            'sale_number'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </a>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $salesReturn[
                                        'return_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $clientName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $salesReturn[
                                        'warehouse_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $salesReturn[
                                    'item_count'
                                ] ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $salesReturn[
                                        'returned_quantity'
                                    ],
                                    3
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $salesReturn[
                                        'restocked_quantity'
                                    ],
                                    3
                                ) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= number_format(
                                        (float) $salesReturn[
                                            'total_amount'
                                        ],
                                        2
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?php if (
                                    $salesReturn['status'] ===
                                    'completed'
                                ): ?>
                                    <span class="badge text-bg-success">
                                        Completed
                                    </span>
                                <?php elseif (
                                    $salesReturn['status'] ===
                                    'cancelled'
                                ): ?>
                                    <span class="badge text-bg-danger">
                                        Cancelled
                                    </span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">
                                        Draft
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    href="/sales-returns/show?id=<?= (int) $salesReturn['id'] ?>"
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