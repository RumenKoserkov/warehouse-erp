<?php

declare(strict_types=1);

namespace App\Services;

class MarginReportService
{
    public function build(
        array $events,
        string $grouping,
        float $minimumMargin
    ): array {
        $summary =
            $this->emptyAggregate();

        $periods = [];
        $products = [];
        $categories = [];
        $clients = [];
        $warehouses = [];
        $sales = [];

        $bands = [
            'negative' =>
                $this->emptyAggregate(),

            'below_target' =>
                $this->emptyAggregate(),

            'meets_target' =>
                $this->emptyAggregate(),

            'uncosted' =>
                $this->emptyAggregate(),
        ];

        $saleIds = [];
        $returnIds = [];

        $decoratedEvents = [];

        foreach ($events as $event) {
            $event =
                $this->decorateEvent(
                    $event,
                    $minimumMargin
                );

            $decoratedEvents[] = $event;

            $this->addEvent(
                $summary,
                $event
            );

            $eventDate =
                (string) $event[
                    'event_date'
                ];

            $periodKey =
                $grouping === 'monthly'
                    ? substr(
                        $eventDate,
                        0,
                        7
                    )
                    : $eventDate;

            if (!isset($periods[$periodKey])) {
                $periods[$periodKey] =
                    $this->emptyAggregate();

                $periods[$periodKey][
                    'label'
                ] = $periodKey;
            }

            $this->addEvent(
                $periods[$periodKey],
                $event
            );

            $productId =
                (int) $event['product_id'];

            if (!isset($products[$productId])) {
                $products[$productId] =
                    $this->emptyAggregate();

                $products[$productId][
                    'product_id'
                ] = $productId;

                $products[$productId][
                    'product_name'
                ] = (string) $event[
                    'product_name'
                ];

                $products[$productId][
                    'product_internal_code'
                ] = (string) $event[
                    'product_internal_code'
                ];

                $products[$productId][
                    'product_unit'
                ] = (string) $event[
                    'product_unit'
                ];

                $products[$productId][
                    'category_name'
                ] = (string) $event[
                    'category_name'
                ];
            }

            $this->addEvent(
                $products[$productId],
                $event
            );

            $categoryId =
                $event['category_id'] !== null
                    ? (int) $event[
                        'category_id'
                    ]
                    : 0;

            if (
                !isset(
                    $categories[$categoryId]
                )
            ) {
                $categories[$categoryId] =
                    $this->emptyAggregate();

                $categories[$categoryId][
                    'category_id'
                ] = $categoryId;

                $categories[$categoryId][
                    'category_name'
                ] = (string) $event[
                    'category_name'
                ];
            }

            $this->addEvent(
                $categories[$categoryId],
                $event
            );

            $clientId =
                $event['client_id'] !== null
                    ? (int) $event[
                        'client_id'
                    ]
                    : 0;

            if (!isset($clients[$clientId])) {
                $clients[$clientId] =
                    $this->emptyAggregate();

                $clients[$clientId][
                    'client_id'
                ] = $clientId;

                $clients[$clientId][
                    'client_name'
                ] = (string) $event[
                    'client_name'
                ];
            }

            $this->addEvent(
                $clients[$clientId],
                $event
            );

            $warehouseId =
                (int) $event[
                    'warehouse_id'
                ];

            if (
                !isset(
                    $warehouses[$warehouseId]
                )
            ) {
                $warehouses[$warehouseId] =
                    $this->emptyAggregate();

                $warehouses[$warehouseId][
                    'warehouse_id'
                ] = $warehouseId;

                $warehouses[$warehouseId][
                    'warehouse_name'
                ] = (string) $event[
                    'warehouse_name'
                ];

                $warehouses[$warehouseId][
                    'warehouse_code'
                ] = (string) $event[
                    'warehouse_code'
                ];
            }

            $this->addEvent(
                $warehouses[$warehouseId],
                $event
            );

            $saleId =
                (int) $event['sale_id'];

            if (!isset($sales[$saleId])) {
                $sales[$saleId] =
                    $this->emptyAggregate();

                $sales[$saleId][
                    'sale_id'
                ] = $saleId;

                $sales[$saleId][
                    'sale_number'
                ] = (string) $event[
                    'sale_number'
                ];

                $sales[$saleId][
                    'client_name'
                ] = (string) $event[
                    'client_name'
                ];

                $sales[$saleId][
                    'warehouse_name'
                ] = (string) $event[
                    'warehouse_name'
                ];

                $sales[$saleId][
                    'first_event_date'
                ] = $eventDate;

                $sales[$saleId][
                    'last_event_date'
                ] = $eventDate;
            }

            if (
                $eventDate <
                $sales[$saleId][
                    'first_event_date'
                ]
            ) {
                $sales[$saleId][
                    'first_event_date'
                ] = $eventDate;
            }

            if (
                $eventDate >
                $sales[$saleId][
                    'last_event_date'
                ]
            ) {
                $sales[$saleId][
                    'last_event_date'
                ] = $eventDate;
            }

            $this->addEvent(
                $sales[$saleId],
                $event
            );

            if (
                (string) $event[
                    'source_type'
                ] === 'sale'
            ) {
                $saleIds[$saleId] = true;

                $band =
                    $this->bandForEvent(
                        $event
                    );

                $this->addEvent(
                    $bands[$band],
                    $event
                );
            }

            if (
                $event[
                    'sales_return_id'
                ] !== null
            ) {
                $returnIds[
                    (int) $event[
                        'sales_return_id'
                    ]
                ] = true;
            }
        }

        $summary['sale_count'] =
            count($saleIds);

        $summary['return_count'] =
            count($returnIds);

        $this->finishAggregate(
            $summary
        );

        foreach ($periods as &$period) {
            $this->finishAggregate(
                $period
            );
        }

        unset($period);

        foreach ($products as &$product) {
            $this->finishAggregate(
                $product
            );
        }

        unset($product);

        foreach ($categories as &$category) {
            $this->finishAggregate(
                $category
            );
        }

        unset($category);

        foreach ($clients as &$client) {
            $this->finishAggregate(
                $client
            );
        }

        unset($client);

        foreach (
            $warehouses as &$warehouse
        ) {
            $this->finishAggregate(
                $warehouse
            );
        }

        unset($warehouse);

        foreach ($sales as &$sale) {
            $this->finishAggregate(
                $sale
            );
        }

        unset($sale);

        foreach ($bands as &$band) {
            $this->finishAggregate(
                $band
            );
        }

        unset($band);

        ksort($periods);

        $this->sortByMargin(
            $products
        );

        $this->sortByMargin(
            $categories
        );

        $this->sortByMargin(
            $clients
        );

        $this->sortByMargin(
            $warehouses
        );

        usort(
            $sales,
            static function (
                array $first,
                array $second
            ): int {
                return strcmp(
                    (string) $second[
                        'last_event_date'
                    ],
                    (string) $first[
                        'last_event_date'
                    ]
                );
            }
        );

        $belowTargetProducts =
            array_values(
                array_filter(
                    $products,
                    static function (
                        array $product
                    ) use (
                        $minimumMargin
                    ): bool {
                        return
                            $product[
                                'weighted_margin_percent'
                            ] !== null
                            &&
                            (float) $product[
                                'weighted_margin_percent'
                            ] <
                            $minimumMargin;
                    }
                )
            );

        $negativeProducts =
            array_values(
                array_filter(
                    $products,
                    static function (
                        array $product
                    ): bool {
                        return
                            (float) $product[
                                'known_gross_profit'
                            ] < -0.005;
                    }
                )
            );

        $belowTargetSales =
            array_values(
                array_filter(
                    $sales,
                    static function (
                        array $sale
                    ) use (
                        $minimumMargin
                    ): bool {
                        return
                            $sale[
                                'weighted_margin_percent'
                            ] !== null
                            &&
                            (float) $sale[
                                'weighted_margin_percent'
                            ] <
                            $minimumMargin;
                    }
                )
            );

        $this->sortByMargin(
            $belowTargetProducts
        );

        $this->sortByMargin(
            $negativeProducts
        );

        $this->sortByMargin(
            $belowTargetSales
        );

        return [
            'summary' =>
                $summary,

            'periods' =>
                array_values($periods),

            'products' =>
                array_values($products),

            'categories' =>
                array_values($categories),

            'clients' =>
                array_values($clients),

            'warehouses' =>
                array_values($warehouses),

            'sales' =>
                array_values($sales),

            'bands' =>
                $bands,

            'below_target_products' =>
                $belowTargetProducts,

            'negative_products' =>
                $negativeProducts,

            'below_target_sales' =>
                $belowTargetSales,

            'events' =>
                $decoratedEvents,

            'minimum_margin' =>
                $minimumMargin,
        ];
    }

    private function emptyAggregate(): array
    {
        return [
            'event_count' => 0,

            'sale_row_count' => 0,

            'return_row_count' => 0,

            'gross_amount' => 0.0,

            'discount_amount' => 0.0,

            'net_revenue' => 0.0,

            'costed_revenue' => 0.0,

            'known_cogs' => 0.0,

            'known_gross_profit' => 0.0,

            'weighted_margin_percent' =>
                null,

            'markup_percent' =>
                null,

            'discount_rate_percent' =>
                null,

            'cost_coverage_percent' =>
                null,

            'total_absolute_revenue' =>
                0.0,

            'costed_absolute_revenue' =>
                0.0,

            'sold_quantity' => 0.0,

            'returned_quantity' => 0.0,

            'restocked_quantity' => 0.0,

            'costed_event_count' => 0,

            'uncosted_event_count' => 0,

            'below_target_row_count' =>
                0,

            'negative_row_count' => 0,

            'below_target_revenue' =>
                0.0,

            'below_target_profit' =>
                0.0,

            'sale_count' => 0,

            'return_count' => 0,
        ];
    }

    private function addEvent(
        array &$aggregate,
        array $event
    ): void {
        $grossAmount = round(
            (float) (
                $event[
                    'gross_amount'
                ] ?? 0
            ),
            2
        );

        $discountAmount = round(
            (float) (
                $event[
                    'discount_amount'
                ] ?? 0
            ),
            2
        );

        $revenue = round(
            (float) $event[
                'revenue_amount'
            ],
            2
        );

        $aggregate['event_count']++;

        $aggregate['gross_amount'] +=
            $grossAmount;

        $aggregate[
            'discount_amount'
        ] += $discountAmount;

        $aggregate['net_revenue'] +=
            $revenue;

        $aggregate[
            'total_absolute_revenue'
        ] += abs($revenue);

        $aggregate['sold_quantity'] +=
            (float) $event[
                'sold_quantity'
            ];

        $aggregate[
            'returned_quantity'
        ] +=
            (float) $event[
                'returned_quantity'
            ];

        $aggregate[
            'restocked_quantity'
        ] +=
            (float) $event[
                'restocked_quantity'
            ];

        if (
            (string) $event[
                'source_type'
            ] === 'sale'
        ) {
            $aggregate[
                'sale_row_count'
            ]++;
        } else {
            $aggregate[
                'return_row_count'
            ]++;
        }

        $isCosted =
            (int) $event[
                'is_costed'
            ] === 1;

        if (!$isCosted) {
            $aggregate[
                'uncosted_event_count'
            ]++;

            return;
        }

        $aggregate[
            'costed_event_count'
        ]++;

        $aggregate[
            'costed_revenue'
        ] += $revenue;

        $aggregate[
            'costed_absolute_revenue'
        ] += abs($revenue);

        $aggregate['known_cogs'] +=
            (float) $event[
                'cogs_amount'
            ];

        $aggregate[
            'known_gross_profit'
        ] +=
            (float) $event[
                'gross_profit_amount'
            ];

        if (
            (string) $event[
                'source_type'
            ] !== 'sale'
        ) {
            return;
        }

        $marginStatus =
            (string) $event[
                'margin_status'
            ];

        if (
            in_array(
                $marginStatus,
                [
                    'negative',
                    'below_target',
                ],
                true
            )
        ) {
            $aggregate[
                'below_target_row_count'
            ]++;

            $aggregate[
                'below_target_revenue'
            ] += $revenue;

            $aggregate[
                'below_target_profit'
            ] +=
                (float) $event[
                    'gross_profit_amount'
                ];
        }

        if (
            $marginStatus ===
            'negative'
        ) {
            $aggregate[
                'negative_row_count'
            ]++;
        }
    }

    private function finishAggregate(
        array &$aggregate
    ): void {
        $moneyFields = [
            'gross_amount',
            'discount_amount',
            'net_revenue',
            'costed_revenue',
            'known_cogs',
            'known_gross_profit',
            'total_absolute_revenue',
            'costed_absolute_revenue',
            'below_target_revenue',
            'below_target_profit',
        ];

        foreach ($moneyFields as $field) {
            $aggregate[$field] = round(
                (float) $aggregate[$field],
                2
            );
        }

        $aggregate['sold_quantity'] =
            round(
                (float) $aggregate[
                    'sold_quantity'
                ],
                3
            );

        $aggregate[
            'returned_quantity'
        ] = round(
            (float) $aggregate[
                'returned_quantity'
            ],
            3
        );

        $aggregate[
            'restocked_quantity'
        ] = round(
            (float) $aggregate[
                'restocked_quantity'
            ],
            3
        );

        if (
            (float) $aggregate[
                'costed_revenue'
            ] > 0.005
        ) {
            $aggregate[
                'weighted_margin_percent'
            ] = round(
                (
                    (float) $aggregate[
                        'known_gross_profit'
                    ]
                    /
                    (float) $aggregate[
                        'costed_revenue'
                    ]
                ) * 100,
                2
            );
        }

        if (
            (float) $aggregate[
                'known_cogs'
            ] > 0.005
        ) {
            $aggregate[
                'markup_percent'
            ] = round(
                (
                    (float) $aggregate[
                        'known_gross_profit'
                    ]
                    /
                    (float) $aggregate[
                        'known_cogs'
                    ]
                ) * 100,
                2
            );
        }

        if (
            (float) $aggregate[
                'gross_amount'
            ] > 0.005
        ) {
            $aggregate[
                'discount_rate_percent'
            ] = round(
                (
                    (float) $aggregate[
                        'discount_amount'
                    ]
                    /
                    (float) $aggregate[
                        'gross_amount'
                    ]
                ) * 100,
                2
            );
        }

        if (
            (float) $aggregate[
                'total_absolute_revenue'
            ] > 0.005
        ) {
            $aggregate[
                'cost_coverage_percent'
            ] = round(
                (
                    (float) $aggregate[
                        'costed_absolute_revenue'
                    ]
                    /
                    (float) $aggregate[
                        'total_absolute_revenue'
                    ]
                ) * 100,
                2
            );
        }
    }

    private function decorateEvent(
        array $event,
        float $minimumMargin
    ): array {
        $event[
            'event_margin_percent'
        ] = null;

        $event[
            'event_markup_percent'
        ] = null;

        $event[
            'event_discount_rate_percent'
        ] = null;

        $event['margin_status'] =
            'return';

        if (
            (string) $event[
                'source_type'
            ] !== 'sale'
        ) {
            return $event;
        }

        if (
            (int) $event[
                'is_costed'
            ] !== 1
        ) {
            $event['margin_status'] =
                'uncosted';

            return $event;
        }

        $revenue =
            (float) $event[
                'revenue_amount'
            ];

        $cogs =
            (float) $event[
                'cogs_amount'
            ];

        $profit =
            (float) $event[
                'gross_profit_amount'
            ];

        $gross =
            (float) (
                $event[
                    'gross_amount'
                ] ?? 0
            );

        $discount =
            (float) (
                $event[
                    'discount_amount'
                ] ?? 0
            );

        if ($revenue > 0.005) {
            $event[
                'event_margin_percent'
            ] = round(
                (
                    $profit /
                    $revenue
                ) * 100,
                2
            );
        }

        if ($cogs > 0.005) {
            $event[
                'event_markup_percent'
            ] = round(
                (
                    $profit /
                    $cogs
                ) * 100,
                2
            );
        }

        if ($gross > 0.005) {
            $event[
                'event_discount_rate_percent'
            ] = round(
                (
                    $discount /
                    $gross
                ) * 100,
                2
            );
        }

        if ($profit < -0.005) {
            $event['margin_status'] =
                'negative';

            return $event;
        }

        if (
            $event[
                'event_margin_percent'
            ] !== null
            &&
            (float) $event[
                'event_margin_percent'
            ] < $minimumMargin
        ) {
            $event['margin_status'] =
                'below_target';

            return $event;
        }

        $event['margin_status'] =
            'meets_target';

        return $event;
    }

    private function bandForEvent(
        array $event
    ): string {
        $status =
            (string) $event[
                'margin_status'
            ];

        if (
            array_key_exists(
                $status,
                [
                    'negative' => true,
                    'below_target' => true,
                    'meets_target' => true,
                    'uncosted' => true,
                ]
            )
        ) {
            return $status;
        }

        return 'uncosted';
    }

    private function sortByMargin(
        array &$rows
    ): void {
        usort(
            $rows,
            static function (
                array $first,
                array $second
            ): int {
                $firstMargin =
                    $first[
                        'weighted_margin_percent'
                    ];

                $secondMargin =
                    $second[
                        'weighted_margin_percent'
                    ];

                if (
                    $firstMargin === null &&
                    $secondMargin === null
                ) {
                    return
                        (float) $second[
                            'net_revenue'
                        ]
                        <=>
                        (float) $first[
                            'net_revenue'
                        ];
                }

                if ($firstMargin === null) {
                    return 1;
                }

                if ($secondMargin === null) {
                    return -1;
                }

                return
                    (float) $firstMargin
                    <=>
                    (float) $secondMargin;
            }
        );
    }
}