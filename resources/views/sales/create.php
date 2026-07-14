
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

<div class="alert alert-light border">
    <?php if (
        !$taxConfiguration['vat_registered']
    ): ?>
        Company is not VAT registered.
        VAT will not be charged.
    <?php elseif (
        $taxConfiguration['prices_include_vat']
    ): ?>
        Entered selling prices include VAT.
    <?php else: ?>
        VAT will be added to the entered selling prices.
    <?php endif; ?>
</div>

<form
    action="/sales/store"
    method="POST"
    id="saleForm">
    <?= \App\Core\Csrf::field() ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Sale Information
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Sale Number
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars(
                                    $saleNumber,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                        readonly>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Sale Date *
                    </label>

                    <input
                        type="date"
                        name="sale_date"
                        class="form-control"
                        value="<?= htmlspecialchars(
                                    $saleDate,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                        required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Client
                    </label>

                    <select
                        name="client_id"
                        class="form-select">
                        <option value="">
                            No client
                        </option>

                        <?php foreach ($clients as $client): ?>
                            <option
                                value="<?= htmlspecialchars(
                                            (string) $client['id'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                <?php if (
                                    (string) $old['client_id'] ===
                                    (string) $client['id']
                                ): ?>
                                selected
                                <?php endif; ?>>
                                <?php if (
                                    !empty($client['company_name'])
                                ): ?>
                                    <?= htmlspecialchars(
                                        $client['name'] .
                                            ' - ' .
                                            $client['company_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars(
                                        $client['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label
                        for="warehouse_id"
                        class="form-label">
                        Warehouse *
                    </label>

                    <select
                        id="warehouse_id"
                        name="warehouse_id"
                        class="form-select"
                        required>
                        <option value="">
                            Select warehouse
                        </option>

                        <?php foreach (
                            $warehouses as $warehouse
                        ): ?>
                            <option
                                value="<?= htmlspecialchars(
                                            (string) $warehouse['id'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                <?php if (
                                    (string) $old['warehouse_id'] ===
                                    (string) $warehouse['id']
                                ): ?>
                                selected
                                <?php endif; ?>>
                                <?= htmlspecialchars(
                                    $warehouse['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
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
                        class="form-select">
                        <?php foreach (
                            $paymentMethods
                            as $paymentMethod
                        ): ?>
                            <option
                                value="<?= htmlspecialchars(
                                            $paymentMethod,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                <?php if (
                                    $old['payment_method'] === $paymentMethod
                                ): ?>
                                selected
                                <?php endif; ?>>
                                <?= htmlspecialchars(
                                    $paymentMethod,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-9 mb-3">
                    <label class="form-label">
                        Note
                    </label>

                    <input
                        type="text"
                        name="note"
                        class="form-control"
                        value="<?= htmlspecialchars(
                                    $old['note'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                        placeholder="Optional note...">
                </div>
            </div>
        </div>
    </div>

    <?php
    $lookupContext = 'sale';

    require __DIR__ .
        '/../components/product_lookup.php';
    ?>

    <div class="card shadow-sm mb-4">
        <div
            class="card-header d-flex justify-content-between align-items-center">
            <span>Sale Items</span>

            <button
                type="button"
                class="btn btn-sm btn-outline-primary"
                id="addRowButton">
                Add Row
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-bordered align-middle"
                    id="saleItemsTable">
                    <thead>
                        <tr>
                            <th style="width: 36%;">
                                Product
                            </th>

                            <th style="width: 14%;">
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

                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr data-item-row>
                            <td>
                                <select
                                    name="product_id[]"
                                    class="form-select product-select">
                                    <option value="">
                                        Select product
                                    </option>

                                    <?php foreach (
                                        $products as $product
                                    ): ?>
                                        <option
                                            value="<?= htmlspecialchars(
                                                        (string) $product['id'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                            data-price="<?= htmlspecialchars(
                                                            (string) $product['selling_price'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">
                                            <?= htmlspecialchars(
                                                $product['internal_code'] .
                                                    ' - ' .
                                                    $product['name'] .
                                                    ' (' .
                                                    $product['unit'] .
                                                    ')',
                                                ENT_QUOTES,
                                                'UTF-8'
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
                                    value="1">
                            </td>

                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    name="unit_price[]"
                                    class="form-control price-input"
                                    value="0.00">
                            </td>

                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    name="discount_amount[]"
                                    class="form-control discount-input"
                                    value="0.00">
                            </td>

                            <td>
                                <input
                                    type="text"
                                    class="form-control total-input"
                                    value="0.00"
                                    readonly>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger remove-row-button">
                                    X
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end">
                <div class="col-md-5 col-lg-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Net Subtotal</span>

                        <strong id="net-subtotal">
                            0.00
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Net Discount</span>

                        <strong id="total-discount">
                            0.00
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>
                            VAT
                            (<?= number_format(
                                    (float) $taxConfiguration['vat_rate'],
                                    2
                                ) ?>%)
                        </span>

                        <strong id="tax-total">
                            0.00
                        </strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <span>Total</span>

                        <strong id="grand-total">
                            0.00
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success">
        Save Sale
    </button>
</form>

<script>
    const vatRegistered =
        <?= $taxConfiguration['vat_registered']
            ? 'true'
            : 'false' ?>;

    const pricesIncludeVat =
        <?= $taxConfiguration['prices_include_vat']
            ? 'true'
            : 'false' ?>;

    const configuredVatRate =
        <?= json_encode(
            (float) $taxConfiguration['vat_rate']
        ) ?>;

    const effectiveVatRate =
        vatRegistered ?
        configuredVatRate :
        0;

    const tableBody = document.querySelector(
        '#saleItemsTable tbody'
    );

    const addRowButton = document.querySelector(
        '#addRowButton'
    );

    function money(amount) {
        return Math.round(
            (amount + Number.EPSILON) * 100
        ) / 100;
    }

    function numberValue(input) {
        const value = parseFloat(input.value);

        if (!Number.isFinite(value)) {
            return 0;
        }

        return value;
    }

    function calculateLine(
        quantity,
        unitPrice,
        discountAmount
    ) {
        const lineAmount = money(
            quantity * unitPrice
        );

        const safeDiscount = Math.max(
            discountAmount,
            0
        );

        const discountedAmount = Math.max(
            money(lineAmount - safeDiscount),
            0
        );

        if (
            pricesIncludeVat &&
            effectiveVatRate > 0
        ) {
            const divisor =
                1 + effectiveVatRate / 100;

            const netAmount = money(
                discountedAmount / divisor
            );

            const taxAmount = money(
                discountedAmount - netAmount
            );

            const netDiscount = money(
                safeDiscount / divisor
            );

            const netSubtotal = money(
                netAmount + netDiscount
            );

            return {
                subtotal: netSubtotal,
                discount: netDiscount,
                net: netAmount,
                tax: taxAmount,
                total: discountedAmount
            };
        }

        const netSubtotal = lineAmount;
        const netDiscount = money(
            safeDiscount
        );

        const netAmount = money(
            Math.max(
                netSubtotal - netDiscount,
                0
            )
        );

        const taxAmount = money(
            netAmount *
            (effectiveVatRate / 100)
        );

        const totalAmount = money(
            netAmount + taxAmount
        );

        return {
            subtotal: netSubtotal,
            discount: netDiscount,
            net: netAmount,
            tax: taxAmount,
            total: totalAmount
        };
    }

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

        const result = calculateLine(
            numberValue(quantityInput),
            numberValue(priceInput),
            numberValue(discountInput)
        );

        row.dataset.netSubtotal =
            String(result.subtotal);

        row.dataset.netDiscount =
            String(result.discount);

        row.dataset.taxAmount =
            String(result.tax);

        row.dataset.totalAmount =
            String(result.total);

        totalInput.value =
            result.total.toFixed(2);
    }

    function calculateTotals() {
        const rows = tableBody.querySelectorAll(
            '[data-item-row]'
        );

        let netSubtotal = 0;
        let netDiscount = 0;
        let taxTotal = 0;
        let grandTotal = 0;

        rows.forEach(function(row) {
            calculateRow(row);

            netSubtotal += Number(
                row.dataset.netSubtotal || 0
            );

            netDiscount += Number(
                row.dataset.netDiscount || 0
            );

            taxTotal += Number(
                row.dataset.taxAmount || 0
            );

            grandTotal += Number(
                row.dataset.totalAmount || 0
            );
        });

        document.querySelector(
            '#net-subtotal'
        ).textContent = money(
            netSubtotal
        ).toFixed(2);

        document.querySelector(
            '#total-discount'
        ).textContent = money(
            netDiscount
        ).toFixed(2);

        document.querySelector(
            '#tax-total'
        ).textContent = money(
            taxTotal
        ).toFixed(2);

        document.querySelector(
            '#grand-total'
        ).textContent = money(
            grandTotal
        ).toFixed(2);
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
            function() {
                const selectedOption =
                    productSelect.options[
                        productSelect.selectedIndex
                    ];

                const price =
                    selectedOption.getAttribute(
                        'data-price'
                    );

                if (price !== null) {
                    const parsedPrice =
                        parseFloat(price);

                    priceInput.value =
                        Number.isFinite(parsedPrice) ?
                        parsedPrice.toFixed(2) :
                        '0.00';
                } else {
                    priceInput.value = '0.00';
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
            function() {
                const rows =
                    tableBody.querySelectorAll(
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
        const firstRow =
            tableBody.querySelector(
                '[data-item-row]'
            );

        const newRow =
            firstRow.cloneNode(true);

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

        newRow.removeAttribute(
            'data-net-subtotal'
        );

        newRow.removeAttribute(
            'data-net-discount'
        );

        newRow.removeAttribute(
            'data-tax-amount'
        );

        newRow.removeAttribute(
            'data-total-amount'
        );

        tableBody.appendChild(newRow);

        bindRowEvents(newRow);
        calculateTotals();
    }

    addRowButton.addEventListener(
        'click',
        createNewRow
    );

    const initialRows =
        tableBody.querySelectorAll(
            '[data-item-row]'
        );

    initialRows.forEach(function(row) {
        bindRowEvents(row);
    });

    calculateTotals();
</script>

<script>
    document.addEventListener(
        'productLookup:selected',
        function(event) {
            if (
                event.detail.context !== 'sale'
            ) {
                return;
            }

            const product = event.detail.product;

            const warehouseSelect =
                document.getElementById(
                    'warehouse_id'
                );

            if (
                warehouseSelect === null ||
                warehouseSelect.value === ''
            ) {
                alert(
                    'Please select a warehouse first.'
                );

                return;
            }

            if (
                Number(product.stock_quantity) <= 0
            ) {
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
            const productSelect =
                row.querySelector(
                    'select[name="product_id[]"]'
                );

            const quantityInput =
                row.querySelector(
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

                if (
                    !Number.isFinite(
                        currentQuantity
                    )
                ) {
                    currentQuantity = 0;
                }

                const newQuantity =
                    currentQuantity + 1;

                if (
                    newQuantity >
                    Number(
                        product.stock_quantity
                    )
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
                        'input', {
                            bubbles: true
                        }
                    )
                );

                return;
            }
        }

        let emptyProductSelect = null;

        for (const row of rows) {
            const productSelect =
                row.querySelector(
                    'select[name="product_id[]"]'
                );

            if (
                productSelect !== null &&
                productSelect.value === ''
            ) {
                emptyProductSelect =
                    productSelect;

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

            const lastRow =
                rows[rows.length - 1];

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
                'change', {
                    bubbles: true
                }
            )
        );

        const row =
            emptyProductSelect.closest(
                '[data-item-row]'
            );

        if (row === null) {
            return;
        }

        const quantityInput =
            row.querySelector(
                'input[name="quantity[]"]'
            );

        if (quantityInput !== null) {
            quantityInput.value = '1';

            quantityInput.dispatchEvent(
                new Event(
                    'input', {
                        bubbles: true
                    }
                )
            );
        }
    }
</script>
