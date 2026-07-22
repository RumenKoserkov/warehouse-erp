<?php

declare(strict_types=1);

namespace App\Services;

use Generator;

class CsvExportService
{
    public function products(
        iterable $records,
        bool $canViewCosts
    ): array {
        $headers = [
            'ID',
            'Name',
            'Internal Code',
            'Barcode',
            'Category',
            'Unit',
            'Sale Price',
            'Total Stock',
            'Active',
        ];

        if ($canViewCosts) {
            $headers[] =
                'Purchase Price';

            $headers[] =
                'Last Purchase Cost';

            $headers[] =
                'Inventory Value';
        }

        return $this->dataset(
            $headers,
            $records,
            static function (
                array $record
            ) use (
                $canViewCosts
            ): array {
                $salePrice =
                    $record['sale_price']
                    ??
                    $record['selling_price']
                    ??
                    0;

                $row = [
                    (int) $record['id'],

                    (string) (
                        $record['name'] ?? ''
                    ),

                    (string) (
                        $record['internal_code'] ?? ''
                    ),

                    (string) (
                        $record['barcode'] ?? ''
                    ),

                    (string) (
                        $record['category_name'] ?? ''
                    ),

                    (string) (
                        $record['unit'] ?? ''
                    ),

                    round(
                        (float) $salePrice,
                        2
                    ),

                    round(
                        (float) (
                            $record['total_stock_quantity'] ?? 0
                        ),
                        3
                    ),

                    (int) (
                        $record['is_active'] ??
                        0
                    ) === 1
                        ? 'Yes'
                        : 'No',
                ];

                if ($canViewCosts) {
                    $row[] = round(
                        (float) (
                            $record['purchase_price'] ?? 0
                        ),
                        4
                    );

                    $row[] = round(
                        (float) (
                            $record['last_purchase_cost'] ?? 0
                        ),
                        4
                    );

                    $row[] = round(
                        (float) (
                            $record['total_inventory_value'] ?? 0
                        ),
                        2
                    );
                }

                return $row;
            }
        );
    }

    public function stock(
        iterable $records,
        bool $canViewCosts
    ): array {
        $headers = [
            'Product',
            'Internal Code',
            'Barcode',
            'Category',
            'Warehouse',
            'Warehouse Code',
            'Unit',
            'Quantity',
        ];

        if ($canViewCosts) {
            $headers[] =
                'Average Unit Cost';

            $headers[] =
                'Inventory Value';
        }

        return $this->dataset(
            $headers,
            $records,
            static function (
                array $record
            ) use (
                $canViewCosts
            ): array {
                $row = [
                    (string) (
                        $record['product_name'] ?? ''
                    ),

                    (string) (
                        $record['internal_code'] ?? ''
                    ),

                    (string) (
                        $record['barcode'] ?? ''
                    ),

                    (string) (
                        $record['category_name'] ?? ''
                    ),

                    (string) (
                        $record['warehouse_name'] ?? ''
                    ),

                    (string) (
                        $record['warehouse_code'] ?? ''
                    ),

                    (string) (
                        $record['product_unit'] ?? ''
                    ),

                    round(
                        (float) (
                            $record['quantity'] ??
                            0
                        ),
                        3
                    ),
                ];

                if ($canViewCosts) {
                    $row[] = round(
                        (float) (
                            $record['average_unit_cost'] ?? 0
                        ),
                        4
                    );

                    $row[] = round(
                        (float) (
                            $record['inventory_value'] ?? 0
                        ),
                        2
                    );
                }

                return $row;
            }
        );
    }

    public function transactions(
        iterable $records,
        bool $canViewCosts
    ): array {
        $headers = [
            'ID',
            'Date',
            'Type',
            'Product',
            'Internal Code',
            'From Warehouse',
            'To Warehouse',
            'Quantity',
            'Unit',
            'Reference Type',
            'Reference ID',
            'User',
            'Note',
        ];

        if ($canViewCosts) {
            $headers[] =
                'Cost Method';

            $headers[] =
                'Unit Cost';

            $headers[] =
                'Total Cost';

            $headers[] =
                'From Quantity Before';

            $headers[] =
                'From Quantity After';

            $headers[] =
                'To Quantity Before';

            $headers[] =
                'To Quantity After';

            $headers[] =
                'From Average Cost Before';

            $headers[] =
                'From Average Cost After';

            $headers[] =
                'To Average Cost Before';

            $headers[] =
                'To Average Cost After';
        }

        return $this->dataset(
            $headers,
            $records,
            static function (
                array $record
            ) use (
                $canViewCosts
            ): array {
                $row = [
                    (int) $record['id'],

                    (string) (
                        $record['created_at'] ?? ''
                    ),

                    (string) (
                        $record['type'] ?? ''
                    ),

                    (string) (
                        $record['product_name'] ?? ''
                    ),

                    (string) (
                        $record['internal_code'] ?? ''
                    ),

                    (string) (
                        $record['from_warehouse_name'] ?? ''
                    ),

                    (string) (
                        $record['to_warehouse_name'] ?? ''
                    ),

                    round(
                        (float) (
                            $record['quantity'] ??
                            0
                        ),
                        3
                    ),

                    (string) (
                        $record['product_unit'] ?? ''
                    ),

                    (string) (
                        $record['reference_type'] ?? ''
                    ),

                    $record['reference_id']
                        !== null
                        ? (int) $record['reference_id']
                        : '',

                    (string) (
                        $record['user_name'] ??
                        ''
                    ),

                    (string) (
                        $record['note'] ?? ''
                    ),
                ];

                if ($canViewCosts) {
                    $row[] =
                        (string) (
                            $record['cost_method'] ?? ''
                        );

                    $row[] =
                        $record['unit_cost']
                        !== null
                        ? round(
                            (float) $record['unit_cost'],
                            4
                        )
                        : '';

                    $row[] =
                        $record['total_cost']
                        !== null
                        ? round(
                            (float) $record['total_cost'],
                            4
                        )
                        : '';

                    $costFields = [
                        'from_quantity_before' =>
                        3,

                        'from_quantity_after' =>
                        3,

                        'to_quantity_before' =>
                        3,

                        'to_quantity_after' =>
                        3,

                        'from_average_cost_before' =>
                        4,

                        'from_average_cost_after' =>
                        4,

                        'to_average_cost_before' =>
                        4,

                        'to_average_cost_after' =>
                        4,
                    ];

                    foreach (
                        $costFields as
                        $field => $scale
                    ) {
                        $row[] =
                            $record[$field]
                            !== null
                            ? round(
                                (float) $record[$field],
                                $scale
                            )
                            : '';
                    }
                }

                return $row;
            }
        );
    }

    public function sales(
        iterable $records,
        bool $canViewCosts
    ): array {
        $headers = [
            'ID',
            'Sale Number',
            'Sale Date',
            'Client',
            'Warehouse',
            'Status',
            'Items',
            'Quantity',
            'Subtotal',
            'Discount',
            'Promotion Discount',
            'Net Amount',
            'Tax Amount',
            'Total Amount',
            'Created By',
        ];

        if ($canViewCosts) {
            $headers[] =
                'Known COGS';

            $headers[] =
                'Known Gross Profit';

            $headers[] =
                'Known Gross Margin';

            $headers[] =
                'Uncosted Items';
        }

        return $this->dataset(
            $headers,
            $records,
            static function (
                array $record
            ) use (
                $canViewCosts
            ): array {
                $subtotalAmount = round(
                    (float) (
                        $record['subtotal'] ??
                        0
                    ),
                    2
                );

                $discountAmount = round(
                    (float) (
                        $record['discount_amount'] ?? 0
                    ),
                    2
                );

                $promotionDiscountAmount = round(
                    (float) (
                        $record['promotion_discount_amount'] ?? 0
                    ),
                    2
                );

                $netAmount = round(
                    max(
                        0,
                        $subtotalAmount -
                            $discountAmount -
                            $promotionDiscountAmount
                    ),
                    2
                );

                $row = [
                    (int) $record['id'],

                    (string) (
                        $record['sale_number'] ?? ''
                    ),

                    (string) (
                        $record['sale_date'] ?? ''
                    ),

                    (string) (
                        $record['client_name'] ?? ''
                    ),

                    (string) (
                        $record['warehouse_name'] ?? ''
                    ),

                    (string) (
                        $record['status'] ?? ''
                    ),

                    (int) (
                        $record['item_count'] ??
                        0
                    ),

                    round(
                        (float) (
                            $record['total_quantity'] ?? 0
                        ),
                        3
                    ),

                    $subtotalAmount,

                    $discountAmount,

                    $promotionDiscountAmount,

                    $netAmount,

                    round(
                        (float) (
                            $record['tax_amount'] ?? 0
                        ),
                        2
                    ),

                    round(
                        (float) (
                            $record['total_amount'] ?? 0
                        ),
                        2
                    ),

                    (string) (
                        $record['created_by_user_name'] ?? ''
                    ),
                ];

                if ($canViewCosts) {
                    $knownCogs =
                        $record['known_cogs'];

                    $knownProfit =
                        $record['known_gross_profit'];

                    $row[] =
                        $knownCogs !== null
                        ? round(
                            (float) $knownCogs,
                            2
                        )
                        : '';

                    $row[] =
                        $knownProfit !== null
                        ? round(
                            (float) $knownProfit,
                            2
                        )
                        : '';

                    $row[] =
                        $knownProfit !== null &&
                        $netAmount > 0.005
                        ? round(
                            (
                                (float) $knownProfit /
                                $netAmount
                            ) * 100,
                            2
                        )
                        : '';

                    $row[] = (int) (
                        $record['uncosted_item_count'] ?? 0
                    );
                }

                return $row;
            }
        );
    }

    public function purchases(
        iterable $records
    ): array {
        $headers = [
            'ID',
            'Purchase Number',
            'Purchase Date',
            'Supplier',
            'Warehouse',
            'Status',
            'Items',
            'Quantity',
            'Subtotal',
            'Discount',
            'Net Amount',
            'Tax Amount',
            'Total Amount',
            'Inventory Cost',
            'Created By',
        ];

        return $this->dataset(
            $headers,
            $records,
            static function (
                array $record
            ): array {
                $subtotalAmount = round(
                    (float) (
                        $record['subtotal'] ??
                        0
                    ),
                    2
                );

                $discountAmount = round(
                    (float) (
                        $record['discount_amount'] ?? 0
                    ),
                    2
                );

                $netAmount = round(
                    max(
                        0,
                        $subtotalAmount -
                            $discountAmount
                    ),
                    2
                );

                return [
                    (int) $record['id'],

                    (string) (
                        $record['purchase_number'] ?? ''
                    ),

                    (string) (
                        $record['purchase_date'] ?? ''
                    ),

                    (string) (
                        $record['supplier_name'] ?? ''
                    ),

                    (string) (
                        $record['warehouse_name'] ?? ''
                    ),

                    (string) (
                        $record['status'] ?? ''
                    ),

                    (int) (
                        $record['item_count'] ??
                        0
                    ),

                    round(
                        (float) (
                            $record['total_quantity'] ?? 0
                        ),
                        3
                    ),

                    $subtotalAmount,

                    $discountAmount,

                    $netAmount,

                    round(
                        (float) (
                            $record['tax_amount'] ?? 0
                        ),
                        2
                    ),

                    round(
                        (float) (
                            $record['total_amount'] ?? 0
                        ),
                        2
                    ),

                    $record['inventory_total_cost'] !== null
                        ? round(
                            (float) $record['inventory_total_cost'],
                            4
                        )
                        : '',

                    (string) (
                        $record['created_by_user_name'] ?? ''
                    ),
                ];
            }
        );
    }

    public function invoices(
        iterable $records
    ): array {
        $headers = [
            'ID',
            'Invoice Number',
            'Issued At',
            'Due Date',
            'Client',
            'Sale Number',
            'Status',
            'Subtotal',
            'Discount',
            'Net Amount',
            'Tax Amount',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
        ];

        return $this->dataset(
            $headers,
            $records,
            static function (
                array $record
            ): array {
                $totalAmount = round(
                    (float) (
                        $record['total_amount'] ?? 0
                    ),
                    2
                );

                $paidAmount = round(
                    (float) (
                        $record['paid_amount'] ?? 0
                    ),
                    2
                );

                $outstandingAmount =
                    $record['outstanding_amount']
                    ??
                    $record['balance_due']
                    ??
                    max(
                        0,
                        $totalAmount -
                            $paidAmount
                    );

                return [
                    (int) $record['id'],

                    (string) (
                        $record['invoice_number'] ?? ''
                    ),

                    (string) (
                        $record['issued_at'] ??
                        $record['issue_date'] ??
                        ''
                    ),

                    (string) (
                        $record['due_date'] ?? ''
                    ),

                    (string) (
                        $record['client_name'] ?? ''
                    ),

                    (string) (
                        $record['sale_number'] ?? ''
                    ),

                    (string) (
                        $record['status'] ?? ''
                    ),

                    round(
                        (float) (
                            $record['subtotal_amount'] ?? 0
                        ),
                        2
                    ),

                    round(
                        (float) (
                            $record['discount_amount'] ?? 0
                        ),
                        2
                    ),

                    round(
                        (float) (
                            $record['net_amount'] ?? 0
                        ),
                        2
                    ),

                    round(
                        (float) (
                            $record['tax_amount'] ?? 0
                        ),
                        2
                    ),

                    $totalAmount,
                    $paidAmount,

                    round(
                        (float) $outstandingAmount,
                        2
                    ),
                ];
            }
        );
    }

    public function profitEvents(
        iterable $events
    ): array {
        $headers = [
            'Date',
            'Type',
            'Document',
            'Original Sale',
            'Product',
            'Internal Code',
            'Category',
            'Client',
            'Warehouse',
            'Quantity',
            'Revenue',
            'COGS',
            'Gross Profit',
            'Cost Status',
        ];

        return $this->dataset(
            $headers,
            $events,
            static function (
                array $event
            ): array {
                $isReturn =
                    (string) $event['source_type'] === 'sales_return';

                return [
                    (string) $event['event_date'],

                    $isReturn
                        ? 'Sales Return'
                        : 'Sale',

                    (string) (
                        $isReturn
                        ? $event['return_number']
                        : $event['sale_number']
                    ),

                    (string) (
                        $event['sale_number'] ?? ''
                    ),

                    (string) $event['product_name'],

                    (string) $event['product_internal_code'],

                    (string) $event['category_name'],

                    (string) $event['client_name'],

                    (string) $event['warehouse_name'],

                    round(
                        (float) $event['signed_quantity'],
                        3
                    ),

                    round(
                        (float) $event['revenue_amount'],
                        2
                    ),

                    $event['cogs_amount']
                        !== null
                        ? round(
                            (float) $event['cogs_amount'],
                            2
                        )
                        : '',

                    $event['gross_profit_amount'] !== null
                        ? round(
                            (float) $event['gross_profit_amount'],
                            2
                        )
                        : '',

                    (int) $event['is_costed'] === 1
                        ? 'Costed'
                        : 'Missing Cost',
                ];
            }
        );
    }

    public function marginEvents(
        iterable $events
    ): array {
        $headers = [
            'Date',
            'Type',
            'Document',
            'Original Sale',
            'Product',
            'Internal Code',
            'Category',
            'Client',
            'Warehouse',
            'Quantity',
            'Gross Amount',
            'Discount',
            'Net Revenue',
            'Inventory Unit Cost',
            'Total Cost',
            'Gross Profit',
            'Margin Percent',
            'Markup Percent',
            'Discount Rate Percent',
            'Margin Status',
        ];

        return $this->dataset(
            $headers,
            $events,
            static function (
                array $event
            ): array {
                $isReturn =
                    (string) $event['source_type'] === 'sales_return';

                return [
                    (string) $event['event_date'],

                    $isReturn
                        ? 'Sales Return'
                        : 'Sale',

                    (string) (
                        $isReturn
                        ? $event['return_number']
                        : $event['sale_number']
                    ),

                    (string) (
                        $event['sale_number'] ?? ''
                    ),

                    (string) $event['product_name'],

                    (string) $event['product_internal_code'],

                    (string) $event['category_name'],

                    (string) $event['client_name'],

                    (string) $event['warehouse_name'],

                    round(
                        (float) $event['signed_quantity'],
                        3
                    ),

                    round(
                        (float) (
                            $event['gross_amount'] ?? 0
                        ),
                        2
                    ),

                    round(
                        (float) (
                            $event['discount_amount'] ?? 0
                        ),
                        2
                    ),

                    round(
                        (float) $event['revenue_amount'],
                        2
                    ),

                    $event['inventory_unit_cost'] !== null
                        ? round(
                            (float) $event['inventory_unit_cost'],
                            4
                        )
                        : '',

                    $event['cogs_amount']
                        !== null
                        ? round(
                            (float) $event['cogs_amount'],
                            2
                        )
                        : '',

                    $event['gross_profit_amount'] !== null
                        ? round(
                            (float) $event['gross_profit_amount'],
                            2
                        )
                        : '',

                    $event['event_margin_percent'] !== null
                        ? round(
                            (float) $event['event_margin_percent'],
                            2
                        )
                        : '',

                    $event['event_markup_percent'] !== null
                        ? round(
                            (float) $event['event_markup_percent'],
                            2
                        )
                        : '',

                    $event['event_discount_rate_percent'] !== null
                        ? round(
                            (float) $event['event_discount_rate_percent'],
                            2
                        )
                        : '',

                    (string) (
                        $event['margin_status'] ?? ''
                    ),
                ];
            }
        );
    }

    private function dataset(
        array $headers,
        iterable $records,
        callable $mapper
    ): array {
        $rows = (
            static function () use (
                $records,
                $mapper
            ): Generator {
                foreach ($records as $record) {
                    if (!is_array($record)) {
                        continue;
                    }

                    yield $mapper($record);
                }
            }
        )();

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }
}
