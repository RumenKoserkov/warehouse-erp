<?php

$isDraft =
    (string) $salesReturn['status'] ===
    'draft';

$isCompleted =
    (string) $salesReturn['status'] ===
    'completed';

$isCancelled =
    (string) $salesReturn['status'] ===
    'cancelled';

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
    $clientName = 'No Client';
}
?>

<div
    class="d-flex flex-column
    flex-lg-row justify-content-between
    align-items-lg-start gap-3 mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Sales Return
            <span class="font-monospace">
                <?= htmlspecialchars(
                    (string) $salesReturn[
                        'return_number'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </h1>

        <p class="text-muted mb-0">
            Sale:
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
            href="/sales-returns"
            class="btn btn-outline-secondary"
        >
            Back
        </a>
    </div>
</div>

<?php if ($isCancelled): ?>
    <div class="alert alert-danger">
        <strong>Cancellation reason:</strong>

        <?= htmlspecialchars(
            (string) $salesReturn[
                'cancellation_reason'
            ],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>
<?php endif; ?>

<?php if ($isCompleted): ?>
    <div class="alert alert-warning">
        This sales return did not automatically
        refund a payment or issue a credit note.
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">
                    Return Date
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        (string) $salesReturn[
                            'return_date'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Client
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        $clientName,
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
                        (string) $salesReturn[
                            'warehouse_name'
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
                        (string) $salesReturn[
                            'created_by_user_name'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">
                    Reason
                </div>

                <div>
                    <?= htmlspecialchars(
                        $reasonTypes[
                            $salesReturn[
                                'reason_type'
                            ]
                        ] ??
                        (string) $salesReturn[
                            'reason_type'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <div class="col-md-8">
                <div class="text-muted small">
                    Reason Description
                </div>

                <div>
                    <?= htmlspecialchars(
                        (string) $salesReturn[
                            'reason_description'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            </div>

            <?php if (
                trim(
                    (string) (
                        $salesReturn['notes'] ?? ''
                    )
                ) !== ''
            ): ?>
                <div class="col-12">
                    <div class="text-muted small">
                        Notes
                    </div>

                    <div>
                        <?= nl2br(
                            htmlspecialchars(
                                (string) $salesReturn[
                                    'notes'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isCompleted): ?>
                <div class="col-md-3">
                    <div class="text-muted small">
                        Completed By
                    </div>

                    <div class="fw-semibold">
                        <?= htmlspecialchars(
                            (string) (
                                $salesReturn[
                                    'completed_by_user_name'
                                ] ?? '—'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">
                        Completed At
                    </div>

                    <div class="fw-semibold">
                        <?= htmlspecialchars(
                            (string) (
                                $salesReturn[
                                    'completed_at'
                                ] ?? '—'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<form
    method="POST"
    action="/sales-returns/update"
>
    <?= \App\Core\Csrf::field() ?>

    <input
        type="hidden"
        name="sales_return_id"
        value="<?= (int) $salesReturn['id'] ?>"
    >

    <div class="card shadow-sm">
        <div class="card-header">
            <h2 class="h5 mb-0">
                Returned Items
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
                        <th>Sold</th>
                        <th>Return Qty</th>
                        <th>Restock Qty</th>
                        <th>Not Restocked</th>
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
                        <?php
                        $saleItemId =
                            (int) $item[
                                'sale_item_id'
                            ];

                        $maximumQuantity =
                            (float) $item[
                                'return_quantity'
                            ];

                        if (
                            isset(
                                $returnableMap[
                                    $saleItemId
                                ]
                            )
                        ) {
                            $maximumQuantity =
                                (float) $returnableMap[
                                    $saleItemId
                                ]['remaining_quantity'];
                        }
                        ?>

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

                                <div
                                    class="small
                                    text-muted
                                    font-monospace"
                                >
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
                                        (string) (
                                            $item[
                                                'item_note'
                                            ] ?? ''
                                        )
                                    ) !== ''
                                ): ?>
                                    <div class="small mt-1">
                                        <?= htmlspecialchars(
                                            (string) $item[
                                                'item_note'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $item[
                                        'sold_quantity'
                                    ],
                                    3
                                ) ?>

                                <?= htmlspecialchars(
                                    (string) $item[
                                        'product_unit'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if ($isDraft): ?>
                                    <input
                                        type="number"
                                        name="return_quantity[<?= $saleItemId ?>]"
                                        class="form-control"
                                        min="0.001"
                                        max="<?= htmlspecialchars(
                                            number_format(
                                                $maximumQuantity,
                                                3,
                                                '.',
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        step="0.001"
                                        required
                                        value="<?= htmlspecialchars(
                                            (string) $item[
                                                'return_quantity'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                <?php else: ?>
                                    <?= number_format(
                                        (float) $item[
                                            'return_quantity'
                                        ],
                                        3
                                    ) ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($isDraft): ?>
                                    <input
                                        type="number"
                                        name="restock_quantity[<?= $saleItemId ?>]"
                                        class="form-control"
                                        min="0"
                                        max="<?= htmlspecialchars(
                                            number_format(
                                                $maximumQuantity,
                                                3,
                                                '.',
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        step="0.001"
                                        required
                                        value="<?= htmlspecialchars(
                                            (string) $item[
                                                'restock_quantity'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="item_note[<?= $saleItemId ?>]"
                                        value="<?= htmlspecialchars(
                                            (string) (
                                                $item[
                                                    'item_note'
                                                ] ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                <?php else: ?>
                                    <?= number_format(
                                        (float) $item[
                                            'restock_quantity'
                                        ],
                                        3
                                    ) ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $item[
                                        'non_restock_quantity'
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

                                <div class="small text-muted">
                                    <?= number_format(
                                        (float) $item[
                                            'vat_rate'
                                        ],
                                        2
                                    ) ?>%
                                </div>
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

                <tfoot>
                    <tr>
                        <th colspan="5">
                            Totals
                        </th>

                        <th>
                            <?= number_format(
                                (float) $salesReturn[
                                    'net_amount'
                                ],
                                2
                            ) ?>
                        </th>

                        <th>
                            <?= number_format(
                                (float) $salesReturn[
                                    'tax_amount'
                                ],
                                2
                            ) ?>
                        </th>

                        <th>
                            <?= number_format(
                                (float) $salesReturn[
                                    'total_amount'
                                ],
                                2
                            ) ?>
                        </th>

                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($isDraft): ?>
            <div class="card-footer">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Quantities
                </button>
            </div>
        <?php endif; ?>
    </div>
</form>

<?php if ($isDraft): ?>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <?php if ($canManage): ?>
            <form
                method="POST"
                action="/sales-returns/complete"
                onsubmit="
                    return confirm(
                        'Complete this sales return and update warehouse stock?'
                    );
                "
            >
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="sales_return_id"
                    value="<?= (int) $salesReturn['id'] ?>"
                >

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    Complete Sales Return
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-info mb-0">
                A manager or administrator must
                complete this sales return.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($canManage): ?>
        <div class="card border-danger mt-4">
            <div class="card-header text-danger">
                <strong>
                    Cancel Sales Return
                </strong>
            </div>

            <div class="card-body">
                <form
                    method="POST"
                    action="/sales-returns/cancel"
                    onsubmit="
                        return confirm(
                            'Cancel this sales return draft?'
                        );
                    "
                >
                    <?= \App\Core\Csrf::field() ?>

                    <input
                        type="hidden"
                        name="sales_return_id"
                        value="<?= (int) $salesReturn['id'] ?>"
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
                        Cancel Sales Return
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>