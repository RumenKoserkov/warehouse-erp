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

$percentage = static function (
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

$marginClass = static function (
    mixed $value,
    float $minimumMargin
): string {
    if ($value === null) {
        return 'text-muted';
    }

    if ((float) $value < 0) {
        return 'text-danger';
    }

    if (
        (float) $value <
        $minimumMargin
    ) {
        return 'text-warning';
    }

    return 'text-success';
};

$summary =
    $report['summary'];

$minimumMargin =
    (float) $report[
        'minimum_margin'
    ];

$hasUncosted =
    (int) $summary[
        'uncosted_event_count'
    ] > 0;
?>

<div
    class="d-flex flex-column
    flex-lg-row justify-content-between
    align-items-lg-start gap-3 mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Margin Reports
        </h1>

        <p class="text-muted mb-0">
            Gross margin, markup,
            discounts and low-margin sales.
        </p>
    </div>

    <a
        href="/reports/profit"
        class="btn btn-outline-secondary"
    >
        Profit Reports
    </a>
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
            Incomplete cost coverage.
        </strong>

        <?= (int) $summary[
            'uncosted_event_count'
        ] ?>
        row(s) do not have a reliable
        historical cost.

        Margin and markup calculations use
        only rows with a known cost snapshot.
    </div>
<?php endif; ?>

<form
    method="GET"
    action="/reports/margins"
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
                        $filters[
                            'date_from'
                        ]
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
                        $filters[
                            'date_to'
                        ]
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

            <div class="col-md-3 col-xl-2">
                <label
                    for="minimum_margin"
                    class="form-label"
                >
                    Minimum Margin %
                </label>

                <input
                    type="number"
                    id="minimum_margin"
                    name="minimum_margin"
                    class="form-control"
                    min="0"
                    max="100"
                    step="0.01"
                    required
                    value="<?= $escape(
                        number_format(
                            (float) $filters[
                                'minimum_margin'
                            ],
                            2,
                            '.',
                            ''
                        )
                    ) ?>"
                >
            </div>

            <div class="col-md-6 col-xl-4">
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
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 col-xl-4">
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
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 col-xl-4">
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

            <div class="col-md-6 col-xl-4">
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

            <div class="col-md-6 col-xl-4">
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

            <div class="col-md-6 col-xl-4">
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
                    placeholder="Sale, return, product, client..."
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
                    href="/reports/margins"
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
                    Weighted Gross Margin
                </div>

                <div
                    class="fs-3 fw-bold <?= $marginClass(
                        $summary[
                            'weighted_margin_percent'
                        ],
                        $minimumMargin
                    ) ?>"
                >
                    <?= $percentage(
                        $summary[
                            'weighted_margin_percent'
                        ]
                    ) ?>
                </div>

                <div class="small text-muted">
                    Target:
                    <?= $percentage(
                        $minimumMargin
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Markup
                </div>

                <div class="fs-3 fw-bold">
                    <?= $percentage(
                        $summary[
                            'markup_percent'
                        ]
                    ) ?>
                </div>

                <div class="small text-muted">
                    Profit relative to COGS
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Discount Rate
                </div>

                <div class="fs-3 fw-bold">
                    <?= $percentage(
                        $summary[
                            'discount_rate_percent'
                        ]
                    ) ?>
                </div>

                <div class="small text-muted">
                    <?= $money(
                        $summary[
                            'discount_amount'
                        ]
                    ) ?>
                    total discounts
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Cost Coverage
                </div>

                <div
                    class="fs-3 fw-bold <?= (
                        (float) (
                            $summary[
                                'cost_coverage_percent'
                            ] ?? 0
                        ) >= 99.99
                    )
                        ? 'text-success'
                        : 'text-warning' ?>"
                >
                    <?= $percentage(
                        $summary[
                            'cost_coverage_percent'
                        ]
                    ) ?>
                </div>

                <div class="small text-muted">
                    Revenue with known cost
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
                    Gross Sales
                </div>

                <div class="fs-4 fw-bold">
                    <?= $money(
                        $summary[
                            'gross_amount'
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
                    Net Revenue
                </div>

                <div class="fs-4 fw-bold">
                    <?= $money(
                        $summary[
                            'net_revenue'
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
                    Known COGS
                </div>

                <div class="fs-4 fw-bold">
                    <?= $money(
                        $summary[
                            'known_cogs'
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
                    Gross Profit
                </div>

                <div
                    class="fs-4 fw-bold <?= (
                        (float) $summary[
                            'known_gross_profit'
                        ] >= 0
                    )
                        ? 'text-success'
                        : 'text-danger' ?>"
                >
                    <?= $money(
                        $summary[
                            'known_gross_profit'
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
                    Below Target Rows
                </div>

                <div class="fs-4 fw-bold text-warning">
                    <?= (int) $summary[
                        'below_target_row_count'
                    ] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Negative Rows
                </div>

                <div class="fs-4 fw-bold text-danger">
                    <?= (int) $summary[
                        'negative_row_count'
                    ] ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Margin Distribution
        </h2>
    </div>

    <div class="table-responsive">
        <table
            class="table table-hover
            align-middle mb-0"
        >
            <thead>
                <tr>
                    <th>Band</th>
                    <th>Sale Rows</th>
                    <th>Revenue</th>
                    <th>COGS</th>
                    <th>Profit</th>
                    <th>Margin</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $bandLabels = [
                    'negative' =>
                        'Negative Margin',

                    'below_target' =>
                        'Below Target',

                    'meets_target' =>
                        'Meets Target',

                    'uncosted' =>
                        'Missing Cost',
                ];
                ?>

                <?php foreach (
                    $bandLabels as
                    $bandKey => $bandLabel
                ): ?>
                    <?php
                    $band =
                        $report[
                            'bands'
                        ][$bandKey];
                    ?>

                    <tr>
                        <td>
                            <strong>
                                <?= $escape(
                                    $bandLabel
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <?= (int) $band[
                                'sale_row_count'
                            ] ?>
                        </td>

                        <td>
                            <?= $money(
                                $band[
                                    'net_revenue'
                                ]
                            ) ?>
                        </td>

                        <td>
                            <?= $money(
                                $band[
                                    'known_cogs'
                                ]
                            ) ?>
                        </td>

                        <td>
                            <?= $money(
                                $band[
                                    'known_gross_profit'
                                ]
                            ) ?>
                        </td>

                        <td
                            class="<?= $marginClass(
                                $band[
                                    'weighted_margin_percent'
                                ],
                                $minimumMargin
                            ) ?>"
                        >
                            <?= $percentage(
                                $band[
                                    'weighted_margin_percent'
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Margin by
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
                No margin data found.
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
                        <th>Gross Sales</th>
                        <th>Discounts</th>
                        <th>Net Revenue</th>
                        <th>COGS</th>
                        <th>Profit</th>
                        <th>Margin</th>
                        <th>Markup</th>
                        <th>Coverage</th>
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
                                        'gross_amount'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $period[
                                        'discount_amount'
                                    ]
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

                            <td>
                                <?= $money(
                                    $period[
                                        'known_gross_profit'
                                    ]
                                ) ?>
                            </td>

                            <td
                                class="<?= $marginClass(
                                    $period[
                                        'weighted_margin_percent'
                                    ],
                                    $minimumMargin
                                ) ?>"
                            >
                                <strong>
                                    <?= $percentage(
                                        $period[
                                            'weighted_margin_percent'
                                        ]
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $percentage(
                                    $period[
                                        'markup_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $percentage(
                                    $period[
                                        'cost_coverage_percent'
                                    ]
                                ) ?>
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
            Product Margins
        </h2>
    </div>

    <?php if (
        empty($report['products'])
    ): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                No product margin data.
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
                        <th>Revenue</th>
                        <th>COGS</th>
                        <th>Profit</th>
                        <th>Margin</th>
                        <th>Markup</th>
                        <th>Discount Rate</th>
                        <th>Coverage</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        array_slice(
                            $report['products'],
                            0,
                            100
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
                                    class="small
                                    text-muted
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

                            <td>
                                <?= $money(
                                    $product[
                                        'known_gross_profit'
                                    ]
                                ) ?>
                            </td>

                            <td
                                class="<?= $marginClass(
                                    $product[
                                        'weighted_margin_percent'
                                    ],
                                    $minimumMargin
                                ) ?>"
                            >
                                <strong>
                                    <?= $percentage(
                                        $product[
                                            'weighted_margin_percent'
                                        ]
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $percentage(
                                    $product[
                                        'markup_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $percentage(
                                    $product[
                                        'discount_rate_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $percentage(
                                    $product[
                                        'cost_coverage_percent'
                                    ]
                                ) ?>
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
        $report[
            'below_target_products'
        ]
    )
): ?>
    <div class="card border-warning mb-4">
        <div class="card-header text-warning-emphasis">
            <h2 class="h5 mb-0">
                Products Below
                <?= $percentage(
                    $minimumMargin
                ) ?>
                Margin
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
                        <th>Revenue</th>
                        <th>COGS</th>
                        <th>Profit</th>
                        <th>Margin</th>
                        <th>Discount Rate</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        array_slice(
                            $report[
                                'below_target_products'
                            ],
                            0,
                            30
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

                            <td>
                                <?= $money(
                                    $product[
                                        'known_gross_profit'
                                    ]
                                ) ?>
                            </td>

                            <td
                                class="<?= $marginClass(
                                    $product[
                                        'weighted_margin_percent'
                                    ],
                                    $minimumMargin
                                ) ?>"
                            >
                                <?= $percentage(
                                    $product[
                                        'weighted_margin_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $percentage(
                                    $product[
                                        'discount_rate_percent'
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
                    Category Margins
                </h2>
            </div>

            <div class="table-responsive">
                <table
                    class="table table-hover
                    align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Revenue</th>
                            <th>Profit</th>
                            <th>Margin</th>
                            <th>Discount</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            array_slice(
                                $report[
                                    'categories'
                                ],
                                0,
                                30
                            ) as $category
                        ): ?>
                            <tr>
                                <td>
                                    <?= $escape(
                                        $category[
                                            'category_name'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= $money(
                                        $category[
                                            'net_revenue'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= $money(
                                        $category[
                                            'known_gross_profit'
                                        ]
                                    ) ?>
                                </td>

                                <td
                                    class="<?= $marginClass(
                                        $category[
                                            'weighted_margin_percent'
                                        ],
                                        $minimumMargin
                                    ) ?>"
                                >
                                    <?= $percentage(
                                        $category[
                                            'weighted_margin_percent'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= $percentage(
                                        $category[
                                            'discount_rate_percent'
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
                    Client Margins
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
                            <th>Discount</th>
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

                                <td>
                                    <?= $money(
                                        $client[
                                            'known_gross_profit'
                                        ]
                                    ) ?>
                                </td>

                                <td
                                    class="<?= $marginClass(
                                        $client[
                                            'weighted_margin_percent'
                                        ],
                                        $minimumMargin
                                    ) ?>"
                                >
                                    <?= $percentage(
                                        $client[
                                            'weighted_margin_percent'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= $percentage(
                                        $client[
                                            'discount_rate_percent'
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
            Warehouse Margins
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
                    <th>COGS</th>
                    <th>Profit</th>
                    <th>Margin</th>
                    <th>Markup</th>
                    <th>Coverage</th>
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

                            <div class="small text-muted">
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

                        <td>
                            <?= $money(
                                $warehouse[
                                    'known_cogs'
                                ]
                            ) ?>
                        </td>

                        <td>
                            <?= $money(
                                $warehouse[
                                    'known_gross_profit'
                                ]
                            ) ?>
                        </td>

                        <td
                            class="<?= $marginClass(
                                $warehouse[
                                    'weighted_margin_percent'
                                ],
                                $minimumMargin
                            ) ?>"
                        >
                            <?= $percentage(
                                $warehouse[
                                    'weighted_margin_percent'
                                ]
                            ) ?>
                        </td>

                        <td>
                            <?= $percentage(
                                $warehouse[
                                    'markup_percent'
                                ]
                            ) ?>
                        </td>

                        <td>
                            <?= $percentage(
                                $warehouse[
                                    'cost_coverage_percent'
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (
    !empty(
        $report[
            'below_target_sales'
        ]
    )
): ?>
    <div class="card border-warning mb-4">
        <div class="card-header text-warning-emphasis">
            <h2 class="h5 mb-0">
                Sales Below Target Margin
            </h2>
        </div>

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
                        <th>Revenue</th>
                        <th>COGS</th>
                        <th>Profit</th>
                        <th>Margin</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        array_slice(
                            $report[
                                'below_target_sales'
                            ],
                            0,
                            50
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

                            <td>
                                <?= $money(
                                    $sale[
                                        'known_gross_profit'
                                    ]
                                ) ?>
                            </td>

                            <td
                                class="<?= $marginClass(
                                    $sale[
                                        'weighted_margin_percent'
                                    ],
                                    $minimumMargin
                                ) ?>"
                            >
                                <strong>
                                    <?= $percentage(
                                        $sale[
                                            'weighted_margin_percent'
                                        ]
                                    ) ?>
                                </strong>
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
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div
        class="card-header d-flex
        justify-content-between
        align-items-center"
    >
        <h2 class="h5 mb-0">
            Margin Movements
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
                No margin movements found.
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
                        <th>Product</th>
                        <th>Gross</th>
                        <th>Discount</th>
                        <th>Net Revenue</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                        <th>Profit</th>
                        <th>Margin</th>
                        <th>Markup</th>
                        <th>Status</th>
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

                        $status =
                            (string) $event[
                                'margin_status'
                            ];

                        $statusLabel =
                            match ($status) {
                                'negative' =>
                                    'Negative',

                                'below_target' =>
                                    'Below Target',

                                'meets_target' =>
                                    'Meets Target',

                                'uncosted' =>
                                    'Missing Cost',

                                default =>
                                    'Return',
                            };

                        $statusClass =
                            match ($status) {
                                'negative' =>
                                    'text-bg-danger',

                                'below_target' =>
                                    'text-bg-warning',

                                'meets_target' =>
                                    'text-bg-success',

                                'uncosted' =>
                                    'text-bg-secondary',

                                default =>
                                    'text-bg-info',
                            };
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
                                    $isReturn
                                        ? $event[
                                            'return_number'
                                        ]
                                        : $event[
                                            'sale_number'
                                        ]
                                ) ?>
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
                                    class="small
                                    text-muted
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
                                <?= $money(
                                    $event[
                                        'gross_amount'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $money(
                                    $event[
                                        'discount_amount'
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
                                    'inventory_unit_cost'
                                ] !== null
                                    ? number_format(
                                        (float) $event[
                                            'inventory_unit_cost'
                                        ],
                                        4,
                                        '.',
                                        ','
                                    )
                                    : '—' ?>
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

                            <td>
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

                            <td
                                class="<?= $marginClass(
                                    $event[
                                        'event_margin_percent'
                                    ],
                                    $minimumMargin
                                ) ?>"
                            >
                                <?= $percentage(
                                    $event[
                                        'event_margin_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $percentage(
                                    $event[
                                        'event_markup_percent'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <span
                                    class="badge <?= $statusClass ?>"
                                >
                                    <?= $escape(
                                        $statusLabel
                                    ) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>