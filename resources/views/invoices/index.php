<div
    class="d-flex justify-content-between
    align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">
            Invoices
        </h1>

        <p class="text-muted mb-0">
            Invoice drafts and issued invoices.
        </p>
    </div>

    <a
        href="/invoices/create"
        class="btn btn-primary">
        Create Invoice Draft
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div
            class="d-flex flex-column
            flex-lg-row justify-content-between
            align-items-lg-center gap-3">
            <div>
                <h2 class="h5 mb-2">
                    Invoice Number Sequence
                </h2>

                <div>
                    Next official invoice number:

                    <strong class="font-monospace">
                        <?= htmlspecialchars(
                            (string) $sequence['next_invoice_number'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

                <div class="text-muted">
                    Last issued:

                    <?php if (
                        $sequence['last_invoice_number'] !== null
                    ): ?>
                        <span class="font-monospace">
                            <?= htmlspecialchars(
                                (string) $sequence['last_invoice_number'],
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
                    class="d-flex align-items-end gap-2">
                    <?= \App\Core\Csrf::field() ?>

                    <div>
                        <label
                            for="next_number"
                            class="form-label">
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
                            value="<?= (int) $sequence['next_number'] ?>">
                    </div>

                    <button
                        type="submit"
                        class="btn btn-outline-primary">
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
    class="row g-2 mb-4">
    <div class="col-md-10">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by client, EIK, VAT number or invoice number..."
            value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">
    </div>

    <div class="col-md-2 d-grid">
        <button
            type="submit"
            class="btn btn-outline-primary">
            Search
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
                align-middle mb-0">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <?php
                        $reference =
                            'DRAFT-' .
                            (int) $invoice['id'];

                        if (
                            isset(
                                $invoice['invoice_number']
                            ) &&
                            trim(
                                (string) $invoice['invoice_number']
                            ) !== ''
                        ) {
                            $reference =
                                (string) $invoice['invoice_number'];
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
                                <?= htmlspecialchars(
                                    (string) $invoice['invoice_date'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $invoice['client_legal_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    (string) $invoice['status'] === 'draft'
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-warning">
                                        Draft
                                    </span>
                                <?php elseif (
                                    (string) $invoice['status'] === 'issued'
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-success">
                                        Issued
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge
                                        text-bg-secondary">
                                        <?= htmlspecialchars(
                                            ucfirst(
                                                (string) $invoice['status']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $invoice['total_amount'],
                                    2
                                ) ?>

                                <?= htmlspecialchars(
                                    (string) $invoice['currency'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $invoice['created_by_user_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/invoices/show?id=<?= (int) $invoice['id'] ?>"
                                    class="btn btn-sm
                                    btn-outline-primary">
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