<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">
        Purchase <?= htmlspecialchars(
            (string) $purchase['purchase_number'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </h1>

    <div class="d-flex gap-2">
        <?php if ($purchase['status'] === 'completed'): ?>
            <form
                action="/purchases/cancel"
                method="POST"
                onsubmit="return confirm(
                    'Are you sure you want to cancel this purchase and decrease the stock?'
                );">
                <?= \App\Core\Csrf::field() ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= htmlspecialchars(
                        (string) $purchase['id'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

                <button
                    type="submit"
                    class="btn btn-danger">
                    Cancel Purchase
                </button>
            </form>
        <?php endif; ?>

        <a
            href="/purchases"
            class="btn btn-outline-secondary">
            Back to Purchases
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Purchase Information
            </div>

            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Purchase Number</th>

                        <td>
                            <?= htmlspecialchars(
                                (string) $purchase[
                                    'purchase_number'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Purchase Date</th>

                        <td>
                            <?= htmlspecialchars(
                                (string) $purchase[
                                    'purchase_date'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Status</th>

                        <td>
                            <?php if (
                                $purchase['status'] ===
                                'completed'
                            ): ?>
                                <span class="badge text-bg-success">
                                    Completed
                                </span>
                            <?php elseif (
                                $purchase['status'] ===
                                'cancelled'
                            ): ?>
                                <span class="badge text-bg-danger">
                                    Cancelled
                                </span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">
                                    <?= htmlspecialchars(
                                        (string) $purchase[
                                            'status'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Payment Method</th>

                        <td>
                            <?php if (
                                !empty(
                                    $purchase[
                                        'payment_method'
                                    ]
                                )
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $purchase[
                                        'payment_method'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>VAT Registered</th>

                        <td>
                            <?php if (
                                (int) $purchase[
                                    'vat_registered'
                                ] === 1
                            ): ?>
                                Yes
                            <?php else: ?>
                                No
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Price Mode</th>

                        <td>
                            <?php if (
                                (int) $purchase[
                                    'vat_registered'
                                ] !== 1
                            ): ?>
                                VAT not charged
                            <?php elseif (
                                (int) $purchase[
                                    'prices_include_vat'
                                ] === 1
                            ): ?>
                                VAT included
                            <?php else: ?>
                                VAT excluded
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Default VAT Rate</th>

                        <td>
                            <?= number_format(
                                (float) $purchase[
                                    'default_vat_rate'
                                ],
                                2
                            ) ?>%
                        </td>
                    </tr>

                    <tr>
                        <th>Created By</th>

                        <td>
                            <?php if (
                                !empty(
                                    $purchase['user_name']
                                )
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $purchase[
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
                    </tr>

                    <tr>
                        <th>Created At</th>

                        <td>
                            <?= htmlspecialchars(
                                (string) $purchase[
                                    'created_at'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                Supplier & Warehouse
            </div>

            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Supplier</th>

                        <td>
                            <?php if (
                                !empty(
                                    $purchase[
                                        'supplier_name'
                                    ]
                                )
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $purchase[
                                        'supplier_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                <?php if (
                                    !empty(
                                        $purchase[
                                            'supplier_company_name'
                                        ]
                                    )
                                ): ?>
                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            (string) $purchase[
                                                'supplier_company_name'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    No supplier
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Supplier Phone</th>

                        <td>
                            <?php if (
                                !empty(
                                    $purchase[
                                        'supplier_phone'
                                    ]
                                )
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $purchase[
                                        'supplier_phone'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Supplier Email</th>

                        <td>
                            <?php if (
                                !empty(
                                    $purchase[
                                        'supplier_email'
                                    ]
                                )
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $purchase[
                                        'supplier_email'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Supplier EIK</th>

                        <td>
                            <?php if (
                                !empty(
                                    $purchase[
                                        'supplier_eik'
                                    ]
                                )
                            ): ?>
                                <?= htmlspecialchars(
                                    (string) $purchase[
                                        'supplier_eik'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Warehouse</th>

                        <td>
                            <?= htmlspecialchars(
                                (string) $purchase[
                                    'warehouse_code'
                                ] .
                                ' - ' .
                                (string) $purchase[
                                    'warehouse_name'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        Purchase Items
    </div>

    <div class="card-body">
        <?php if (empty($items)): ?>
            <p class="text-muted mb-0">
                No purchase items found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table
                    class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Code</th>
                            <th>Product</th>
                            <th>Barcode</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Cost</th>
                            <th>Discount</th>
                            <th>Net</th>
                            <th>VAT %</th>
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $items as $item
                        ): ?>
                            <tr>
                                <td>
                                    <?php if (
                                        !empty(
                                            $item[
                                                'image_path'
                                            ]
                                        )
                                    ): ?>
                                        <img
                                            src="<?= htmlspecialchars(
                                                (string) $item[
                                                    'image_path'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            alt="Product image"
                                            style="width: 50px; height: 50px; object-fit: cover;"
                                            class="rounded border">
                                    <?php else: ?>
                                        <span class="text-muted">
                                            No image
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= htmlspecialchars(
                                            (string) $item[
                                                'product_internal_code'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

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
                                    <?php if (
                                        !empty(
                                            $item['barcode']
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            (string) $item[
                                                'barcode'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            -
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $item[
                                            'quantity'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $item[
                                            'unit'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float) $item[
                                            'unit_cost'
                                        ],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float) $item[
                                            'discount_amount'
                                        ],
                                        2
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
                                            'vat_rate'
                                        ],
                                        2
                                    ) ?>%
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
                                                'total_price'
                                            ],
                                            2
                                        ) ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row justify-content-end">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                Totals
            </div>

            <div class="card-body">
                <table class="table mb-0">
                    <tr>
                        <th>Net Subtotal</th>

                        <td class="text-end">
                            <?= number_format(
                                (float) $purchase[
                                    'subtotal'
                                ],
                                2
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Net Discount</th>

                        <td class="text-end">
                            <?= number_format(
                                (float) $purchase[
                                    'discount_amount'
                                ],
                                2
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>VAT</th>

                        <td class="text-end">
                            <?= number_format(
                                (float) $purchase[
                                    'tax_amount'
                                ],
                                2
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Total with VAT</th>

                        <td class="text-end">
                            <strong>
                                <?= number_format(
                                    (float) $purchase[
                                        'total_amount'
                                    ],
                                    2
                                ) ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($purchase['note'])): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            Note
        </div>

        <div class="card-body">
            <?= nl2br(
                htmlspecialchars(
                    (string) $purchase['note'],
                    ENT_QUOTES,
                    'UTF-8'
                )
            ) ?>
        </div>
    </div>
<?php endif; ?>