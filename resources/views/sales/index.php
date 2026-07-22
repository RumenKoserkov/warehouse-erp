<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Sales</h1>

    <div class="d-flex gap-2">
        <?php
        $csvExportPath =
            '/exports/sales.csv';

        require __DIR__ .
            '/../partials/csv_export_button.php';
        ?>

        <a
            href="/sales/create"
            class="btn btn-success"
        >
            New Sale
        </a>
    </div>
</div>

<form method="GET" action="/sales" class="mb-3">
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by sale number, client, warehouse, user..."
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
        <?php if (empty($sales)): ?>
            <p class="text-muted mb-0">
                No sales found.
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
                            <th>Sale Number</th>
                            <th>Date</th>
                            <th>Client</th>
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
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $sale['sale_number'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $sale['sale_date'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        !empty($sale['client_name'])
                                    ): ?>
                                        <?= htmlspecialchars(
                                            $sale['client_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $sale[
                                                    'client_company_name'
                                                ]
                                            )
                                        ): ?>
                                            <br>

                                            <small class="text-muted">
                                                <?= htmlspecialchars(
                                                    $sale[
                                                        'client_company_name'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            No client
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $sale['warehouse_code'] .
                                        ' - ' .
                                        $sale['warehouse_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        !empty($sale['user_name'])
                                    ): ?>
                                        <?= htmlspecialchars(
                                            $sale['user_name'],
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
                                            $sale['payment_method']
                                        )
                                    ): ?>
                                        <?= htmlspecialchars(
                                            $sale['payment_method'],
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
                                        $sale['status'] ===
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
                                        $sale['status'] ===
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
                                                $sale['status'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            (string) $sale[
                                                'total_amount'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $sale['created_at'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a
                                            href="/sales/show?id=<?= htmlspecialchars(
                                                (string) $sale['id'],
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
                                            $sale['status'] ===
                                            'completed'
                                        ): ?>
                                            <form
                                                action="/sales/cancel"
                                                method="POST"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to cancel this sale and return the stock?'
                                                    );
                                                "
                                            >
                                                <?= \App\Core\Csrf::field() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= htmlspecialchars(
                                                        (string) $sale[
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