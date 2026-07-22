<?php

$escape = static function (
    mixed $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$money = static function (
    mixed $value
): string {
    return number_format(
        (float) $value,
        2,
        '.',
        ','
    );
};

$quantity = static function (
    mixed $value
): string {
    return number_format(
        (float) $value,
        3,
        '.',
        ','
    );
};

$margin = static function (
    mixed $value
): string {
    if ($value === null) {
        return '—';
    }

    return number_format(
        (float) $value,
        2,
        '.',
        ','
    ) . '%';
};

$profitClass = static function (
    mixed $value
): string {
    return (float) $value >= 0
        ? 'text-success'
        : 'text-danger';
};

$summary = $report['summary'];

$hasUncosted =
    (int) $summary[
        'uncosted_event_count'
    ] > 0;
?>

<div
    class="d-flex flex-column flex-lg-row
    justify-content-between align-items-lg-start
    gap-3 mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Profit Reports
        </h1>

        <p class="text-muted mb-0">
            Revenue, cost of goods sold,
            gross profit and margin.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <?php
        $csvExportPath =
            '/exports/profit.csv';

        $csvExportLabel =
            'Export Profit CSV';

        require __DIR__ .
            '/../partials/csv_export_button.php';
        ?>

        <a
            href="/sales/report"
            class="btn btn-outline-secondary"
        >
            Sales Report
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach (
                $errors as $error
            ): ?>
                <li>
                    <?= $escape($error) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($hasUncosted): ?>
    <div class="alert alert-warning">
        <strong>
            Incomplete historical cost data.
        </strong>

        <?= (int) $summary[
            'uncosted_event_count'
        ] ?>
        report row(s) do not have a reliable
        cost snapshot.

        Net Revenue includes these rows, but
        Known COGS and Known Gross Profit do not.
    </div>
<?php endif; ?>

<form
    method="GET"
    action="/reports/profit"
    class="card shadow-sm mb-4"
>
    <div class="card-header">
        <h2 class="h5 mb-0">
            Report Filters
        </h2>
    </div>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 col-xl-2">
                <label
                    for="date_from"
                    class="form-label"
                >
                    From
                </label>

                <input
                    type="date"
                    id="date_from"
                    name="date_from"
                    class="form-control"
                    required
                    value="<?= $escape(
                        $filters['date_from']
                    ) ?>"
                >
            </div>

            <div class="col-md-3 col-xl-2">
                <label
                    for="date_to"
                    class="form-label"
                >
                    To
                </label>

                <input
                    type="date"
                    id="date_to"
                    name="date_to"
                    class="form-control"
                    required
                    value="<?= $escape(
                        $filters['date_to']
                    ) ?>"
                >
            </div>

            <div class="col-md-3 col-xl-2">
                <label
                    for="grouping"
                    class="form-label"
                >
                    Grouping
                </label>

                <select
                    id="grouping"
                    name="grouping"
                    class="form-select"
                >
                    <option
                        value="daily"
                        <?= $filters[
                            'grouping'
                        ] === 'daily'
                            ? 'selected'
                            : '' ?>
                    >
                        Daily
                    </option>

                    <option
                        value="monthly"
                        <?= $filters[
                            'grouping'
                        ] === 'monthly'
                            ? 'selected'
                            : '' ?>
                    >
                        Monthly
                    </option>
                </select>
            </div>

            <div class="col-md-3 col-xl-3">
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
                            <?= $escape(
                                $warehouse['name']
                            ) ?>

                            <?php if (
                                (int) $warehouse[
                                    'is_active'
                                ] !== 1
                            ): ?>
                                — Inactive
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 col-xl-3">
                <label
                    for="client_id"
                    class="form-label"
                >
                    Client
                </label>

                <select
                    id="client_id"
                    name="client_id"
                    class="form-select"
                >
                    <option value="0">
                        All Clients
                    </option>

                    <?php foreach (
                        $clients as $client
                    ): ?>
                        <?php
                        $clientName = trim(
                            (string) (
                                $client[
                                    'company_name'
                                ] ?? ''
                            )
                        );

                        if ($clientName === '') {
                            $clientName =
                                (string) $client[
                                    'name'
                                ];
                        }
                        ?>

                        <option
                            value="<?= (int) $client['id'] ?>"
                            <?= (int) $filters[
                                'client_id'
                            ] ===
                            (int) $client['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= $escape(
                                $clientName
                            ) ?>

                            <?php if (
                                (int) $client[
                                    'is_active'
                                ] !== 1
                            ): ?>
                                — Inactive
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 col-xl-3">
                <label
                    for="category_id"
                    class="form-label"
                >
                    Category
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    class="form-select"
                >
                    <option value="0">
                        All Categories
                    </option>

                    <?php foreach (
                        $categories as $category
                    ): ?>
                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (int) $filters[
                                'category_id'
                            ] ===
                            (int) $category['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= $escape(
                                $category['name']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 col-xl-3">
                <label
                    for="product_id"
                    class="form-label"
                >
                    Product
                </label>

                <select
                    id="product_id"
                    name="product_id"
                    class="form-select"
                >
                    <option value="0">
                        All Products
                    </option>

                    <?php foreach (
                        $products as $product
                    ): ?>
                        <option
                            value="<?= (int) $product['id'] ?>"
                            <?= (int) $filters[
                                'product_id'
                            ] ===
                            (int) $product['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= $escape(
                                $product['name']
                            ) ?>
                            —
                            <?= $escape(
                                $product[
                                    'internal_code'
                                ]
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 col-xl-3">
                <label
                    for="cost_status"
                    class="form-label"
                >
                    Cost Status
                </label>

                <select
                    id="cost_status"
                    name="cost_status"
                    class="form-select"
                >
                    <option
                        value="all"
                        <?= $filters[
                            'cost_status'
                        ] === 'all'
                            ? 'selected'
                            : '' ?>
                    >
                        All Rows
                    </option>

                    <option
                        value="costed"
                        <?= $filters[
                            'cost_status'
                        ] === 'costed'
                            ? 'selected'
                            : '' ?>
                    >
                        Costed Only
                    </option>

                    <option
                        value="uncosted"
                        <?= $filters[
                            'cost_status'
                        ] === 'uncosted'
                            ? 'selected'
                            : '' ?>
                    >
                        Missing Cost Only
                    </option>
                </select>
            </div>

            <div class="col-md-9 col-xl-6">
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
                    maxlength="255"
                    placeholder="Sale, return, product, client or warehouse..."
                    value="<?= $escape(
                        $filters['search']
                    ) ?>"
                >
            </div>

            <div class="col-12">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Apply Filters
                </button>

                <a
                    href="/reports/profit"
                    class="btn btn-outline-secondary"
                >
                    Clear
                </a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Net Revenue
                </div>

                <div class="fs-3 fw-bold">
                    <?= $money(
                        $summary[
                            'net_revenue'
                        ]
                    ) ?>
                </div>

                <div class="small text-muted">
                    After completed returns
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Known COGS
                </div>

                <div class="fs-3 fw-bold">
                    <?= $money(
                        $summary[
                            'known_cogs'
                        ]
                    ) ?>
                </div>

                <div class="small text-muted">
                    Costed rows only
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Known Gross Profit
                </div>

                <div
                    class="fs-3 fw-bold <?= $profitClass(
                        $summary[
                            'known_gross_profit'
                        ]
                    ) ?>"
                >
                    <?= $money(
                        $summary[
                            'known_gross_profit'
                        ]
                    ) ?>
                </div>

                <div class="small text-muted">
                    Costed revenue minus known COGS
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Known Gross Margin
                </div>

                <div
                    class="fs-3 fw-bold <?= $profitClass(
                        $summary[
                            'known_margin_percent'
                        ] ?? 0
                    ) ?>"
                >
                    <?= $margin(
                        $summary[
                            'known_margin_percent'
                        ]
                    ) ?>
                </div>

                <div class="small text-muted">
                    Costed rows only
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Sales
                </div>

                <div class="fs-4 fw-bold">
                    <?= (int) $summary[
                        'sale_count'
                    ] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Returns
                </div>

                <div class="fs-4 fw-bold">
                    <?= (int) $summary[
                        'return_count'
                    ] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Sold Quantity
                </div>

                <div class="fs-4 fw-bold">
                    <?= $quantity(
                        $summary[
                            'sold_quantity'
                        ]
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Returned Quantity
                </div>

                <div class="fs-4 fw-bold">
                    <?= $quantity(
                        $summary[
                            'returned_quantity'
                        ]
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Return Value
                </div>

                <div class="fs-4 fw-bold">
                    <?= $money(
                        $summary[
                            'return_revenue'
                        ]
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Uncosted Rows
                </div>

                <div
                    class="fs-4 fw-bold <?= $hasUncosted
                        ? 'text-warning'
                        : 'text-success' ?>"
                >
                    <?= (int) $summary[
                        'uncosted_event_count'
                    ] ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Profit by
            <?= $filters[
                'grouping'
            ] === 'monthly'
                ? 'Month'
                : 'Day' ?>
        </h2>
    </div>

    <?php if (
        empty($report['periods'])
    ): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                No profit data was found for
                the selected filters.
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Net Revenue</th>
                        <th>Known COGS</th>
                        <th>Known Profit</th>
                        <th>Known Margin</th>
                        <th>Sold</th>
                        <th>Returned</th>
                        <th>Uncosted</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $report['periods']
                        as $period
                    ): ?>
                        <tr>
                            <td class="fw-semibold">
                                <?= $escape(
                                    $period['label']
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $period[
                                        'net_revenue'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $period[
                                        'known_cogs'
                                    ]
                                ) ?>
                            </td>

                            <td
                                class="<?= $profitClass(
                                    $period[
                                        'known_gross_profit'
                                    ]
                                ) ?>"
                            >
                                <strong>
                                    <?= $money(
                                        $period[
                                            'known_gross_profit'
                                        ]
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $margin(
                                    $period[
                                        'known_margin_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $quantity(
                                    $period[
                                        'sold_quantity'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $quantity(
                                    $period[
                                        'returned_quantity'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $period[
                                    'uncosted_event_count'
                                ] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Product Profitability
        </h2>
    </div>

    <?php if (
        empty($report['products'])
    ): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                No product profitability data.
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Sold</th>
                        <th>Returned</th>
                        <th>Net Revenue</th>
                        <th>Known COGS</th>
                        <th>Known Profit</th>
                        <th>Margin</th>
                        <th>Uncosted</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        array_slice(
                            $report['products'],
                            0,
                            50
                        ) as $product
                    ): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= $escape(
                                        $product[
                                            'product_name'
                                        ]
                                    ) ?>
                                </strong>

                                <div
                                    class="small text-muted
                                    font-monospace"
                                >
                                    <?= $escape(
                                        $product[
                                            'product_internal_code'
                                        ]
                                    ) ?>
                                </div>
                            </td>

                            <td>
                                <?= $escape(
                                    $product[
                                        'category_name'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $quantity(
                                    $product[
                                        'sold_quantity'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $quantity(
                                    $product[
                                        'returned_quantity'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $product[
                                        'net_revenue'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $product[
                                        'known_cogs'
                                    ]
                                ) ?>
                            </td>

                            <td
                                class="<?= $profitClass(
                                    $product[
                                        'known_gross_profit'
                                    ]
                                ) ?>"
                            >
                                <strong>
                                    <?= $money(
                                        $product[
                                            'known_gross_profit'
                                        ]
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $margin(
                                    $product[
                                        'known_margin_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $product[
                                    'uncosted_event_count'
                                ] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (
    !empty(
        $report['loss_products']
    )
): ?>
    <div class="card border-danger mb-4">
        <div class="card-header text-danger">
            <h2 class="h5 mb-0">
                Loss-Making Products
            </h2>
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Net Revenue</th>
                        <th>Known COGS</th>
                        <th>Known Loss</th>
                        <th>Margin</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        array_slice(
                            $report[
                                'loss_products'
                            ],
                            0,
                            20
                        ) as $product
                    ): ?>
                        <tr>
                            <td>
                                <?= $escape(
                                    $product[
                                        'product_name'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $product[
                                        'net_revenue'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $product[
                                        'known_cogs'
                                    ]
                                ) ?>
                            </td>

                            <td class="text-danger fw-bold">
                                <?= $money(
                                    $product[
                                        'known_gross_profit'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $margin(
                                    $product[
                                        'known_margin_percent'
                                    ]
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    Profit by Client
                </h2>
            </div>

            <div class="table-responsive">
                <table
                    class="table table-hover
                    align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Revenue</th>
                            <th>Profit</th>
                            <th>Margin</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            array_slice(
                                $report['clients'],
                                0,
                                30
                            ) as $client
                        ): ?>
                            <tr>
                                <td>
                                    <?= $escape(
                                        $client[
                                            'client_name'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= $money(
                                        $client[
                                            'net_revenue'
                                        ]
                                    ) ?>
                                </td>

                                <td
                                    class="<?= $profitClass(
                                        $client[
                                            'known_gross_profit'
                                        ]
                                    ) ?>"
                                >
                                    <?= $money(
                                        $client[
                                            'known_gross_profit'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= $margin(
                                        $client[
                                            'known_margin_percent'
                                        ]
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    Profit by Warehouse
                </h2>
            </div>

            <div class="table-responsive">
                <table
                    class="table table-hover
                    align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <th>Warehouse</th>
                            <th>Revenue</th>
                            <th>Profit</th>
                            <th>Margin</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $report['warehouses']
                            as $warehouse
                        ): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= $escape(
                                            $warehouse[
                                                'warehouse_name'
                                            ]
                                        ) ?>
                                    </strong>

                                    <div
                                        class="small text-muted"
                                    >
                                        <?= $escape(
                                            $warehouse[
                                                'warehouse_code'
                                            ]
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <?= $money(
                                        $warehouse[
                                            'net_revenue'
                                        ]
                                    ) ?>
                                </td>

                                <td
                                    class="<?= $profitClass(
                                        $warehouse[
                                            'known_gross_profit'
                                        ]
                                    ) ?>"
                                >
                                    <?= $money(
                                        $warehouse[
                                            'known_gross_profit'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= $margin(
                                        $warehouse[
                                            'known_margin_percent'
                                        ]
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Profit by Sale
        </h2>
    </div>

    <?php if (
        empty($report['sales'])
    ): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                No sales found.
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Sale</th>
                        <th>Client</th>
                        <th>Warehouse</th>
                        <th>Last Activity</th>
                        <th>Net Revenue</th>
                        <th>Known COGS</th>
                        <th>Known Profit</th>
                        <th>Margin</th>
                        <th>Uncosted</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        array_slice(
                            $report['sales'],
                            0,
                            100
                        ) as $sale
                    ): ?>
                        <tr>
                            <td class="font-monospace">
                                <?= $escape(
                                    $sale[
                                        'sale_number'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $sale[
                                        'client_name'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $sale[
                                        'warehouse_name'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $sale[
                                        'last_event_date'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $sale[
                                        'net_revenue'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $sale[
                                        'known_cogs'
                                    ]
                                ) ?>
                            </td>

                            <td
                                class="<?= $profitClass(
                                    $sale[
                                        'known_gross_profit'
                                    ]
                                ) ?>"
                            >
                                <?= $money(
                                    $sale[
                                        'known_gross_profit'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $margin(
                                    $sale[
                                        'known_margin_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $sale[
                                    'uncosted_event_count'
                                ] ?>
                            </td>

                            <td>
                                <a
                                    href="/sales/show?id=<?= (int) $sale['sale_id'] ?>"
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
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div
        class="card-header d-flex
        justify-content-between
        align-items-center"
    >
        <h2 class="h5 mb-0">
            Profit Movements
        </h2>

        <span class="small text-muted">
            Showing up to 200 rows
        </span>
    </div>

    <?php if (
        empty($report['events'])
    ): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                No report movements found.
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Document</th>
                        <th>Type</th>
                        <th>Product</th>
                        <th>Client</th>
                        <th>Warehouse</th>
                        <th>Quantity</th>
                        <th>Revenue</th>
                        <th>COGS</th>
                        <th>Profit</th>
                        <th>Cost Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        array_slice(
                            $report['events'],
                            0,
                            200
                        ) as $event
                    ): ?>
                        <?php
                        $isReturn =
                            $event[
                                'source_type'
                            ] ===
                            'sales_return';

                        $documentNumber =
                            $isReturn
                                ? $event[
                                    'return_number'
                                ]
                                : $event[
                                    'sale_number'
                                ];
                        ?>

                        <tr>
                            <td>
                                <?= $escape(
                                    $event[
                                        'event_date'
                                    ]
                                ) ?>
                            </td>

                            <td class="font-monospace">
                                <?= $escape(
                                    $documentNumber
                                ) ?>

                                <?php if ($isReturn): ?>
                                    <div class="small">
                                        <a
                                            href="/sales/show?id=<?= (int) $event['sale_id'] ?>"
                                        >
                                            Sale:
                                            <?= $escape(
                                                $event[
                                                    'sale_number'
                                                ]
                                            ) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($isReturn): ?>
                                    <span
                                        class="badge
                                        text-bg-warning"
                                    >
                                        Sales Return
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge
                                        text-bg-success"
                                    >
                                        Sale
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong>
                                    <?= $escape(
                                        $event[
                                            'product_name'
                                        ]
                                    ) ?>
                                </strong>

                                <div
                                    class="small text-muted
                                    font-monospace"
                                >
                                    <?= $escape(
                                        $event[
                                            'product_internal_code'
                                        ]
                                    ) ?>
                                </div>
                            </td>

                            <td>
                                <?= $escape(
                                    $event[
                                        'client_name'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $event[
                                        'warehouse_name'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $quantity(
                                    $event[
                                        'signed_quantity'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $event[
                                        'revenue_amount'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $event[
                                    'cogs_amount'
                                ] !== null
                                    ? $money(
                                        $event[
                                            'cogs_amount'
                                        ]
                                    )
                                    : '—' ?>
                            </td>

                            <td
                                class="<?= $event[
                                    'gross_profit_amount'
                                ] !== null
                                    ? $profitClass(
                                        $event[
                                            'gross_profit_amount'
                                        ]
                                    )
                                    : '' ?>"
                            >
                                <?= $event[
                                    'gross_profit_amount'
                                ] !== null
                                    ? $money(
                                        $event[
                                            'gross_profit_amount'
                                        ]
                                    )
                                    : '—' ?>
                            </td>

                            <td>
                                <?php if (
                                    (int) $event[
                                        'is_costed'
                                    ] === 1
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-success"
                                    >
                                        Costed
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge
                                        text-bg-warning"
                                    >
                                        Missing Cost
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