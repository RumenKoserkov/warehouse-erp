<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Purchases</h1>

    <div class="d-flex gap-2">
        <?php
        $csvExportPath =
            '/exports/purchases.csv';

        require __DIR__ .
            '/../partials/csv_export_button.php';
        ?>

        <a
            href="/purchases/create"
            class="btn btn-success"
        >
            New Purchase
        </a>
    </div>
</div>

<form
    method="GET"
    action="/purchases"
    class="mb-3"
>
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by purchase number, supplier, warehouse, user..."
            value="<?= htmlspecialchars(
                $search,
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
        <?php if (empty($purchases)): ?>
            <p class="text-muted mb-0">
                No purchases found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table
                    class="
                        table table-striped table-hover
                        align-middle mb-0
                    "
                >
                    <thead>
                        <tr>
                            <th>Purchase Number</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th>User</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($purchases as $purchase): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $purchase[
                                                'purchase_number'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $purchase[
                                            'purchase_date'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        !empty(
                                            $purchase[
                                                'supplier_name'
                                            ]
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            $purchase[
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
                                                    $purchase[
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

                                <td>
                                    <?= htmlspecialchars(
                                        $purchase[
                                            'warehouse_code'
                                        ] .
                                        ' - ' .
                                        $purchase[
                                            'warehouse_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        !empty(
                                            $purchase[
                                                'user_name'
                                            ]
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            $purchase[
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

                                <td>
                                    <?php if (
                                        !empty(
                                            $purchase[
                                                'payment_method'
                                            ]
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            $purchase[
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

                                <td>
                                    <?php if (
                                        $purchase['status'] ===
                                        'completed'
                                    ): ?>
                                        <span
                                            class="
                                                badge text-bg-success
                                            "
                                        >
                                            Completed
                                        </span>
                                    <?php elseif (
                                        $purchase['status'] ===
                                        'cancelled'
                                    ): ?>
                                        <span
                                            class="
                                                badge text-bg-danger
                                            "
                                        >
                                            Cancelled
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="
                                                badge text-bg-secondary
                                            "
                                        >
                                            <?= htmlspecialchars(
                                                $purchase[
                                                    'status'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            (string) $purchase[
                                                'total_amount'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $purchase[
                                            'created_at'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a
                                            href="/purchases/show?id=<?= htmlspecialchars(
                                                (string) $purchase[
                                                    'id'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            class="
                                                btn btn-sm
                                                btn-outline-primary
                                            "
                                        >
                                            View
                                        </a>

                                        <?php if (
                                            $purchase['status'] ===
                                            'completed'
                                        ): ?>
                                            <form
                                                action="/purchases/cancel"
                                                method="POST"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to cancel this purchase and decrease the stock?'
                                                    );
                                                "
                                            >
                                                <?= \App\Core\Csrf::field() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= htmlspecialchars(
                                                        (string) $purchase[
                                                            'id'
                                                        ],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="
                                                        btn btn-sm
                                                        btn-outline-danger
                                                    "
                                                >
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>