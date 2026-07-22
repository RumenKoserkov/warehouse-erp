<div
    class="d-flex justify-content-between
    align-items-center mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Invoices
        </h1>

        <p class="text-muted mb-0">
            Invoice drafts and issued invoices.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <?php
        $csvExportPath =
            '/exports/invoices.csv';

        require __DIR__ .
            '/../partials/csv_export_button.php';
        ?>

        <a
            href="/invoices/create"
            class="btn btn-primary"
        >
            Create Invoice Draft
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div
            class="d-flex flex-column
            flex-lg-row justify-content-between
            align-items-lg-center gap-3"
        >
            <div>
                <h2 class="h5 mb-2">
                    Invoice Number Sequence
                </h2>

                <div>
                    Next official invoice number:

                    <strong class="font-monospace">
                        <?= htmlspecialchars(
                            (string) $sequence[
                                'next_invoice_number'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

                <div class="text-muted">
                    Last issued:

                    <?php if (
                        $sequence[
                            'last_invoice_number'
                        ] !== null
                    ): ?>
                        <span class="font-monospace">
                            <?= htmlspecialchars(
                                (string) $sequence[
                                    'last_invoice_number'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    <?php else: ?>
                        No invoices issued yet
                    <?php endif; ?>
                </div>
            </div>

            <?php if (
                $canConfigureSequence &&
                $sequence['can_change_start']
            ): ?>
                <form
                    method="POST"
                    action="/invoices/sequence/update"
                    class="d-flex align-items-end gap-2"
                >
                    <?= \App\Core\Csrf::field() ?>

                    <div>
                        <label
                            for="next_number"
                            class="form-label"
                        >
                            Starting number
                        </label>

                        <input
                            type="number"
                            id="next_number"
                            name="next_number"
                            class="form-control"
                            min="1"
                            max="9999999999"
                            step="1"
                            required
                            value="<?= (int) $sequence[
                                'next_number'
                            ] ?>"
                        >
                    </div>

                    <button
                        type="submit"
                        class="btn btn-outline-primary"
                    >
                        Save
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (
            $canConfigureSequence &&
            !$sequence['can_change_start']
        ): ?>
            <div class="form-text mt-3">
                The starting number is locked because
                at least one invoice has already been issued.
            </div>
        <?php elseif (
            $canConfigureSequence &&
            $sequence['can_change_start']
        ): ?>
            <div class="form-text mt-3">
                Set this only when the company needs to
                continue an existing invoice sequence.
                It cannot be changed after the first
                invoice is issued.
            </div>
        <?php endif; ?>
    </div>
</div>

<form
    method="GET"
    action="/invoices"
    class="row g-2 mb-4"
>
    <div class="col-lg-7">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by client, EIK, VAT number or invoice number..."
            value="<?= htmlspecialchars(
                $search,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    </div>

    <div class="col-lg-3">
        <select
            name="due_filter"
            class="form-select"
        >
            <?php
            $dueFilters = [
                'all' =>
                    'All Documents',

                'overdue' =>
                    'Overdue Invoices',

                'due_today' =>
                    'Due Today',

                'due_soon' =>
                    'Due Within 7 Days',

                'unpaid' =>
                    'Unpaid',

                'partially_paid' =>
                    'Partially Paid',

                'paid' =>
                    'Paid',

                'no_due_date' =>
                    'No Due Date',
            ];
            ?>

            <?php foreach (
                $dueFilters as
                $value => $label
            ): ?>
                <option
                    value="<?= htmlspecialchars(
                        $value,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    <?php if (
                        $dueFilter === $value
                    ): ?>
                        selected
                    <?php endif; ?>
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

    <div class="col-lg-2 d-grid">
        <button
            type="submit"
            class="btn btn-outline-primary"
        >
            Filter
        </button>
    </div>
</form>

<?php if (empty($invoices)): ?>
    <div class="alert alert-info">
        No invoices found.
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Client</th>
                        <th>Document Status</th>
                        <th>Balance</th>
                        <th>Due Status</th>
                        <th>Total</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $invoices as $invoice
                    ): ?>
                        <?php
                        $reference =
                            'DRAFT-' .
                            (int) $invoice['id'];

                        if (
                            isset(
                                $invoice[
                                    'invoice_number'
                                ]
                            ) &&
                            trim(
                                (string) $invoice[
                                    'invoice_number'
                                ]
                            ) !== ''
                        ) {
                            $reference =
                                (string) $invoice[
                                    'invoice_number'
                                ];
                        }
                        ?>

                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        $reference,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?php if (
                                    (string) $invoice[
                                        'document_type'
                                    ] === 'credit_note'
                                ): ?>
                                    <span
                                        class="badge text-bg-info"
                                    >
                                        Credit Note
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge text-bg-primary"
                                    >
                                        Invoice
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $invoice[
                                        'invoice_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    isset(
                                        $invoice['due_date']
                                    ) &&
                                    $invoice['due_date'] !== null &&
                                    trim(
                                        (string) $invoice[
                                            'due_date'
                                        ]
                                    ) !== ''
                                ): ?>
                                    <?= htmlspecialchars(
                                        (string) $invoice[
                                            'due_date'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $invoice[
                                        'client_legal_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    (string) $invoice[
                                        'status'
                                    ] === 'cancelled'
                                ): ?>
                                    <span
                                        class="badge text-bg-danger"
                                    >
                                        Cancelled
                                    </span>
                                <?php elseif (
                                    (string) $invoice[
                                        'status'
                                    ] === 'draft'
                                ): ?>
                                    <span
                                        class="badge text-bg-warning"
                                    >
                                        Draft
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge text-bg-success"
                                    >
                                        Issued
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (
                                    (string) $invoice[
                                        'document_type'
                                    ] === 'invoice'
                                ): ?>
                                    <strong>
                                        <?= number_format(
                                            (float) $invoice[
                                                'balance_due'
                                            ],
                                            2
                                        ) ?>

                                        <?= htmlspecialchars(
                                            (string) $invoice[
                                                'currency'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                $dueInformation =
                                    $invoice[
                                        'due_information'
                                    ];
                                ?>

                                <?php if (
                                    $dueInformation[
                                        'status'
                                    ] === 'not_applicable'
                                ): ?>
                                    —
                                <?php else: ?>
                                    <span
                                        class="badge <?= htmlspecialchars(
                                            (string) $dueInformation[
                                                'badge_class'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            (string) $dueInformation[
                                                'label'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <?php if (
                                        $dueInformation[
                                            'status'
                                        ] === 'overdue'
                                    ): ?>
                                        <div
                                            class="
                                                small text-danger mt-1
                                            "
                                        >
                                            <?= (int) $dueInformation[
                                                'days_overdue'
                                            ] ?>
                                            day(s) overdue
                                        </div>
                                    <?php elseif (
                                        $dueInformation[
                                            'status'
                                        ] === 'due_soon'
                                    ): ?>
                                        <div
                                            class="
                                                small text-muted mt-1
                                            "
                                        >
                                            <?= (int) $dueInformation[
                                                'days_until_due'
                                            ] ?>
                                            day(s) remaining
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $invoice[
                                        'total_amount'
                                    ],
                                    2
                                ) ?>

                                <?= htmlspecialchars(
                                    (string) $invoice[
                                        'currency'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $invoice[
                                        'created_by_user_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <div
                                    class="
                                        d-flex flex-wrap gap-1
                                    "
                                >
                                    <a
                                        href="/invoices/show?id=<?= (int) $invoice[
                                            'id'
                                        ] ?>"
                                        class="
                                            btn btn-sm
                                            btn-outline-primary
                                        "
                                    >
                                        View
                                    </a>

                                    <a
                                        href="/invoices/print?id=<?= (int) $invoice[
                                            'id'
                                        ] ?>"
                                        target="_blank"
                                        rel="noopener"
                                        class="
                                            btn btn-sm
                                            btn-outline-dark
                                        "
                                    >
                                        Print
                                    </a>

                                    <a
                                        href="/invoices/pdf?id=<?= (int) $invoice[
                                            'id'
                                        ] ?>"
                                        class="
                                            btn btn-sm
                                            btn-outline-secondary
                                        "
                                    >
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>