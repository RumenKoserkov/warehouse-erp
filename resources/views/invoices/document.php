<?php

declare(strict_types=1);

$isDraft =
    (string) $invoice['status'] === 'draft';

$documentNumber =
    'DRAFT-' . (int) $invoice['id'];

if (
    isset($invoice['invoice_number']) &&
    trim((string) $invoice['invoice_number']) !== ''
) {
    $documentNumber = trim(
        (string) $invoice['invoice_number']
    );
}

$formatDate = static function (
    mixed $value
): string {
    if ($value === null) {
        return '-';
    }

    $dateValue = trim((string) $value);

    if ($dateValue === '') {
        return '-';
    }

    $timestamp = strtotime($dateValue);

    if ($timestamp === false) {
        return $dateValue;
    }

    return date('d.m.Y', $timestamp);
};

$escape = static function (
    mixed $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$money = static function (
    mixed $value
): string {
    return number_format(
        (float) $value,
        2,
        '.',
        ' '
    );
};

$quantity = static function (
    mixed $value
): string {
    $formatted = number_format(
        (float) $value,
        3,
        '.',
        ' '
    );

    return rtrim(
        rtrim($formatted, '0'),
        '.'
    );
};

$taxBase =
    (float) $invoice['subtotal'] -
    (float) $invoice['discount_amount'];

if ($taxBase < 0) {
    $taxBase = 0;
}

$priceMode = 'Prices exclude VAT';

if ((int) $invoice['vat_registered'] !== 1) {
    $priceMode = 'VAT is not charged';
} elseif (
    (int) $invoice['prices_include_vat'] === 1
) {
    $priceMode = 'Prices include VAT';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        Invoice <?= $escape($documentNumber) ?>
    </title>

    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm 16mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #202124;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            line-height: 1.45;
            background: #ffffff;
        }

        .print-toolbar {
            margin: 0 auto 20px auto;
            padding: 12px;
            max-width: 210mm;
            border: 1px solid #d5d9dd;
            background: #f7f8f9;
            text-align: right;
        }

        .print-toolbar button {
            padding: 8px 14px;
            border: 0;
            border-radius: 4px;
            color: #ffffff;
            background: #2457a7;
            cursor: pointer;
        }

        .document {
            width: 100%;
        }

        .draft-banner {
            margin-bottom: 14px;
            padding: 9px;
            border: 2px solid #a12b2b;
            color: #8d1f1f;
            font-size: 15px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
        }

        .header-table,
        .party-table,
        .meta-table,
        .items-table,
        .summary-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            margin-bottom: 16px;
        }

        .header-title {
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .header-number {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 17px;
            font-weight: bold;
            text-align: right;
        }

        .status-label {
            margin-top: 5px;
            font-size: 10px;
            font-weight: bold;
            text-align: right;
            text-transform: uppercase;
        }

        .party-table {
            margin-bottom: 14px;
        }

        .party-table td {
            width: 50%;
            padding: 10px;
            vertical-align: top;
            border: 1px solid #c9cdd2;
        }

        .party-table td:first-child {
            border-right: 0;
        }

        .section-label {
            margin-bottom: 7px;
            color: #555b61;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .party-name {
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: bold;
        }

        .detail-line {
            margin-bottom: 2px;
        }

        .meta-table {
            margin-bottom: 15px;
        }

        .meta-table td {
            width: 25%;
            padding: 7px;
            border: 1px solid #c9cdd2;
            vertical-align: top;
        }

        .meta-name {
            display: block;
            margin-bottom: 2px;
            color: #555b61;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .items-table {
            margin-bottom: 14px;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table th {
            padding: 6px 5px;
            border: 1px solid #92989e;
            color: #ffffff;
            background: #303842;
            font-size: 8px;
            text-align: center;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 6px 5px;
            border: 1px solid #c9cdd2;
            vertical-align: top;
        }

        .items-table .number {
            text-align: right;
            white-space: nowrap;
        }

        .items-table .center {
            text-align: center;
        }

        .item-code {
            margin-top: 2px;
            color: #6b7075;
            font-size: 8px;
        }

        .summary-wrapper {
            width: 100%;
            margin-bottom: 16px;
        }

        .summary-spacer {
            width: 55%;
        }

        .summary-cell {
            width: 45%;
            vertical-align: top;
        }

        .summary-table td {
            padding: 5px 7px;
            border-bottom: 1px solid #d7dade;
        }

        .summary-table .summary-number {
            text-align: right;
            white-space: nowrap;
        }

        .summary-table .grand-total td {
            padding-top: 9px;
            border-top: 2px solid #303842;
            border-bottom: 0;
            font-size: 13px;
            font-weight: bold;
        }

        .note-box {
            margin-bottom: 14px;
            padding: 9px;
            border: 1px solid #c9cdd2;
        }

        .footer-table {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .footer-table td {
            width: 50%;
            padding: 9px;
            border-top: 1px solid #92989e;
            vertical-align: top;
        }

        .signature-space {
            height: 28px;
        }

        .small-muted {
            color: #666c72;
            font-size: 8px;
        }

        .text-right {
            text-align: right;
        }

        .source-sale {
            margin-top: 6px;
            color: #555b61;
            font-size: 8px;
        }

        @media print {
            .print-toolbar {
                display: none;
            }

            body {
                background: #ffffff;
            }
        }
    </style>
</head>

<body>
    <?php if (!$forPdf): ?>
        <div class="print-toolbar">
            <button
                type="button"
                onclick="window.print()">
                Print Invoice
            </button>
        </div>
    <?php endif; ?>

    <div class="document">
        <?php if ($isDraft): ?>
            <div class="draft-banner">
                DRAFT - NOT AN OFFICIAL INVOICE
            </div>
        <?php endif; ?>

        <table class="header-table">
            <tr>
                <td>
                    <div class="header-title">
                        INVOICE
                    </div>

                    <div class="small-muted">
                        Original document
                    </div>
                </td>

                <td>
                    <div class="header-number">
                        No. <?= $escape($documentNumber) ?>
                    </div>

                    <div class="status-label">
                        <?= $escape(
                            strtoupper(
                                (string) $invoice['status']
                            )
                        ) ?>
                    </div>
                </td>
            </tr>
        </table>

        <table class="party-table">
            <tr>
                <td>
                    <div class="section-label">
                        Supplier
                    </div>

                    <div class="party-name">
                        <?= $escape(
                            $invoice['supplier_legal_name']
                        ) ?>
                    </div>

                    <div class="detail-line">
                        EIK:
                        <?= $escape(
                            $invoice['supplier_eik']
                        ) ?>
                    </div>

                    <?php if (
                        trim(
                            (string) $invoice['supplier_vat_number']
                        ) !== ''
                    ): ?>
                        <div class="detail-line">
                            VAT No.:
                            <?= $escape(
                                $invoice['supplier_vat_number']
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <div class="detail-line">
                        Address:
                        <?= $escape(
                            $invoice['supplier_address']
                        ) ?>,
                        <?= $escape(
                            $invoice['supplier_postal_code']
                        ) ?>
                        <?= $escape(
                            $invoice['supplier_city']
                        ) ?>,
                        <?= $escape(
                            $invoice['supplier_country']
                        ) ?>
                    </div>

                    <?php if (
                        trim(
                            (string) $invoice['supplier_phone']
                        ) !== ''
                    ): ?>
                        <div class="detail-line">
                            Phone:
                            <?= $escape(
                                $invoice['supplier_phone']
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        trim(
                            (string) $invoice['supplier_email']
                        ) !== ''
                    ): ?>
                        <div class="detail-line">
                            Email:
                            <?= $escape(
                                $invoice['supplier_email']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </td>

                <td>
                    <div class="section-label">
                        Client
                    </div>

                    <div class="party-name">
                        <?= $escape(
                            $invoice['client_legal_name']
                        ) ?>
                    </div>

                    <?php if (
                        trim(
                            (string) $invoice['client_eik']
                        ) !== ''
                    ): ?>
                        <div class="detail-line">
                            EIK:
                            <?= $escape(
                                $invoice['client_eik']
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        trim(
                            (string) $invoice['client_vat_number']
                        ) !== ''
                    ): ?>
                        <div class="detail-line">
                            VAT No.:
                            <?= $escape(
                                $invoice['client_vat_number']
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <div class="detail-line">
                        Address:
                        <?= $escape(
                            $invoice['client_address']
                        ) ?>,
                        <?= $escape(
                            $invoice['client_postal_code']
                        ) ?>
                        <?= $escape(
                            $invoice['client_city']
                        ) ?>,
                        <?= $escape(
                            $invoice['client_country']
                        ) ?>
                    </div>

                    <?php if (
                        trim(
                            (string) $invoice['client_email']
                        ) !== ''
                    ): ?>
                        <div class="detail-line">
                            Email:
                            <?= $escape(
                                $invoice['client_email']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td>
                    <span class="meta-name">
                        Invoice date
                    </span>

                    <?= $escape(
                        $formatDate(
                            $invoice['invoice_date']
                        )
                    ) ?>
                </td>

                <td>
                    <span class="meta-name">
                        Supply date
                    </span>

                    <?= $escape(
                        $formatDate(
                            $invoice['supply_date']
                        )
                    ) ?>
                </td>

                <td>
                    <span class="meta-name">
                        Due date
                    </span>

                    <?= $escape(
                        $formatDate(
                            $invoice['due_date']
                        )
                    ) ?>
                </td>

                <td>
                    <span class="meta-name">
                        Currency
                    </span>

                    <?= $escape(
                        $invoice['currency']
                    ) ?>
                </td>
            </tr>
        </table>

        <div
            class="small-muted"
            style="margin-bottom: 6px;">
            <?= $escape($priceMode) ?>

            <?php if (
                (int) $invoice['vat_registered'] === 1
            ): ?>
                - Default VAT:
                <?= $money(
                    $invoice['default_vat_rate']
                ) ?>%
            <?php endif; ?>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 29%;">Description</th>
                    <th style="width: 9%;">Quantity</th>
                    <th style="width: 7%;">Unit</th>
                    <th style="width: 11%;">Unit Price</th>
                    <th style="width: 10%;">Discount</th>
                    <th style="width: 10%;">Tax Base</th>
                    <th style="width: 7%;">VAT %</th>
                    <th style="width: 10%;">VAT</th>
                    <th style="width: 12%;">Total</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach (
                    $items as $index => $item
                ): ?>
                    <tr>
                        <td class="center">
                            <?= (int) $index + 1 ?>
                        </td>

                        <td>
                            <?= $escape(
                                $item['description']
                            ) ?>

                            <?php if (
                                trim(
                                    (string) $item['product_internal_code']
                                ) !== ''
                            ): ?>
                                <div class="item-code">
                                    Code:
                                    <?= $escape(
                                        $item['product_internal_code']
                                    ) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="number">
                            <?= $quantity(
                                $item['quantity']
                            ) ?>
                        </td>

                        <td class="center">
                            <?= $escape(
                                $item['unit']
                            ) ?>
                        </td>

                        <td class="number">
                            <?= $money(
                                $item['unit_price']
                            ) ?>
                        </td>

                        <td class="number">
                            <?= $money(
                                $item['discount_amount']
                            ) ?>
                        </td>

                        <td class="number">
                            <?= $money(
                                $item['net_amount']
                            ) ?>
                        </td>

                        <td class="number">
                            <?= $money(
                                $item['vat_rate']
                            ) ?>%
                        </td>

                        <td class="number">
                            <?= $money(
                                $item['tax_amount']
                            ) ?>
                        </td>

                        <td class="number">
                            <strong>
                                <?= $money(
                                    $item['total_amount']
                                ) ?>
                            </strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="summary-wrapper">
            <tr>
                <td class="summary-spacer"></td>

                <td class="summary-cell">
                    <table class="summary-table">
                        <tr>
                            <td>
                                Subtotal before discount
                            </td>

                            <td class="summary-number">
                                <?= $money(
                                    $invoice['subtotal']
                                ) ?>
                                <?= $escape(
                                    $invoice['currency']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Discount
                            </td>

                            <td class="summary-number">
                                <?= $money(
                                    $invoice['discount_amount']
                                ) ?>
                                <?= $escape(
                                    $invoice['currency']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                Tax base
                            </td>

                            <td class="summary-number">
                                <?= $money($taxBase) ?>
                                <?= $escape(
                                    $invoice['currency']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                VAT
                            </td>

                            <td class="summary-number">
                                <?= $money(
                                    $invoice['tax_amount']
                                ) ?>
                                <?= $escape(
                                    $invoice['currency']
                                ) ?>
                            </td>
                        </tr>

                        <tr class="grand-total">
                            <td>
                                Total
                            </td>

                            <td class="summary-number">
                                <?= $money(
                                    $invoice['total_amount']
                                ) ?>
                                <?= $escape(
                                    $invoice['currency']
                                ) ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <?php if (
            trim((string) $invoice['note']) !== ''
        ): ?>
            <div class="note-box">
                <div class="section-label">
                    Note
                </div>

                <?= nl2br(
                    $escape($invoice['note'])
                ) ?>
            </div>
        <?php endif; ?>

        <?php if (
            trim(
                (string) $invoice['supplier_bank_name']
            ) !== '' ||
            trim(
                (string) $invoice['supplier_iban']
            ) !== ''
        ): ?>
            <div class="note-box">
                <div class="section-label">
                    Bank information
                </div>

                <?php if (
                    trim(
                        (string) $invoice['supplier_bank_name']
                    ) !== ''
                ): ?>
                    <div>
                        Bank:
                        <?= $escape(
                            $invoice['supplier_bank_name']
                        ) ?>
                    </div>
                <?php endif; ?>

                <?php if (
                    trim(
                        (string) $invoice['supplier_iban']
                    ) !== ''
                ): ?>
                    <div>
                        IBAN:
                        <?= $escape(
                            $invoice['supplier_iban']
                        ) ?>
                    </div>
                <?php endif; ?>

                <?php if (
                    trim(
                        (string) $invoice['supplier_bic']
                    ) !== ''
                ): ?>
                    <div>
                        BIC:
                        <?= $escape(
                            $invoice['supplier_bic']
                        ) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (
            isset($invoice['source_sale_number']) &&
            trim(
                (string) $invoice['source_sale_number']
            ) !== ''
        ): ?>
            <div class="source-sale">
                Generated from sale:
                <?= $escape(
                    $invoice['source_sale_number']
                ) ?>
            </div>
        <?php endif; ?>

        <table class="footer-table">
            <tr>
                <td>
                    <div class="section-label">
                        Prepared by
                    </div>

                    <div class="signature-space">
                        <?php if (
                            trim(
                                (string) $invoice['supplier_manager_name']
                            ) !== ''
                        ): ?>
                            <?= $escape(
                                $invoice['supplier_manager_name']
                            ) ?>
                        <?php endif; ?>
                    </div>

                    <div class="small-muted">
                        Name and signature
                    </div>
                </td>

                <td class="text-right">
                    <div class="section-label">
                        Received by
                    </div>

                    <div class="signature-space"></div>

                    <div class="small-muted">
                        Name and signature
                    </div>
                </td>
            </tr>
        </table>

        <?php if ($isDraft): ?>
            <div
                class="draft-banner"
                style="margin-top: 18px;">
                DRAFT - NOT AN OFFICIAL INVOICE
            </div>
        <?php endif; ?>
    </div>
</body>

</html>