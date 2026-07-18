<?php

$isDraft =
    (string) $adjustment['status'] ===
    'draft';

$isCompleted =
    (string) $adjustment['status'] ===
    'completed';

$isCancelled =
    (string) $adjustment['status'] ===
    'cancelled';

$reasonLabel =
    $reasonTypes[
        $adjustment['reason_type']
    ] ??
    (string) $adjustment['reason_type'];
?>

<div
    class="d-flex flex-column flex-lg-row
    justify-content-between
    align-items-lg-start gap-3 mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Inventory Adjustment
            <span class="font-monospace">
                <?= htmlspecialchars(
                    (string) $adjustment[
                        'adjustment_number'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </h1>

        <p class="text-muted mb-0">
            <?= htmlspecialchars(
                (string) $adjustment[
                    'warehouse_name'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
            —
            <?= htmlspecialchars(
                (string) $adjustment[
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
            href="/inventory-adjustments"
            class="btn btn-outline-secondary"
        >
            Back
        </a>
    </div>
</div>

<?php if ($isCancelled): ?>
    <div class="alert alert-danger">
        <div>
            <strong>Cancellation reason:</strong>

            <?= htmlspecialchars(
                (string) $adjustment[
                    'cancellation_reason'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

        <div>
            <strong>Cancelled at:</strong>

            <?= htmlspecialchars(
                (string) $adjustment[
                    'cancelled_at'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

        <div>
            <strong>Cancelled by:</strong>

            <?= htmlspecialchars(
                (string) $adjustment[
                    'cancelled_by_user_name'
                ],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">
                    Date
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        (string) $adjustment[
                            'adjustment_date'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Reason Type
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        $reasonLabel,
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
                        (string) $adjustment[
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
                            (string) $adjustment[
                                'completed_by_user_name'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-12">
                <div class="text-muted small">
                    Reason Description
                </div>

                <div>
                    <?= htmlspecialchars(
                        (string) $adjustment[
                            'reason_description'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <?php if (
                trim(
                    (string) $adjustment['notes']
                ) !== ''
            ): ?>
                <div class="col-12">
                    <div class="text-muted small">
                        Notes
                    </div>

                    <div>
                        <?= nl2br(
                            htmlspecialchars(
                                (string) $adjustment[
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
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">
                Add Product
            </h2>
        </div>

        <div class="card-body">
            <form
                method="GET"
                action="/inventory-adjustments/show"
                class="row g-2 mb-4"
            >
                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $adjustment['id'] ?>"
                >

                <div class="col-md-10">
                    <input
                        type="text"
                        name="product_search"
                        class="form-control"
                        placeholder="Search product by name, code or barcode..."
                        value="<?= htmlspecialchars(
                            $productSearch,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div class="col-md-2 d-grid">
                    <button
                        type="submit"
                        class="btn btn-outline-primary"
                    >
                        Search
                    </button>
                </div>
            </form>

            <form
                method="POST"
                action="/inventory-adjustments/items/store"
            >
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="inventory_adjustment_id"
                    value="<?= (int) $adjustment['id'] ?>"
                >

                <div class="row g-3">
                    <div class="col-lg-5">
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
                            required
                        >
                            <option value="">
                                Select Product
                            </option>

                            <?php foreach (
                                $products as $product
                            ): ?>
                                <option
                                    value="<?= (int) $product['id'] ?>"
                                >
                                    <?= htmlspecialchars(
                                        (string) $product[
                                            'name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                    |
                                    <?= htmlspecialchars(
                                        (string) $product[
                                            'internal_code'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                    |
                                    Stock:
                                    <?= number_format(
                                        (float) $product[
                                            'current_quantity'
                                        ],
                                        3
                                    ) ?>
                                    <?= htmlspecialchars(
                                        (string) $product[
                                            'unit'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <label
                            for="direction"
                            class="form-label"
                        >
                            Direction
                        </label>

                        <select
                            id="direction"
                            name="direction"
                            class="form-select"
                            required
                        >
                            <?php foreach (
                                $directions as
                                $value => $label
                            ): ?>
                                <option
                                    value="<?= htmlspecialchars(
                                        $value,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
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

                    <div class="col-lg-2">
                        <label
                            for="quantity"
                            class="form-label"
                        >
                            Quantity
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            class="form-control"
                            min="0.001"
                            step="0.001"
                            required
                        >
                    </div>

                    <div class="col-lg-3">
                        <label
                            for="item_note"
                            class="form-label"
                        >
                            Item Note
                        </label>

                        <input
                            type="text"
                            id="item_note"
                            name="item_note"
                            class="form-control"
                            maxlength="500"
                        >
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary mt-3"
                >
                    Add Product
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Adjustment Items
        </h2>
    </div>

    <?php if (empty($items)): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                No products have been added.
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
                        <th>Code</th>
                        <th>Direction</th>
                        <th>Quantity</th>
                        <th>Stock When Added</th>
                        <th>Before Completion</th>
                        <th>After Completion</th>
                        <th>Note</th>
                        <?php if ($isDraft): ?>
                            <th></th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $items as $item
                    ): ?>
                        <tr>
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

                                <div class="small text-muted">
                                    <?= htmlspecialchars(
                                        (string) $item[
                                            'product_unit'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>
                            </td>

                            <td class="font-monospace">
                                <?= htmlspecialchars(
                                    (string) $item[
                                        'product_internal_code'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    $item['direction'] ===
                                    'increase'
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-success"
                                    >
                                        Increase
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge
                                        text-bg-danger"
                                    >
                                        Decrease
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong
                                    class="<?= $item[
                                        'direction'
                                    ] === 'increase'
                                        ? 'text-success'
                                        : 'text-danger' ?>"
                                >
                                    <?= $item[
                                        'direction'
                                    ] === 'increase'
                                        ? '+'
                                        : '-' ?>

                                    <?= number_format(
                                        (float) $item[
                                            'quantity'
                                        ],
                                        3
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $item[
                                        'stock_quantity_at_add'
                                    ],
                                    3
                                ) ?>
                            </td>

                            <td>
                                <?= $item[
                                    'quantity_before'
                                ] !== null
                                    ? number_format(
                                        (float) $item[
                                            'quantity_before'
                                        ],
                                        3
                                    )
                                    : '—' ?>
                            </td>

                            <td>
                                <?= $item[
                                    'quantity_after'
                                ] !== null
                                    ? number_format(
                                        (float) $item[
                                            'quantity_after'
                                        ],
                                        3
                                    )
                                    : '—' ?>
                            </td>

                            <td>
                                <?= trim(
                                    (string) $item[
                                        'item_note'
                                    ]
                                ) !== ''
                                    ? htmlspecialchars(
                                        (string) $item[
                                            'item_note'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : '—' ?>
                            </td>

                            <?php if ($isDraft): ?>
                                <td>
                                    <form
                                        method="POST"
                                        action="/inventory-adjustments/items/delete"
                                        onsubmit="
                                            return confirm(
                                                'Remove this product?'
                                            );
                                        "
                                    >
                                        <?= \App\Core\Csrf::field() ?>

                                        <input
                                            type="hidden"
                                            name="inventory_adjustment_id"
                                            value="<?= (int) $adjustment['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="item_id"
                                            value="<?= (int) $item['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm
                                            btn-outline-danger"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($isDraft): ?>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <?php if (!empty($items)): ?>
            <form
                method="POST"
                action="/inventory-adjustments/complete"
                onsubmit="
                    return confirm(
                        'Complete this adjustment and change stock quantities? This cannot be undone.'
                    );
                "
            >
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="inventory_adjustment_id"
                    value="<?= (int) $adjustment['id'] ?>"
                >

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    Complete Adjustment
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card border-danger mt-4">
        <div class="card-header text-danger">
            <strong>Cancel Adjustment</strong>
        </div>

        <div class="card-body">
            <form
                method="POST"
                action="/inventory-adjustments/cancel"
                onsubmit="
                    return confirm(
                        'Cancel this adjustment?'
                    );
                "
            >
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="inventory_adjustment_id"
                    value="<?= (int) $adjustment['id'] ?>"
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
                    maxlength="500"
                    rows="3"
                    required
                ></textarea>

                <button
                    type="submit"
                    class="btn btn-danger mt-3"
                >
                    Cancel Adjustment
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>