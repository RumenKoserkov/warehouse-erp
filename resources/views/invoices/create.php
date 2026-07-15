<div class="row justify-content-center">
    <div class="col-xl-11">
        <div class="d-flex justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">
                    Create Invoice Draft
                </h1>

                <p class="text-muted mb-0">
                    The official invoice number will be
                    assigned when the draft is issued.
                </p>
            </div>

            <a
                href="/invoices"
                class="btn btn-outline-secondary"
            >
                Back
            </a>
        </div>

        <div class="alert alert-warning">
            This operation creates an invoice draft only.
            It does not change stock quantities.
            For warehouse products, create a Sale first.
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?= htmlspecialchars(
                                $error,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form
            method="POST"
            action="/invoices/store"
            id="invoice-form"
        >
            <?= \App\Core\Csrf::field() ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label
                                for="client_id"
                                class="form-label"
                            >
                                Client
                            </label>

                            <select
                                id="client_id"
                                name="client_id"
                                class="form-select"
                                required
                            >
                                <option value="">
                                    Select client
                                </option>

                                <?php foreach ($clients as $client): ?>
                                    <option
                                        value="<?= (int) $client['id'] ?>"
                                        <?php if (
                                            (string) $old['client_id'] ===
                                            (string) $client['id']
                                        ): ?>
                                            selected
                                        <?php endif; ?>
                                    >
                                        <?= htmlspecialchars(
                                            (string) $client['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <?php if (
                                            isset(
                                                $client[
                                                    'company_name'
                                                ]
                                            ) &&
                                            trim(
                                                (string) $client[
                                                    'company_name'
                                                ]
                                            ) !== ''
                                        ): ?>
                                            —
                                            <?= htmlspecialchars(
                                                (string) $client[
                                                    'company_name'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label
                                for="invoice_date"
                                class="form-label"
                            >
                                Invoice Date
                            </label>

                            <input
                                type="date"
                                id="invoice_date"
                                name="invoice_date"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars(
                                    (string) $old[
                                        'invoice_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-2">
                            <label
                                for="supply_date"
                                class="form-label"
                            >
                                Supply Date
                            </label>

                            <input
                                type="date"
                                id="supply_date"
                                name="supply_date"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars(
                                    (string) $old[
                                        'supply_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-2">
                            <label
                                for="due_date"
                                class="form-label"
                            >
                                Due Date
                            </label>

                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    (string) $old[
                                        'due_date'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div
                    class="card-header d-flex
                    justify-content-between
                    align-items-center"
                >
                    <h2 class="h5 mb-0">
                        Invoice Items
                    </h2>

                    <button
                        type="button"
                        id="add-invoice-item"
                        class="btn btn-sm
                        btn-outline-primary"
                    >
                        Add Product
                    </button>
                </div>

                <div class="table-responsive">
                    <table
                        class="table align-middle mb-0"
                    >
                        <thead>
                            <tr>
                                <th style="min-width: 250px;">
                                    Product
                                </th>

                                <th style="width: 130px;">
                                    Quantity
                                </th>

                                <th style="width: 150px;">
                                    Unit Price
                                </th>

                                <th style="width: 140px;">
                                    Discount
                                </th>

                                <th style="width: 130px;">
                                    Total
                                </th>

                                <th style="width: 70px;"></th>
                            </tr>
                        </thead>

                        <tbody id="invoice-items-body">
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7">
                    <label
                        for="note"
                        class="form-label"
                    >
                        Note
                    </label>

                    <textarea
                        id="note"
                        name="note"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars(
                        (string) $old['note'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>
                </div>

                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div
                                class="d-flex
                                justify-content-between"
                            >
                                <span>Subtotal</span>

                                <strong id="preview-subtotal">
                                    0.00
                                </strong>
                            </div>

                            <div
                                class="d-flex
                                justify-content-between"
                            >
                                <span>Discount</span>

                                <strong id="preview-discount">
                                    0.00
                                </strong>
                            </div>

                            <div
                                class="d-flex
                                justify-content-between"
                            >
                                <span>
                                    VAT
                                    (<?= number_format(
                                        (float) $taxConfiguration[
                                            'vat_rate'
                                        ],
                                        2
                                    ) ?>%)
                                </span>

                                <strong id="preview-tax">
                                    0.00
                                </strong>
                            </div>

                            <hr>

                            <div
                                class="d-flex
                                justify-content-between
                                fs-5"
                            >
                                <span>Total</span>

                                <strong id="preview-total">
                                    0.00
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Invoice Draft
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const products =
            <?= json_encode(
                $products,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ) ?>;

        const taxConfiguration =
            <?= json_encode(
                $taxConfiguration,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ) ?>;

        const itemsBody =
            document.getElementById(
                'invoice-items-body'
            );

        const addButton =
            document.getElementById(
                'add-invoice-item'
            );

        const previewSubtotal =
            document.getElementById(
                'preview-subtotal'
            );

        const previewDiscount =
            document.getElementById(
                'preview-discount'
            );

        const previewTax =
            document.getElementById(
                'preview-tax'
            );

        const previewTotal =
            document.getElementById(
                'preview-total'
            );

        function productOptions() {
            let html =
                '<option value="">Select product</option>';

            products.forEach(function (product) {
                html +=
                    '<option value="' +
                    Number(product.id) +
                    '" data-price="' +
                    Number(
                        product.selling_price
                    ).toFixed(2) +
                    '">' +
                    escapeHtml(
                        String(
                            product.internal_code
                        )
                    ) +
                    ' — ' +
                    escapeHtml(
                        String(product.name)
                    ) +
                    '</option>';
            });

            return html;
        }

        function addRow() {
            const row =
                document.createElement('tr');

            row.innerHTML =
                '<td>' +
                    '<select name="product_id[]" ' +
                    'class="form-select product-select" ' +
                    'required>' +
                        productOptions() +
                    '</select>' +
                '</td>' +

                '<td>' +
                    '<input type="number" ' +
                    'name="quantity[]" ' +
                    'class="form-control quantity-input" ' +
                    'min="0.001" step="0.001" ' +
                    'value="1" required>' +
                '</td>' +

                '<td>' +
                    '<input type="number" ' +
                    'name="unit_price[]" ' +
                    'class="form-control price-input" ' +
                    'min="0" step="0.01" ' +
                    'value="0.00" required>' +
                '</td>' +

                '<td>' +
                    '<input type="number" ' +
                    'name="discount_amount[]" ' +
                    'class="form-control discount-input" ' +
                    'min="0" step="0.01" ' +
                    'value="0.00">' +
                '</td>' +

                '<td>' +
                    '<strong class="line-total">' +
                        '0.00' +
                    '</strong>' +
                '</td>' +

                '<td>' +
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-danger ' +
                    'remove-row">' +
                        '×' +
                    '</button>' +
                '</td>';

            itemsBody.appendChild(row);

            bindRow(row);
            calculateTotals();
        }

        function bindRow(row) {
            const productSelect =
                row.querySelector(
                    '.product-select'
                );

            const quantityInput =
                row.querySelector(
                    '.quantity-input'
                );

            const priceInput =
                row.querySelector(
                    '.price-input'
                );

            const discountInput =
                row.querySelector(
                    '.discount-input'
                );

            const removeButton =
                row.querySelector(
                    '.remove-row'
                );

            productSelect.addEventListener(
                'change',
                function () {
                    const selectedOption =
                        productSelect.options[
                            productSelect.selectedIndex
                        ];

                    if (
                        selectedOption !== undefined &&
                        selectedOption.dataset.price !==
                        undefined
                    ) {
                        priceInput.value =
                            selectedOption.dataset.price;
                    }

                    calculateTotals();
                }
            );

            quantityInput.addEventListener(
                'input',
                calculateTotals
            );

            priceInput.addEventListener(
                'input',
                calculateTotals
            );

            discountInput.addEventListener(
                'input',
                calculateTotals
            );

            removeButton.addEventListener(
                'click',
                function () {
                    row.remove();

                    if (
                        itemsBody.children.length === 0
                    ) {
                        addRow();
                    }

                    calculateTotals();
                }
            );
        }

        function calculateTotals() {
            let subtotal = 0;
            let totalDiscount = 0;
            let totalTax = 0;
            let total = 0;

            const rows =
                itemsBody.querySelectorAll('tr');

            rows.forEach(function (row) {
                const quantity =
                    Number(
                        row.querySelector(
                            '.quantity-input'
                        ).value
                    );

                const unitPrice =
                    Number(
                        row.querySelector(
                            '.price-input'
                        ).value
                    );

                const discount =
                    Number(
                        row.querySelector(
                            '.discount-input'
                        ).value
                    );

                const lineGross =
                    quantity * unitPrice;

                let lineAfterDiscount =
                    lineGross - discount;

                if (lineAfterDiscount < 0) {
                    lineAfterDiscount = 0;
                }

                let lineNet =
                    lineAfterDiscount;

                let lineTax = 0;

                const vatRegistered =
                    taxConfiguration.vat_registered ===
                    true;

                const pricesIncludeVat =
                    taxConfiguration.prices_include_vat ===
                    true;

                let vatRate =
                    Number(
                        taxConfiguration.vat_rate
                    );

                if (!vatRegistered) {
                    vatRate = 0;
                }

                if (
                    pricesIncludeVat &&
                    vatRate > 0
                ) {
                    lineNet =
                        lineAfterDiscount /
                        (1 + vatRate / 100);

                    lineTax =
                        lineAfterDiscount -
                        lineNet;
                } else {
                    lineTax =
                        lineNet *
                        (vatRate / 100);
                }

                const lineTotal =
                    lineNet + lineTax;

                subtotal += lineGross;
                totalDiscount += discount;
                totalTax += lineTax;
                total += lineTotal;

                row.querySelector(
                    '.line-total'
                ).textContent =
                    lineTotal.toFixed(2);
            });

            previewSubtotal.textContent =
                subtotal.toFixed(2);

            previewDiscount.textContent =
                totalDiscount.toFixed(2);

            previewTax.textContent =
                totalTax.toFixed(2);

            previewTotal.textContent =
                total.toFixed(2);
        }

        function escapeHtml(value) {
            return value
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        addButton.addEventListener(
            'click',
            addRow
        );

        addRow();
    }
);
</script>