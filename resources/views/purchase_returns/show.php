<?php

$isDraft =
    (string) $purchaseReturn['status'] ===
    'draft';

$isCompleted =
    (string) $purchaseReturn['status'] ===
    'completed';

$isCancelled =
    (string) $purchaseReturn['status'] ===
    'cancelled';

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
    $supplierName = 'No Supplier';
}
?>

<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Purchase Return
            <span class="font-monospace">
                <?= htmlspecialchars(
                    (string) $purchaseReturn[
                        'return_number'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </h1>

        <p class="text-muted mb-0">
            Purchase:
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
        </p>
    </div>

    <a
        href="/purchase-returns"
        class="btn btn-outline-secondary"
    >
        Back
    </a>
</div>

<?php if ($isCancelled): ?>
    <div class="alert alert-danger">
        <strong>Cancellation reason:</strong>

        <?= htmlspecialchars(
            (string) $purchaseReturn[
                'cancellation_reason'
            ],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">
                    Status
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        ucfirst(
                            (string) $purchaseReturn[
                                'status'
                            ]
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Return Date
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        (string) $purchaseReturn[
                            'return_date'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Supplier
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        $supplierName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Warehouse
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        (string) $purchaseReturn[
                            'warehouse_name'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-12">
                <div class="text-muted small">
                    Reason
                </div>

                <div>
                    <?= htmlspecialchars(
                        $reasonTypes[
                            $purchaseReturn[
                                'reason_type'
                            ]
                        ] ??
                        (string) $purchaseReturn[
                            'reason_type'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    —
                    <?= htmlspecialchars(
                        (string) $purchaseReturn[
                            'reason_description'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isDraft): ?>
    <form
        method="POST"
        action="/purchase-returns/update"
    >
        <?= \App\Core\Csrf::field() ?>

        <input
            type="hidden"
            name="purchase_return_id"
            value="<?= (int) $purchaseReturn['id'] ?>"
        >

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table
                    class="table align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Purchased</th>
                            <th>Returned Before</th>
                            <th>Remaining</th>
                            <th>Return Quantity</th>
                            <th>Note</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $returnableItems as $item
                        ): ?>
                            <?php
                            $itemId =
                                (int) $item['id'];

                            $stored =
                                $storedItemMap[
                                    $itemId
                                ] ?? null;
                            ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $item[
                                            'product_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float) $item[
                                            'quantity'
                                        ],
                                        3
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float) $item[
                                            'returned_quantity'
                                        ],
                                        3
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float) $item[
                                            'remaining_quantity'
                                        ],
                                        3
                                    ) ?>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="return_quantity[<?= $itemId ?>]"
                                        class="form-control"
                                        min="0"
                                        max="<?= number_format(
                                            (float) $item[
                                                'remaining_quantity'
                                            ],
                                            3,
                                            '.',
                                            ''
                                        ) ?>"
                                        step="0.001"
                                        value="<?= htmlspecialchars(
                                            (string) (
                                                $stored[
                                                    'return_quantity'
                                                ] ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="item_note[<?= $itemId ?>]"
                                        class="form-control"
                                        maxlength="500"
                                        value="<?= htmlspecialchars(
                                            (string) (
                                                $stored[
                                                    'item_note'
                                                ] ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Quantities
                </button>
            </div>
        </div>
    </form>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table
                class="table align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Returned</th>
                        <th>Net</th>
                        <th>VAT</th>
                        <th>Total</th>
                        <th>Stock Before</th>
                        <th>Stock After</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $items as $item
                    ): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    (string) $item[
                                        'product_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $item[
                                        'return_quantity'
                                    ],
                                    3
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $item[
                                        'net_amount'
                                    ],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $item[
                                        'tax_amount'
                                    ],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= number_format(
                                        (float) $item[
                                            'total_amount'
                                        ],
                                        2
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $item[
                                    'stock_quantity_before'
                                ] !== null
                                    ? number_format(
                                        (float) $item[
                                            'stock_quantity_before'
                                        ],
                                        3
                                    )
                                    : '—' ?>
                            </td>

                            <td>
                                <?= $item[
                                    'stock_quantity_after'
                                ] !== null
                                    ? number_format(
                                        (float) $item[
                                            'stock_quantity_after'
                                        ],
                                        3
                                    )
                                    : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm mt-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Subtotal:</strong>
                <?= number_format(
                    (float) $purchaseReturn[
                        'subtotal_amount'
                    ],
                    2
                ) ?>
            </div>

            <div class="col-md-3">
                <strong>Discount:</strong>
                <?= number_format(
                    (float) $purchaseReturn[
                        'discount_amount'
                    ],
                    2
                ) ?>
            </div>

            <div class="col-md-3">
                <strong>Tax:</strong>
                <?= number_format(
                    (float) $purchaseReturn[
                        'tax_amount'
                    ],
                    2
                ) ?>
            </div>

            <div class="col-md-3">
                <strong>Total:</strong>
                <?= number_format(
                    (float) $purchaseReturn[
                        'total_amount'
                    ],
                    2
                ) ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isDraft && $canManage): ?>
    <div class="d-flex gap-2 mt-4">
        <form
            method="POST"
            action="/purchase-returns/complete"
            onsubmit="
                return confirm(
                    'Complete this return and remove stock from the warehouse?'
                );
            "
        >
            <?= \App\Core\Csrf::field() ?>

            <input
                type="hidden"
                name="purchase_return_id"
                value="<?= (int) $purchaseReturn['id'] ?>"
            >

            <button
                type="submit"
                class="btn btn-success"
            >
                Complete Purchase Return
            </button>
        </form>
    </div>

    <div class="card border-danger mt-4">
        <div class="card-body">
            <form
                method="POST"
                action="/purchase-returns/cancel"
            >
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="purchase_return_id"
                    value="<?= (int) $purchaseReturn['id'] ?>"
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
                    required
                ></textarea>

                <button
                    type="submit"
                    class="btn btn-danger mt-3"
                >
                    Cancel Draft
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>