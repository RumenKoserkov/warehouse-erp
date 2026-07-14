<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Create Sale</h1>

    <a href="/stock" class="btn btn-outline-secondary">
        Back to Stock
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="/sales/store" method="POST" id="saleForm">
    <?= \App\Core\Csrf::field() ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Sale Information
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Sale Number</label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars($saleNumber) ?>"
                        readonly
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Sale Date *</label>

                    <input
                        type="date"
                        name="sale_date"
                        class="form-control"
                        value="<?= htmlspecialchars($saleDate) ?>"
                        required
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Client</label>

                    <select
                        name="client_id"
                        class="form-select"
                    >
                        <option value="">No client</option>

                        <?php foreach ($clients as $client): ?>
                            <option
                                value="<?= htmlspecialchars(
                                    (string) $client['id']
                                ) ?>"
                                <?php if (
                                    (string) $old['client_id'] ===
                                    (string) $client['id']
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?php if (
                                    !empty($client['company_name'])
                                ): ?>
                                    <?= htmlspecialchars(
                                        $client['name'] .
                                        ' - ' .
                                        $client['company_name']
                                    ) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars(
                                        $client['name']
                                    ) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label
                        for="warehouse_id"
                        class="form-label"
                    >
                        Warehouse *
                    </label>

                    <select
                        id="warehouse_id"
                        name="warehouse_id"
                        class="form-select"
                        required
                    >
                        <option value="">Select warehouse</option>

                        <?php foreach ($warehouses as $warehouse): ?>
                            <option
                                value="<?= htmlspecialchars(
                                    (string) $warehouse['id']
                                ) ?>"
                                <?php if (
                                    (string) $old['warehouse_id'] ===
                                    (string) $warehouse['id']
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars(
                                    $warehouse['name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Payment Method
                    </label>

                    <select
                        name="payment_method"
                        class="form-select"
                    >
                        <?php foreach (
                            $paymentMethods as $paymentMethod
                        ): ?>
                            <option
                                value="<?= htmlspecialchars(
                                    $paymentMethod
                                ) ?>"
                                <?php if (
                                    $old['payment_method'] ===
                                    $paymentMethod
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars(
                                    $paymentMethod
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-9 mb-3">
                    <label class="form-label">Note</label>

                    <input
                        type="text"
                        name="note"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $old['note']
                        ) ?>"
                        placeholder="Optional note..."
                    >
                </div>
            </div>
        </div>
    </div>

    <?php
    $lookupContext = 'sale';

    require __DIR__ . '/../components/product_lookup.php';
    ?>

    <div class="card shadow-sm mb-4">
        <div
            class="card-header d-flex justify-content-between align-items-center"
        >
            <span>Sale Items</span>

            <button
                type="button"
                class="btn btn-sm btn-outline-primary"
                id="addRowButton"
            >
                Add Row
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-bordered align-middle"
                    id="saleItemsTable"
                >
                    <thead>
                        <tr>
                            <th style="width: 40%;">
                                Product
                            </th>

                            <th style="width: 15%;">
                                Quantity
                            </th>

                            <th style="width: 15%;">
                                Unit Price
                            </th>

                            <th style="width: 15%;">
                                Discount
                            </th>

                            <th style="width: 15%;">
                                Total
                            </th>

                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr data-item-row>
                            <td>
                                <select
                                    name="product_id[]"
                                    class="form-select product-select"
                                >
                                    <option value="">
                                        Select product
                                    </option>

                                    <?php foreach (
                                        $products as $product
                                    ): ?>
                                        <option
                                            value="<?= htmlspecialchars(
                                                (string) $product['id']
                                            ) ?>"
                                            data-price="<?= htmlspecialchars(
                                                (string) $product[
                                                    'selling_price'
                                                ]
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $product[
                                                    'internal_code'
                                                ] .
                                                ' - ' .
                                                $product['name'] .
                                                ' (' .
                                                $product['unit'] .
                                                ')'
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    min="0.001"
                                    step="0.001"
                                    name="quantity[]"
                                    class="form-control quantity-input"
                                    value="1"
                                >
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="unit_price[]"
                                    class="form-control price-input"
                                    value="0.00"
                                >
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="discount_amount[]"
                                    class="form-control discount-input"
                                    value="0.00"
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    class="form-control total-input"
                                    value="0.00"
                                    readonly
                                >
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger remove-row-button"
                                >
                                    X
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end">
                <div class="col-md-4">
                    <table class="table">
                        <tr>
                            <th>Subtotal</th>

                            <td class="text-end">
                                <span id="subtotalText">
                                    0.00
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <th>Total Discount</th>

                            <td class="text-end">
                                <span id="discountText">
                                    0.00
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <th>Total</th>

                            <td class="text-end">
                                <strong id="totalText">
                                    0.00
                                </strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success">
        Save Sale
    </button>
</form>

<script>
const tableBody = document.querySelector(
    '#saleItemsTable tbody'
);

const addRowButton = document.querySelector(
    '#addRowButton'
);

function calculateRow(row) {
    const quantityInput = row.querySelector(
        '.quantity-input'
    );

    const priceInput = row.querySelector(
        '.price-input'
    );

    const discountInput = row.querySelector(
        '.discount-input'
    );

    const totalInput = row.querySelector(
        '.total-input'
    );

    let quantity = parseFloat(quantityInput.value);
    let price = parseFloat(priceInput.value);
    let discount = parseFloat(discountInput.value);

    if (isNaN(quantity)) {
        quantity = 0;
    }

    if (isNaN(price)) {
        price = 0;
    }

    if (isNaN(discount)) {
        discount = 0;
    }

    let rowTotal = quantity * price - discount;

    if (rowTotal < 0) {
        rowTotal = 0;
    }

    totalInput.value = rowTotal.toFixed(2);
}

function calculateTotals() {
    const rows = tableBody.querySelectorAll(
        '[data-item-row]'
    );

    let subtotal = 0;
    let totalDiscount = 0;
    let total = 0;

    rows.forEach(function (row) {
        const quantityInput = row.querySelector(
            '.quantity-input'
        );

        const priceInput = row.querySelector(
            '.price-input'
        );

        const discountInput = row.querySelector(
            '.discount-input'
        );

        const totalInput = row.querySelector(
            '.total-input'
        );

        let quantity = parseFloat(
            quantityInput.value
        );

        let price = parseFloat(
            priceInput.value
        );

        let discount = parseFloat(
            discountInput.value
        );

        let rowTotal = parseFloat(
            totalInput.value
        );

        if (isNaN(quantity)) {
            quantity = 0;
        }

        if (isNaN(price)) {
            price = 0;
        }

        if (isNaN(discount)) {
            discount = 0;
        }

        if (isNaN(rowTotal)) {
            rowTotal = 0;
        }

        subtotal += quantity * price;
        totalDiscount += discount;
        total += rowTotal;
    });

    document.querySelector(
        '#subtotalText'
    ).textContent = subtotal.toFixed(2);

    document.querySelector(
        '#discountText'
    ).textContent = totalDiscount.toFixed(2);

    document.querySelector(
        '#totalText'
    ).textContent = total.toFixed(2);
}

function bindRowEvents(row) {
    const productSelect = row.querySelector(
        '.product-select'
    );

    const quantityInput = row.querySelector(
        '.quantity-input'
    );

    const priceInput = row.querySelector(
        '.price-input'
    );

    const discountInput = row.querySelector(
        '.discount-input'
    );

    const removeButton = row.querySelector(
        '.remove-row-button'
    );

    productSelect.addEventListener(
        'change',
        function () {
            const selectedOption =
                productSelect.options[
                    productSelect.selectedIndex
                ];

            const price = selectedOption.getAttribute(
                'data-price'
            );

            if (price !== null) {
                priceInput.value =
                    parseFloat(price).toFixed(2);
            } else {
                priceInput.value = '0.00';
            }

            calculateRow(row);
            calculateTotals();
        }
    );

    quantityInput.addEventListener(
        'input',
        function () {
            calculateRow(row);
            calculateTotals();
        }
    );

    priceInput.addEventListener(
        'input',
        function () {
            calculateRow(row);
            calculateTotals();
        }
    );

    discountInput.addEventListener(
        'input',
        function () {
            calculateRow(row);
            calculateTotals();
        }
    );

    removeButton.addEventListener(
        'click',
        function () {
            const rows = tableBody.querySelectorAll(
                '[data-item-row]'
            );

            if (rows.length <= 1) {
                return;
            }

            row.remove();
            calculateTotals();
        }
    );
}

function createNewRow() {
    const firstRow = tableBody.querySelector(
        '[data-item-row]'
    );

    const newRow = firstRow.cloneNode(true);

    newRow.querySelector(
        '.product-select'
    ).value = '';

    newRow.querySelector(
        '.quantity-input'
    ).value = '1';

    newRow.querySelector(
        '.price-input'
    ).value = '0.00';

    newRow.querySelector(
        '.discount-input'
    ).value = '0.00';

    newRow.querySelector(
        '.total-input'
    ).value = '0.00';

    tableBody.appendChild(newRow);

    bindRowEvents(newRow);
}

addRowButton.addEventListener(
    'click',
    function () {
        createNewRow();
    }
);

const initialRows = tableBody.querySelectorAll(
    '[data-item-row]'
);

initialRows.forEach(function (row) {
    bindRowEvents(row);
});
</script>

<script>
document.addEventListener(
    'productLookup:selected',
    function (event) {
        if (event.detail.context !== 'sale') {
            return;
        }

        const product = event.detail.product;

        const warehouseSelect =
            document.getElementById('warehouse_id');

        if (
            warehouseSelect === null ||
            warehouseSelect.value === ''
        ) {
            alert('Please select a warehouse first.');

            return;
        }

        if (Number(product.stock_quantity) <= 0) {
            alert(
                'This product is out of stock in the selected warehouse.'
            );

            return;
        }

        addLookupProductToSale(product);
    }
);

function addLookupProductToSale(product) {
    let rows = document.querySelectorAll(
        '[data-item-row]'
    );

    for (const row of rows) {
        const productSelect = row.querySelector(
            'select[name="product_id[]"]'
        );

        const quantityInput = row.querySelector(
            'input[name="quantity[]"]'
        );

        if (
            productSelect === null ||
            quantityInput === null
        ) {
            continue;
        }

        if (
            productSelect.value ===
            String(product.id)
        ) {
            let currentQuantity =
                Number(quantityInput.value);

            if (!Number.isFinite(currentQuantity)) {
                currentQuantity = 0;
            }

            const newQuantity =
                currentQuantity + 1;

            if (
                newQuantity >
                Number(product.stock_quantity)
            ) {
                alert(
                    'There is not enough stock for this product.'
                );

                return;
            }

            quantityInput.value =
                String(newQuantity);

            quantityInput.dispatchEvent(
                new Event(
                    'input',
                    {
                        bubbles: true
                    }
                )
            );

            return;
        }
    }

    let emptyProductSelect = null;

    for (const row of rows) {
        const productSelect = row.querySelector(
            'select[name="product_id[]"]'
        );

        if (
            productSelect !== null &&
            productSelect.value === ''
        ) {
            emptyProductSelect = productSelect;

            break;
        }
    }

    if (emptyProductSelect === null) {
        const addButton =
            document.getElementById(
                'addRowButton'
            );

        if (addButton === null) {
            alert(
                'Unable to add another product row.'
            );

            return;
        }

        addButton.click();

        rows = document.querySelectorAll(
            '[data-item-row]'
        );

        const lastRow = rows[rows.length - 1];

        if (lastRow !== undefined) {
            emptyProductSelect =
                lastRow.querySelector(
                    'select[name="product_id[]"]'
                );
        }
    }

    if (emptyProductSelect === null) {
        return;
    }

    emptyProductSelect.value =
        String(product.id);

    emptyProductSelect.dispatchEvent(
        new Event(
            'change',
            {
                bubbles: true
            }
        )
    );

    const row = emptyProductSelect.closest(
        '[data-item-row]'
    );

    if (row === null) {
        return;
    }

    const quantityInput = row.querySelector(
        'input[name="quantity[]"]'
    );

    if (quantityInput !== null) {
        quantityInput.value = '1';

        quantityInput.dispatchEvent(
            new Event(
                'input',
                {
                    bubbles: true
                }
            )
        );
    }
}
</script>