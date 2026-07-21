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
        Stock History
    </h1>

    <a
        href="/stock"
        class="btn btn-outline-secondary"
    >
        Back to Stock
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form
            method="GET"
            action="/stock/history"
        >
            <div class="row">
                <div class="col-md-3 mb-3">
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
                        placeholder="Product, code, warehouse, user..."
                        value="<?= htmlspecialchars(
                            (string) $filters[
                                'search'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label
                        for="type"
                        class="form-label"
                    >
                        Type
                    </label>

                    <select
                        id="type"
                        name="type"
                        class="form-select"
                    >
                        <option value="">
                            All types
                        </option>

                        <?php foreach (
                            $types as $type
                        ): ?>
                            <option
                                value="<?= htmlspecialchars(
                                    (string) $type,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?php if (
                                    (string) $filters[
                                        'type'
                                    ] ===
                                    (string) $type
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars(
                                    (string) $type,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
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
                        <option value="">
                            All products
                        </option>

                        <?php foreach (
                            $products as $product
                        ): ?>
                            <option
                                value="<?= (int) $product[
                                    'id'
                                ] ?>"
                                <?php if (
                                    (string) $filters[
                                        'product_id'
                                    ] ===
                                    (string) $product['id']
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars(
                                    (string) $product[
                                        'internal_code'
                                    ] .
                                    ' - ' .
                                    (string) $product[
                                        'name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
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
                        <option value="">
                            All warehouses
                        </option>

                        <?php foreach (
                            $warehouses as $warehouse
                        ): ?>
                            <option
                                value="<?= (int) $warehouse[
                                    'id'
                                ] ?>"
                                <?php if (
                                    (string) $filters[
                                        'warehouse_id'
                                    ] ===
                                    (string) $warehouse[
                                        'id'
                                    ]
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars(
                                    (string) $warehouse[
                                        'code'
                                    ] .
                                    ' - ' .
                                    (string) $warehouse[
                                        'name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Filter
                </button>

                <a
                    href="/stock/history"
                    class="btn btn-outline-secondary"
                >
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <p class="text-muted mb-0">
                No stock history found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table
                    class="table table-striped
                    table-hover align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Product</th>
                            <th>From Warehouse</th>
                            <th>To Warehouse</th>
                            <th>Quantity</th>

                            <?php if ($showCosts): ?>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                                <th>Cost Snapshot</th>
                            <?php endif; ?>

                            <th>User</th>
                            <th>Reference</th>
                            <th>Note</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $transactions as $transaction
                        ): ?>
                            <?php
                            $transactionType =
                                (string) $transaction[
                                    'type'
                                ];

                            $incomingTypes = [
                                'in',
                                'purchase',
                                'sale_cancel',
                                'sale_return',
                            ];

                            $outgoingTypes = [
                                'out',
                                'sale',
                                'purchase_cancel',
                                'purchase_return',
                            ];

                            $warningTypes = [
                                'sale_cancel',
                                'purchase_cancel',
                            ];
                            ?>

                            <tr>
                                <td class="text-nowrap">
                                    <?= htmlspecialchars(
                                        (string) $transaction[
                                            'created_at'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        $transactionType ===
                                        'transfer'
                                    ): ?>
                                        <span
                                            class="badge
                                            text-bg-primary"
                                        >
                                            <?= htmlspecialchars(
                                                $transactionType,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php elseif (
                                        in_array(
                                            $transactionType,
                                            $warningTypes,
                                            true
                                        )
                                    ): ?>
                                        <span
                                            class="badge
                                            text-bg-warning"
                                        >
                                            <?= htmlspecialchars(
                                                $transactionType,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php elseif (
                                        in_array(
                                            $transactionType,
                                            $incomingTypes,
                                            true
                                        )
                                    ): ?>
                                        <span
                                            class="badge
                                            text-bg-success"
                                        >
                                            <?= htmlspecialchars(
                                                $transactionType,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php elseif (
                                        in_array(
                                            $transactionType,
                                            $outgoingTypes,
                                            true
                                        )
                                    ): ?>
                                        <span
                                            class="badge
                                            text-bg-danger"
                                        >
                                            <?= htmlspecialchars(
                                                $transactionType,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="badge
                                            text-bg-secondary"
                                        >
                                            <?= htmlspecialchars(
                                                $transactionType,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong
                                        class="font-monospace"
                                    >
                                        <?= htmlspecialchars(
                                            (string) $transaction[
                                                'internal_code'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                    <br>

                                    <?= htmlspecialchars(
                                        (string) $transaction[
                                            'product_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        !empty(
                                            $transaction[
                                                'from_warehouse_name'
                                            ]
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            (string) $transaction[
                                                'from_warehouse_code'
                                            ] .
                                            ' - ' .
                                            (string) $transaction[
                                                'from_warehouse_name'
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
                                    <?php if (
                                        !empty(
                                            $transaction[
                                                'to_warehouse_name'
                                            ]
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            (string) $transaction[
                                                'to_warehouse_code'
                                            ] .
                                            ' - ' .
                                            (string) $transaction[
                                                'to_warehouse_name'
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

                                <td class="text-nowrap">
                                    <strong>
                                        <?= number_format(
                                            (float) $transaction[
                                                'quantity'
                                            ],
                                            3,
                                            '.',
                                            ''
                                        ) ?>
                                    </strong>

                                    <?= htmlspecialchars(
                                        (string) $transaction[
                                            'unit'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <?php if ($showCosts): ?>
                                    <td class="text-nowrap">
                                        <?php if (
                                            $transaction[
                                                'unit_cost'
                                            ] !== null
                                        ): ?>
                                            <?= number_format(
                                                (float) $transaction[
                                                    'unit_cost'
                                                ],
                                                4,
                                                '.',
                                                ''
                                            ) ?>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                —
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-nowrap">
                                        <?php if (
                                            $transaction[
                                                'total_cost'
                                            ] !== null
                                        ): ?>
                                            <strong>
                                                <?= number_format(
                                                    (float) $transaction[
                                                        'total_cost'
                                                    ],
                                                    4,
                                                    '.',
                                                    ''
                                                ) ?>
                                            </strong>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                —
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-nowrap">
                                        <?php
                                        $hasFromCost =
                                            $transaction[
                                                'from_average_cost_before'
                                            ] !== null ||
                                            $transaction[
                                                'from_average_cost_after'
                                            ] !== null;

                                        $hasToCost =
                                            $transaction[
                                                'to_average_cost_before'
                                            ] !== null ||
                                            $transaction[
                                                'to_average_cost_after'
                                            ] !== null;
                                        ?>

                                        <?php if ($hasFromCost): ?>
                                            <div>
                                                <span
                                                    class="text-muted
                                                    small"
                                                >
                                                    From:
                                                </span>

                                                <?= $transaction[
                                                    'from_average_cost_before'
                                                ] !== null
                                                    ? number_format(
                                                        (float) $transaction[
                                                            'from_average_cost_before'
                                                        ],
                                                        4,
                                                        '.',
                                                        ''
                                                    )
                                                    : '—' ?>

                                                →

                                                <?= $transaction[
                                                    'from_average_cost_after'
                                                ] !== null
                                                    ? number_format(
                                                        (float) $transaction[
                                                            'from_average_cost_after'
                                                        ],
                                                        4,
                                                        '.',
                                                        ''
                                                    )
                                                    : '—' ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($hasToCost): ?>
                                            <div>
                                                <span
                                                    class="text-muted
                                                    small"
                                                >
                                                    To:
                                                </span>

                                                <?= $transaction[
                                                    'to_average_cost_before'
                                                ] !== null
                                                    ? number_format(
                                                        (float) $transaction[
                                                            'to_average_cost_before'
                                                        ],
                                                        4,
                                                        '.',
                                                        ''
                                                    )
                                                    : '—' ?>

                                                →

                                                <?= $transaction[
                                                    'to_average_cost_after'
                                                ] !== null
                                                    ? number_format(
                                                        (float) $transaction[
                                                            'to_average_cost_after'
                                                        ],
                                                        4,
                                                        '.',
                                                        ''
                                                    )
                                                    : '—' ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (
                                            !$hasFromCost &&
                                            !$hasToCost
                                        ): ?>
                                            <span class="text-muted">
                                                —
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <?php if (
                                        !empty(
                                            $transaction[
                                                'user_name'
                                            ]
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            (string) $transaction[
                                                'user_name'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            System
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-nowrap">
                                    <?php if (
                                        !empty(
                                            $transaction[
                                                'reference_type'
                                            ]
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            (string) $transaction[
                                                'reference_type'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $transaction[
                                                    'reference_id'
                                                ]
                                            )
                                        ): ?>
                                            #<?= (int) $transaction[
                                                'reference_id'
                                            ] ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            —
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (
                                        !empty(
                                            $transaction[
                                                'note'
                                            ]
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            (string) $transaction[
                                                'note'
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>