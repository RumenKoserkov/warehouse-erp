<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Create Sales Return
        </h1>

        <p class="text-muted mb-0">
            Sale:
            <strong>
                <?= htmlspecialchars(
                    (string) $sale[
                        'sale_number'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>
        </p>
    </div>

    <a
        href="/sales/show?id=<?= (int) $sale['id'] ?>"
        class="btn btn-outline-secondary"
    >
        Back to Sale
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach (
                $errors as $error
            ): ?>
                <li>
                    <?= htmlspecialchars(
                        (string) $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <strong>Return Quantity</strong>
    is the quantity returned by the customer.

    <strong>Restock Quantity</strong>
    is the quantity that can be returned to
    available warehouse stock.
</div>

<form
    method="POST"
    action="/sales-returns/store"
>
    <?= \App\Core\Csrf::field() ?>

    <input
        type="hidden"
        name="sale_id"
        value="<?= (int) $sale['id'] ?>"
    >

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label
                        for="return_date"
                        class="form-label"
                    >
                        Return Date
                    </label>

                    <input
                        type="date"
                        id="return_date"
                        name="return_date"
                        class="form-control"
                        min="<?= htmlspecialchars(
                            (string) $sale[
                                'sale_date'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        max="<?= date('Y-m-d') ?>"
                        required
                        value="<?= htmlspecialchars(
                            (string) $old[
                                'return_date'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label
                        for="reason_type"
                        class="form-label"
                    >
                        Reason Type
                    </label>

                    <select
                        id="reason_type"
                        name="reason_type"
                        class="form-select"
                        required
                    >
                        <?php foreach (
                            $reasonTypes as
                            $value => $label
                        ): ?>
                            <option
                                value="<?= htmlspecialchars(
                                    $value,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $old[
                                    'reason_type'
                                ] === $value
                                    ? 'selected'
                                    : '' ?>
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

                <div class="col-md-5">
                    <label
                        for="reason_description"
                        class="form-label"
                    >
                        Reason Description
                    </label>

                    <input
                        type="text"
                        id="reason_description"
                        name="reason_description"
                        class="form-control"
                        maxlength="500"
                        required
                        value="<?= htmlspecialchars(
                            (string) $old[
                                'reason_description'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div class="col-12">
                    <label
                        for="notes"
                        class="form-label"
                    >
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        class="form-control"
                        maxlength="2000"
                        rows="3"
                    ><?= htmlspecialchars(
                        (string) $old['notes'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h2 class="h5 mb-0">
                Return Items
            </h2>
        </div>

        <div class="table-responsive">
            <table
                class="table align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Sold</th>
                        <th>Returned</th>
                        <th>Remaining</th>
                        <th style="width: 155px;">
                            Return Qty
                        </th>
                        <th style="width: 155px;">
                            Restock Qty
                        </th>
                        <th>Unit Price</th>
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

                        $remaining =
                            (float) $item[
                                'remaining_quantity'
                            ];
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
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $item[
                                        'quantity'
                                    ],
                                    3
                                ) ?>
                                <?= htmlspecialchars(
                                    (string) $item['unit'],
                                    ENT_QUOTES,
                                    'UTF-8'
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
                                <strong>
                                    <?= number_format(
                                        $remaining,
                                        3
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?php if (
                                    $remaining >
                                    0.0005
                                ): ?>
                                    <input
                                        type="number"
                                        name="return_quantity[<?= $itemId ?>]"
                                        class="form-control return-quantity"
                                        min="0"
                                        max="<?= htmlspecialchars(
                                            number_format(
                                                $remaining,
                                                3,
                                                '.',
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        step="0.001"
                                        data-item-id="<?= $itemId ?>"
                                        value="<?= htmlspecialchars(
                                            (string) (
                                                $old[
                                                    'return_quantity'
                                                ][$itemId] ??
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                <?php else: ?>
                                    Fully Returned
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (
                                    $remaining >
                                    0.0005
                                ): ?>
                                    <input
                                        type="number"
                                        name="restock_quantity[<?= $itemId ?>]"
                                        class="form-control restock-quantity"
                                        min="0"
                                        max="<?= htmlspecialchars(
                                            number_format(
                                                $remaining,
                                                3,
                                                '.',
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        step="0.001"
                                        data-item-id="<?= $itemId ?>"
                                        value="<?= htmlspecialchars(
                                            (string) (
                                                $old[
                                                    'restock_quantity'
                                                ][$itemId] ??
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $item[
                                        'unit_price'
                                    ],
                                    4
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    $remaining >
                                    0.0005
                                ): ?>
                                    <input
                                        type="text"
                                        name="item_note[<?= $itemId ?>]"
                                        class="form-control"
                                        maxlength="500"
                                        value="<?= htmlspecialchars(
                                            (string) (
                                                $old[
                                                    'item_note'
                                                ][$itemId] ??
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            <button
                type="button"
                id="copy-return-to-restock"
                class="btn btn-outline-secondary"
            >
                Restock All Returned Quantities
            </button>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Create Draft
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const button =
            document.getElementById(
                'copy-return-to-restock'
            );

        if (button === null) {
            return;
        }

        button.addEventListener(
            'click',
            function () {
                const returnInputs =
                    document.querySelectorAll(
                        '.return-quantity'
                    );

                returnInputs.forEach(
                    function (returnInput) {
                        const itemId =
                            returnInput.dataset.itemId;

                        const restockInput =
                            document.querySelector(
                                '.restock-quantity' +
                                '[data-item-id="' +
                                itemId +
                                '"]'
                            );

                        if (
                            restockInput !== null
                        ) {
                            restockInput.value =
                                returnInput.value;
                        }
                    }
                );
            }
        );
    }
);
</script>