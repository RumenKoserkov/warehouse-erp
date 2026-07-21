<?php

declare(strict_types=1);

namespace App\Services;

class ProfitReportService
{
    public function build(
        array $events,
        string $grouping = 'daily'
    ): array {
        $summary =
            $this->emptyAggregate();

        $periods = [];
        $products = [];
        $clients = [];
        $warehouses = [];
        $sales = [];

        $saleIds = [];
        $returnIds = [];

        foreach ($events as $event) {
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

            $productKey =
                (int) $event['product_id'];

            if (!isset($products[$productKey])) {
                $products[$productKey] =
                    $this->emptyAggregate();

                $products[$productKey][
                    'product_id'
                ] = $productKey;

                $products[$productKey][
                    'product_name'
                ] = (string) $event[
                    'product_name'
                ];

                $products[$productKey][
                    'product_internal_code'
                ] = (string) $event[
                    'product_internal_code'
                ];

                $products[$productKey][
                    'product_unit'
                ] = (string) $event[
                    'product_unit'
                ];

                $products[$productKey][
                    'category_name'
                ] = (string) $event[
                    'category_name'
                ];
            }

            $this->addEvent(
                $products[$productKey],
                $event
            );

            $clientId =
                $event['client_id'] !== null
                    ? (int) $event[
                        'client_id'
                    ]
                    : 0;

            $clientKey =
                (string) $clientId;

            if (!isset($clients[$clientKey])) {
                $clients[$clientKey] =
                    $this->emptyAggregate();

                $clients[$clientKey][
                    'client_id'
                ] = $clientId;

                $clients[$clientKey][
                    'client_name'
                ] = (string) $event[
                    'client_name'
                ];
            }

            $this->addEvent(
                $clients[$clientKey],
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

        $this->finishAggregate($summary);

        foreach ($periods as &$period) {
            $this->finishAggregate($period);
        }

        unset($period);

        foreach ($products as &$product) {
            $this->finishAggregate($product);
        }

        unset($product);

        foreach ($clients as &$client) {
            $this->finishAggregate($client);
        }

        unset($client);

        foreach ($warehouses as &$warehouse) {
            $this->finishAggregate($warehouse);
        }

        unset($warehouse);

        foreach ($sales as &$sale) {
            $this->finishAggregate($sale);
        }

        unset($sale);

        ksort($periods);

        usort(
            $products,
            static function (
                array $first,
                array $second
            ): int {
                return
                    $second[
                        'known_gross_profit'
                    ]
                    <=>
                    $first[
                        'known_gross_profit'
                    ];
            }
        );

        usort(
            $clients,
            static function (
                array $first,
                array $second
            ): int {
                return
                    $second[
                        'known_gross_profit'
                    ]
                    <=>
                    $first[
                        'known_gross_profit'
                    ];
            }
        );

        usort(
            $warehouses,
            static function (
                array $first,
                array $second
            ): int {
                return
                    $second[
                        'known_gross_profit'
                    ]
                    <=>
                    $first[
                        'known_gross_profit'
                    ];
            }
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

        $lossProducts = array_values(
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

        usort(
            $lossProducts,
            static function (
                array $first,
                array $second
            ): int {
                return
                    $first[
                        'known_gross_profit'
                    ]
                    <=>
                    $second[
                        'known_gross_profit'
                    ];
            }
        );

        return [
            'summary' => $summary,

            'periods' =>
                array_values($periods),

            'products' =>
                array_values($products),

            'loss_products' =>
                $lossProducts,

            'clients' =>
                array_values($clients),

            'warehouses' =>
                array_values($warehouses),

            'sales' =>
                array_values($sales),

            'events' =>
                $events,
        ];
    }

    private function emptyAggregate(): array
    {
        return [
            'event_count' => 0,

            'net_revenue' => 0.0,

            'costed_revenue' => 0.0,

            'uncosted_net_revenue' => 0.0,

            'uncosted_absolute_revenue' => 0.0,

            'known_cogs' => 0.0,

            'known_gross_profit' => 0.0,

            'known_margin_percent' => null,

            'sold_quantity' => 0.0,

            'returned_quantity' => 0.0,

            'restocked_quantity' => 0.0,

            'return_revenue' => 0.0,

            'uncosted_event_count' => 0,

            'costed_event_count' => 0,

            'sale_count' => 0,

            'return_count' => 0,
        ];
    }

    private function addEvent(
        array &$aggregate,
        array $event
    ): void {
        $revenue = round(
            (float) $event[
                'revenue_amount'
            ],
            2
        );

        $aggregate['event_count']++;

        $aggregate['net_revenue'] +=
            $revenue;

        $aggregate['sold_quantity'] +=
            (float) $event[
                'sold_quantity'
            ];

        $aggregate['returned_quantity'] +=
            (float) $event[
                'returned_quantity'
            ];

        $aggregate['restocked_quantity'] +=
            (float) $event[
                'restocked_quantity'
            ];

        if (
            (string) $event[
                'source_type'
            ] === 'sales_return'
        ) {
            $aggregate['return_revenue'] +=
                abs($revenue);
        }

        $isCosted =
            (int) $event['is_costed'] === 1;

        if (!$isCosted) {
            $aggregate[
                'uncosted_event_count'
            ]++;

            $aggregate[
                'uncosted_net_revenue'
            ] += $revenue;

            $aggregate[
                'uncosted_absolute_revenue'
            ] += abs($revenue);

            return;
        }

        $aggregate[
            'costed_event_count'
        ]++;

        $aggregate[
            'costed_revenue'
        ] += $revenue;

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
    }

    private function finishAggregate(
        array &$aggregate
    ): void {
        $moneyFields = [
            'net_revenue',
            'costed_revenue',
            'uncosted_net_revenue',
            'uncosted_absolute_revenue',
            'known_cogs',
            'known_gross_profit',
            'return_revenue',
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

        $aggregate['returned_quantity'] =
            round(
                (float) $aggregate[
                    'returned_quantity'
                ],
                3
            );

        $aggregate['restocked_quantity'] =
            round(
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
                'known_margin_percent'
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
        } else {
            $aggregate[
                'known_margin_percent'
            ] = null;
        }
    }
}