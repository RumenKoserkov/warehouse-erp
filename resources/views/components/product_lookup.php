<?php

declare(strict_types=1);

if (!isset($lookupContext)) {
    $lookupContext = 'document';
}

$lookupInputId = $lookupContext . '_product_lookup';
$lookupResultsId = $lookupContext . '_product_lookup_results';
$lookupMessageId = $lookupContext . '_product_lookup_message';
?>

<div class="card border-primary shadow-sm mb-4">
    <div class="card-body">
        <label
            for="<?= htmlspecialchars(
                $lookupInputId,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="form-label fw-semibold"
        >
            Barcode / Fast Product Lookup
        </label>

        <div class="input-group">
            <input
                type="text"
                id="<?= htmlspecialchars(
                    $lookupInputId,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="form-control"
                placeholder="Scan barcode or enter product code/name..."
                autocomplete="off"
            >

            <button
                type="button"
                id="<?= htmlspecialchars(
                    $lookupInputId . '_button',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="btn btn-primary"
            >
                Find Product
            </button>
        </div>

        <div
            id="<?= htmlspecialchars(
                $lookupMessageId,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="form-text"
        >
            Scan a barcode and press Enter.
        </div>

        <div
            id="<?= htmlspecialchars(
                $lookupResultsId,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="list-group mt-3"
        ></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const context = <?= json_encode(
        $lookupContext,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>;

    const input = document.getElementById(
        <?= json_encode($lookupInputId) ?>
    );

    const button = document.getElementById(
        <?= json_encode($lookupInputId . '_button') ?>
    );

    const resultsContainer = document.getElementById(
        <?= json_encode($lookupResultsId) ?>
    );

    const messageContainer = document.getElementById(
        <?= json_encode($lookupMessageId) ?>
    );

    if (
        input === null ||
        button === null ||
        resultsContainer === null ||
        messageContainer === null
    ) {
        return;
    }

    function clearResults() {
        resultsContainer.replaceChildren();
    }

    function setMessage(message, isError) {
        messageContainer.textContent = message;

        messageContainer.classList.remove(
            'text-danger',
            'text-success',
            'text-muted'
        );

        if (isError) {
            messageContainer.classList.add('text-danger');
        } else {
            messageContainer.classList.add('text-muted');
        }
    }

    function selectProduct(product) {
        const event = new CustomEvent(
            'productLookup:selected',
            {
                detail: {
                    context: context,
                    product: product
                }
            }
        );

        document.dispatchEvent(event);

        input.value = '';
        clearResults();

        setMessage(
            product.name + ' selected.',
            false
        );

        input.focus();
    }

    function createResultButton(product) {
        const resultButton = document.createElement('button');

        resultButton.type = 'button';

        resultButton.className =
            'list-group-item list-group-item-action';

        const title = document.createElement('div');

        title.className = 'fw-semibold';

        title.textContent =
            product.internal_code + ' — ' + product.name;

        const details = document.createElement('small');

        details.className = 'text-muted';

        let detailsText = '';

        if (product.barcode !== '') {
            detailsText += 'Barcode: ' + product.barcode;
        }

        detailsText +=
            ' | Sale price: ' +
            Number(product.selling_price).toFixed(2);

        if (context === 'sale') {
            detailsText +=
                ' | Stock: ' +
                Number(product.stock_quantity);
        }

        details.textContent = detailsText;

        resultButton.appendChild(title);
        resultButton.appendChild(details);

        resultButton.addEventListener(
            'click',
            function () {
                selectProduct(product);
            }
        );

        return resultButton;
    }

    function showResults(items) {
        clearResults();

        if (items.length === 0) {
            setMessage(
                'No product was found.',
                true
            );

            return;
        }

        const exactItem = items.find(function (item) {
            return item.exact_match === true;
        });

        if (exactItem !== undefined) {
            selectProduct(exactItem);

            return;
        }

        if (items.length === 1) {
            selectProduct(items[0]);

            return;
        }

        setMessage(
            'Multiple products found. Select one:',
            false
        );

        items.forEach(function (product) {
            resultsContainer.appendChild(
                createResultButton(product)
            );
        });
    }

    async function lookupProduct() {
        const query = input.value.trim();

        if (query === '') {
            setMessage(
                'Enter a barcode, code or product name.',
                true
            );

            input.focus();

            return;
        }

        const parameters = new URLSearchParams();

        parameters.set('q', query);

        const warehouseSelect =
            document.getElementById('warehouse_id');

        if (
            warehouseSelect !== null &&
            warehouseSelect.value !== ''
        ) {
            parameters.set(
                'warehouse_id',
                warehouseSelect.value
            );
        }

        button.disabled = true;

        setMessage('Searching...', false);

        try {
            const response = await fetch(
                '/products/lookup?' +
                parameters.toString(),
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(
                    'Lookup request failed.'
                );
            }

            const data = await response.json();

            if (!Array.isArray(data.items)) {
                throw new Error(
                    'Invalid lookup response.'
                );
            }

            showResults(data.items);
        } catch (error) {
            clearResults();

            setMessage(
                'Unable to search for products.',
                true
            );
        } finally {
            button.disabled = false;
        }
    }

    button.addEventListener(
        'click',
        lookupProduct
    );

    input.addEventListener(
        'keydown',
        function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();

            lookupProduct();
        }
    );

    input.focus();
});
</script>