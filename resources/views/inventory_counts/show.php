<?php

$isDraft =
    (string) $inventoryCount['status'] ===
    'draft';

$isCompleted =
    (string) $inventoryCount['status'] ===
    'completed';

$isCancelled =
    (string) $inventoryCount['status'] ===
    'cancelled';

$showCosts =
    (bool) ($canManage ?? false);

$missingItems =
    (int) $inventoryCount['total_items'] -
    (int) $inventoryCount['counted_items'];
?>

<div
    class="d-flex flex-column
    flex-lg-row justify-content-between
    align-items-lg-start gap-3 mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Inventory Count

            <span class="font-monospace">
                <?= htmlspecialchars(
                    (string) $inventoryCount[
                        'count_number'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </h1>

        <p class="text-muted mb-0">
            <?= htmlspecialchars(
                (string) $inventoryCount[
                    'warehouse_name'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>

            —

            <?= htmlspecialchars(
                (string) $inventoryCount[
                    'warehouse_code'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </div>

    <div class="d-flex gap-2">
        <?php if ($isCompleted): ?>
            <span class="badge text-bg-success p-2">
                Completed
            </span>
        <?php elseif ($isCancelled): ?>
            <span class="badge text-bg-danger p-2">
                Cancelled
            </span>
        <?php else: ?>
            <span class="badge text-bg-warning p-2">
                Draft
            </span>
        <?php endif; ?>

        <a
            href="/inventory-counts"
            class="btn btn-outline-secondary"
        >
            Back
        </a>
    </div>
</div>

<?php if (
    $isDraft &&
    $stockChanged
): ?>
    <div class="alert alert-danger">
        <h2 class="h5">
            Stock Changed After Snapshot
        </h2>

        <p class="mb-0">
            Warehouse movements were recorded
            after this count was created. It can
            no longer be completed safely. Cancel
            it and create a new inventory count.
        </p>
    </div>
<?php endif; ?>

<?php if ($isCancelled): ?>
    <div class="alert alert-danger">
        <div>
            <strong>Reason:</strong>

            <?= htmlspecialchars(
                (string) $inventoryCount[
                    'cancellation_reason'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

        <div>
            <strong>Cancelled at:</strong>

            <?= htmlspecialchars(
                (string) $inventoryCount[
                    'cancelled_at'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

        <?php if (
            trim(
                (string) $inventoryCount[
                    'cancelled_by_user_name'
                ]
            ) !== ''
        ): ?>
            <div>
                <strong>Cancelled by:</strong>

                <?= htmlspecialchars(
                    (string) $inventoryCount[
                        'cancelled_by_user_name'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Products
                </div>

                <div class="fs-3 fw-bold">
                    <?= (int) $inventoryCount[
                        'total_items'
                    ] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Counted
                </div>

                <div class="fs-3 fw-bold">
                    <?= (int) $inventoryCount[
                        'counted_items'
                    ] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Missing
                </div>

                <div class="fs-3 fw-bold">
                    <?= max(
                        0,
                        $missingItems
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Differences
                </div>

                <div class="fs-3 fw-bold">
                    <?= (int) $inventoryCount[
                        'difference_items'
                    ] ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">
                    Count Date
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        (string) $inventoryCount[
                            'count_date'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Snapshot At
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        (string) $inventoryCount[
                            'snapshot_at'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Created By
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        (string) $inventoryCount[
                            'created_by_user_name'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <?php if ($isCompleted): ?>
                <div class="col-md-3">
                    <div class="text-muted small">
                        Completed By
                    </div>

                    <div class="fw-semibold">
                        <?= htmlspecialchars(
                            (string) $inventoryCount[
                                'completed_by_user_name'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (
                trim(
                    (string) $inventoryCount[
                        'notes'
                    ]
                ) !== ''
            ): ?>
                <div class="col-12">
                    <div class="text-muted small">
                        Notes
                    </div>

                    <div>
                        <?= nl2br(
                            htmlspecialchars(
                                (string) $inventoryCount[
                                    'notes'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isDraft): ?>
    <div class="alert alert-info">
        Enter the physically counted quantity
        for every product. A counted quantity of
        zero means that the product was checked
        and no units were found.
    </div>
<?php endif; ?>

<form
    method="POST"
    action="/inventory-counts/save"
>
    <?= \App\Core\Csrf::field() ?>

    <input
        type="hidden"
        name="inventory_count_id"
        value="<?= (int) $inventoryCount['id'] ?>"
    >

    <div class="card shadow-sm">
        <?php if ($isDraft): ?>
            <div
                class="card-header d-flex
                flex-wrap justify-content-between
                align-items-center gap-2"
            >
                <h2 class="h5 mb-0">
                    Counted Products
                </h2>

                <div class="d-flex flex-wrap gap-2">
                    <button
                        type="button"
                        id="fill-system-quantities"
                        class="btn btn-sm
                        btn-outline-secondary"
                    >
                        Fill With System Quantity
                    </button>

                    <button
                        type="button"
                        id="fill-empty-with-zero"
                        class="btn btn-sm
                        btn-outline-secondary"
                    >
                        Fill Empty With Zero
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Code / Barcode</th>
                        <th>Unit</th>
                        <th>System Qty</th>

                        <th style="width: 180px;">
                            Counted Qty
                        </th>

                        <th>Difference</th>

                        <?php if ($showCosts): ?>
                            <th>Unit Cost</th>
                            <th>Cost Variance</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $items as $item
                    ): ?>
                        <?php
                        $difference = null;

                        if (
                            $item[
                                'counted_quantity'
                            ] !== null
                        ) {
                            $difference = round(
                                (float) $item[
                                    'counted_quantity'
                                ] -
                                (float) $item[
                                    'system_quantity'
                                ],
                                3
                            );
                        }

                        $unitCost = round(
                            (float) (
                                $item[
                                    'unit_cost'
                                ] ?? 0
                            ),
                            4
                        );

                        $displayCostVariance = null;

                        if ($isCompleted) {
                            if (
                                $item[
                                    'variance_value'
                                ] !== null
                            ) {
                                $displayCostVariance =
                                    round(
                                        (float) $item[
                                            'variance_value'
                                        ],
                                        4
                                    );
                            }
                        } elseif (
                            $difference !== null
                        ) {
                            $displayCostVariance =
                                round(
                                    $difference *
                                    $unitCost,
                                    4
                                );
                        }
                        ?>

                        <tr
                            data-unit-cost="<?= htmlspecialchars(
                                number_format(
                                    $unitCost,
                                    4,
                                    '.',
                                    ''
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        (string) $item[
                                            'product_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <div class="font-monospace">
                                    <?= htmlspecialchars(
                                        (string) $item[
                                            'product_internal_code'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                                <?php if (
                                    trim(
                                        (string) $item[
                                            'product_barcode'
                                        ]
                                    ) !== ''
                                ): ?>
                                    <div
                                        class="small text-muted
                                        font-monospace"
                                    >
                                        <?= htmlspecialchars(
                                            (string) $item[
                                                'product_barcode'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $item[
                                        'product_unit'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td
                                class="system-quantity"
                                data-system="<?= htmlspecialchars(
                                    (string) $item[
                                        'system_quantity'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <?= number_format(
                                    (float) $item[
                                        'system_quantity'
                                    ],
                                    3
                                ) ?>
                            </td>

                            <td>
                                <?php if ($isDraft): ?>
                                    <input
                                        type="number"
                                        name="counted_quantity[<?= (int) $item['id'] ?>]"
                                        class="form-control
                                        counted-quantity"
                                        min="0"
                                        step="0.001"
                                        data-system="<?= htmlspecialchars(
                                            (string) $item[
                                                'system_quantity'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        value="<?php
                                        if (
                                            $item[
                                                'counted_quantity'
                                            ] !== null
                                        ) {
                                            echo htmlspecialchars(
                                                (string) $item[
                                                    'counted_quantity'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                        }
                                        ?>"
                                    >
                                <?php else: ?>
                                    <?= $item[
                                        'counted_quantity'
                                    ] !== null
                                        ? number_format(
                                            (float) $item[
                                                'counted_quantity'
                                            ],
                                            3
                                        )
                                        : '—' ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span
                                    class="difference-value <?php
                                    if (
                                        $difference !== null &&
                                        $difference > 0
                                    ) {
                                        echo 'text-success';
                                    } elseif (
                                        $difference !== null &&
                                        $difference < 0
                                    ) {
                                        echo 'text-danger';
                                    }
                                    ?>"
                                >
                                    <?php if (
                                        $difference === null
                                    ): ?>
                                        —
                                    <?php elseif (
                                        $difference > 0
                                    ): ?>
                                        +<?= number_format(
                                            $difference,
                                            3
                                        ) ?>
                                    <?php else: ?>
                                        <?= number_format(
                                            $difference,
                                            3
                                        ) ?>
                                    <?php endif; ?>
                                </span>
                            </td>

                            <?php if ($showCosts): ?>
                                <td>
                                    <?= number_format(
                                        $unitCost,
                                        4
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="cost-variance-value <?php
                                        if (
                                            $displayCostVariance !==
                                                null &&
                                            $displayCostVariance > 0
                                        ) {
                                            echo 'text-success';
                                        } elseif (
                                            $displayCostVariance !==
                                                null &&
                                            $displayCostVariance < 0
                                        ) {
                                            echo 'text-danger';
                                        }
                                        ?>"
                                    >
                                        <?php if (
                                            $displayCostVariance ===
                                            null
                                        ): ?>
                                            —
                                        <?php elseif (
                                            $displayCostVariance > 0
                                        ): ?>
                                            +<?= number_format(
                                                $displayCostVariance,
                                                4
                                            ) ?>
                                        <?php else: ?>
                                            <?= number_format(
                                                $displayCostVariance,
                                                4
                                            ) ?>
                                        <?php endif; ?>
                                    </span>

                                    <?php if (
                                        $isDraft &&
                                        $displayCostVariance !== null
                                    ): ?>
                                        <div
                                            class="small text-muted"
                                        >
                                            Estimated
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($isDraft): ?>
            <div class="card-footer">
                <div class="d-flex flex-wrap gap-2">
                    <button
                        type="submit"
                        formaction="/inventory-counts/save"
                        class="btn btn-primary"
                    >
                        Save Draft
                    </button>

                    <?php if (
                        $canManage &&
                        !$stockChanged
                    ): ?>
                        <button
                            type="submit"
                            formaction="/inventory-counts/complete"
                            class="btn btn-success"
                            onclick="
                                return confirm(
                                    'Complete this inventory count and adjust stock? This cannot be undone.'
                                );
                            "
                        >
                            Save & Complete
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</form>

<?php if (
    $isDraft &&
    $canManage
): ?>
    <div class="card border-danger mt-4">
        <div class="card-header text-danger">
            <strong>
                Cancel Inventory Count
            </strong>
        </div>

        <div class="card-body">
            <form
                method="POST"
                action="/inventory-counts/cancel"
                onsubmit="
                    return confirm(
                        'Cancel this inventory count?'
                    );
                "
            >
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="inventory_count_id"
                    value="<?= (int) $inventoryCount['id'] ?>"
                >

                <label
                    for="cancellation_reason"
                    class="form-label"
                >
                    Cancellation Reason
                </label>

                <textarea
                    id="cancellation_reason"
                    name="cancellation_reason"
                    class="form-control"
                    rows="3"
                    maxlength="500"
                    required
                ></textarea>

                <button
                    type="submit"
                    class="btn btn-danger mt-3"
                >
                    Cancel Inventory Count
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($isDraft): ?>
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const inputs =
                    document.querySelectorAll(
                        '.counted-quantity'
                    );

                function updateDifference(input) {
                    const row =
                        input.closest('tr');

                    if (row === null) {
                        return;
                    }

                    const differenceOutput =
                        row.querySelector(
                            '.difference-value'
                        );

                    const costOutput =
                        row.querySelector(
                            '.cost-variance-value'
                        );

                    if (
                        differenceOutput === null
                    ) {
                        return;
                    }

                    differenceOutput.classList.remove(
                        'text-success',
                        'text-danger'
                    );

                    if (costOutput !== null) {
                        costOutput.classList.remove(
                            'text-success',
                            'text-danger'
                        );
                    }

                    if (
                        input.value.trim() === ''
                    ) {
                        differenceOutput.textContent =
                            '—';

                        if (costOutput !== null) {
                            costOutput.textContent =
                                '—';
                        }

                        return;
                    }

                    const counted =
                        Number(input.value);

                    const system =
                        Number(
                            input.dataset.system
                        );

                    const unitCost =
                        Number(
                            row.dataset.unitCost
                        );

                    if (
                        !Number.isFinite(counted) ||
                        !Number.isFinite(system)
                    ) {
                        differenceOutput.textContent =
                            '—';

                        if (costOutput !== null) {
                            costOutput.textContent =
                                '—';
                        }

                        return;
                    }

                    const difference =
                        counted - system;

                    differenceOutput.textContent =
                        (
                            difference > 0
                                ? '+'
                                : ''
                        ) +
                        difference.toFixed(3);

                    if (difference > 0) {
                        differenceOutput.classList.add(
                            'text-success'
                        );
                    }

                    if (difference < 0) {
                        differenceOutput.classList.add(
                            'text-danger'
                        );
                    }

                    if (
                        costOutput !== null &&
                        Number.isFinite(unitCost)
                    ) {
                        const costVariance =
                            difference *
                            unitCost;

                        costOutput.textContent =
                            (
                                costVariance > 0
                                    ? '+'
                                    : ''
                            ) +
                            costVariance.toFixed(2);

                        if (costVariance > 0) {
                            costOutput.classList.add(
                                'text-success'
                            );
                        }

                        if (costVariance < 0) {
                            costOutput.classList.add(
                                'text-danger'
                            );
                        }
                    }
                }

                inputs.forEach(
                    function (input) {
                        input.addEventListener(
                            'input',
                            function () {
                                updateDifference(
                                    input
                                );
                            }
                        );
                    }
                );

                const fillSystemButton =
                    document.getElementById(
                        'fill-system-quantities'
                    );

                if (
                    fillSystemButton !== null
                ) {
                    fillSystemButton
                        .addEventListener(
                            'click',
                            function () {
                                inputs.forEach(
                                    function (input) {
                                        input.value =
                                            Number(
                                                input.dataset
                                                    .system
                                            ).toFixed(3);

                                        updateDifference(
                                            input
                                        );
                                    }
                                );
                            }
                        );
                }

                const fillZeroButton =
                    document.getElementById(
                        'fill-empty-with-zero'
                    );

                if (
                    fillZeroButton !== null
                ) {
                    fillZeroButton
                        .addEventListener(
                            'click',
                            function () {
                                inputs.forEach(
                                    function (input) {
                                        if (
                                            input.value
                                                .trim() === ''
                                        ) {
                                            input.value =
                                                '0.000';

                                            updateDifference(
                                                input
                                            );
                                        }
                                    }
                                );
                            }
                        );
                }
            }
        );
    </script>
<?php endif; ?>