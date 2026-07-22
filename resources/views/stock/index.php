<?php

$showCosts =
    (bool) ($canViewCosts ?? false);
?>

<div
    class="d-flex flex-column flex-lg-row
    justify-content-between
    align-items-lg-center gap-3 mb-4"
>
    <h1 class="mb-0">
        Stock Levels
    </h1>

    <div class="d-flex flex-wrap gap-2">
        <?php
        $csvExportPath =
            '/exports/stock.csv';

        require __DIR__ .
            '/../partials/csv_export_button.php';
        ?>

        <a
            href="/stock/in"
            class="btn btn-success"
        >
            Stock In
        </a>

        <a
            href="/stock/out"
            class="btn btn-danger"
        >
            Stock Out
        </a>

        <a
            href="/stock/transfer"
            class="btn btn-primary"
        >
            Transfer
        </a>
    </div>
</div>

<form
    method="GET"
    action="/stock"
    class="mb-3"
>
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by product, code, barcode or warehouse..."
            value="<?= htmlspecialchars(
                (string) $search,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <button
            type="submit"
            class="btn btn-outline-secondary"
        >
            Search
        </button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($stockLevels)): ?>
            <p class="text-muted mb-0">
                No stock records found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table
                    class="table table-striped
                    table-hover align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <th>Product Code</th>
                            <th>Barcode</th>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th>Quantity</th>
                            <th>Unit</th>

                            <?php if ($showCosts): ?>
                                <th>
                                    Average Unit Cost
                                </th>

                                <th>
                                    Inventory Value
                                </th>
                            <?php endif; ?>

                            <th>Min Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $stockLevels as $stock
                        ): ?>
                            <?php
                            $quantity =
                                (float) $stock[
                                    'quantity'
                                ];

                            $minimumStock =
                                (float) $stock[
                                    'min_stock'
                                ];

                            $averageUnitCost =
                                (float) (
                                    $stock[
                                        'average_unit_cost'
                                    ] ?? 0
                                );

                            $inventoryValue =
                                (float) (
                                    $stock[
                                        'inventory_value'
                                    ] ?? 0
                                );
                            ?>

                            <tr>
                                <td>
                                    <span
                                        class="badge
                                        text-bg-secondary"
                                    >
                                        <?= htmlspecialchars(
                                            (string) $stock[
                                                'internal_code'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td class="font-monospace">
                                    <?php if (
                                        trim(
                                            (string) $stock[
                                                'barcode'
                                            ]
                                        ) !== ''
                                    ): ?>
                                        <?= htmlspecialchars(
                                            (string) $stock[
                                                'barcode'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            —
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $stock[
                                            'product_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    <?php if (
                                        (int) $stock[
                                            'product_is_active'
                                        ] === 0
                                    ): ?>
                                        <span
                                            class="badge
                                            text-bg-warning ms-1"
                                        >
                                            Inactive product
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $stock[
                                            'warehouse_code'
                                        ] .
                                        ' - ' .
                                        (string) $stock[
                                            'warehouse_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    <?php if (
                                        (int) $stock[
                                            'warehouse_is_active'
                                        ] === 0
                                    ): ?>
                                        <span
                                            class="badge
                                            text-bg-warning ms-1"
                                        >
                                            Inactive warehouse
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= number_format(
                                            $quantity,
                                            3,
                                            '.',
                                            ''
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $stock[
                                            'unit'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <?php if ($showCosts): ?>
                                    <td>
                                        <?= number_format(
                                            $averageUnitCost,
                                            4,
                                            '.',
                                            ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format(
                                                $inventoryValue,
                                                4,
                                                '.',
                                                ''
                                            ) ?>
                                        </strong>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <?= number_format(
                                        $minimumStock,
                                        3,
                                        '.',
                                        ''
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        $quantity <= 0
                                    ): ?>
                                        <span
                                            class="badge
                                            text-bg-danger"
                                        >
                                            Out of Stock
                                        </span>
                                    <?php elseif (
                                        $quantity <=
                                        $minimumStock
                                    ): ?>
                                        <span
                                            class="badge
                                            text-bg-warning"
                                        >
                                            Low Stock
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="badge
                                            text-bg-success"
                                        >
                                            OK
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>