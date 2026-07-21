<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesReturn;
use App\Models\StockLevel;
use App\Models\WarehouseTransaction;
use Exception;
use PDO;

class SaleService
{
    private PDO $db;

    private Sale $saleModel;

    private SaleItem $saleItemModel;

    private SalesReturn $salesReturnModel;

    private Product $productModel;

    private StockLevel $stockLevelModel;

    private WarehouseTransaction
        $warehouseTransactionModel;

    private InventoryCostService
        $inventoryCostService;

    private AuditLogService
        $auditLogService;

    private TaxService
        $taxService;

    private PromotionService
        $promotionService;

    public function __construct()
    {
        $this->db =
            Database::getConnection();

        $this->saleModel =
            new Sale();

        $this->saleItemModel =
            new SaleItem();

        $this->salesReturnModel =
            new SalesReturn();

        $this->productModel =
            new Product();

        $this->stockLevelModel =
            new StockLevel();

        $this->warehouseTransactionModel =
            new WarehouseTransaction();

        $this->inventoryCostService =
            new InventoryCostService();

        $this->auditLogService =
            new AuditLogService();

        $this->taxService =
            new TaxService();

        $this->promotionService =
            new PromotionService();
    }

    public function createSale(
        array $data
    ): array {
        try {
            $this->db->beginTransaction();

            $companyId =
                (int) $data['company_id'];

            $warehouseId =
                (int) $data['warehouse_id'];

            $userId =
                (int) $data['user_id'];

            $taxConfiguration =
                $this->taxService
                    ->salesConfiguration(
                        $companyId
                    );

            $saleNumber =
                $this->saleModel
                    ->generateNextSaleNumber(
                        $companyId
                    );

            $items =
                $this->prepareItems(
                    $companyId,
                    $warehouseId,
                    $data['items'],
                    $taxConfiguration
                );

            $appliedPromotion =
                $this->promotionService
                    ->applyToItems(
                        (int) (
                            $data[
                                'promotion_id'
                            ] ?? 0
                        ),

                        (string) (
                            $data[
                                'promotion_code'
                            ] ?? ''
                        ),

                        $companyId,
                        $items
                    );

            $items =
                $appliedPromotion['items'];

            $totals =
                $this->calculateTotals(
                    $items
                );

            $saleId =
                $this->saleModel
                    ->create([
                        'company_id' =>
                            $companyId,

                        'client_id' =>
                            $data['client_id'],

                        'warehouse_id' =>
                            $warehouseId,

                        'user_id' =>
                            $userId,

                        'sale_number' =>
                            $saleNumber,

                        'sale_date' =>
                            $data['sale_date'],

                        'status' =>
                            'completed',

                        'vat_registered' =>
                            $taxConfiguration[
                                'vat_registered'
                            ]
                                ? 1
                                : 0,

                        'prices_include_vat' =>
                            $taxConfiguration[
                                'prices_include_vat'
                            ]
                                ? 1
                                : 0,

                        'default_vat_rate' =>
                            $taxConfiguration[
                                'vat_rate'
                            ],

                        'subtotal' =>
                            $totals['subtotal'],

                        'discount_amount' =>
                            $totals[
                                'discount_amount'
                            ],

                        'tax_amount' =>
                            $totals['tax_amount'],

                        'total_amount' =>
                            $totals[
                                'total_amount'
                            ],

                        'payment_method' =>
                            $data[
                                'payment_method'
                            ],

                        'note' =>
                            $data['note'],
                    ]);

            foreach ($items as $item) {
                /*
                 * Изписваме количеството по текущата
                 * среднопретеглена себестойност.
                 */
                $costMovement =
                    $this->inventoryCostService
                        ->issue(
                            $companyId,
                            (int) $item[
                                'product_id'
                            ],
                            $warehouseId,
                            (float) $item[
                                'quantity'
                            ]
                        );

                $unitCost = round(
                    (float) $costMovement[
                        'unit_cost'
                    ],
                    4
                );

                $totalCost = round(
                    (float) $costMovement[
                        'total_cost'
                    ],
                    4
                );

                /*
                 * net_amount вече съдържа ефекта
                 * от ръчната отстъпка и промоцията.
                 */
                $grossProfit = round(
                    (float) $item[
                        'net_amount'
                    ] - $totalCost,
                    2
                );

                $grossMarginPercent =
                    (float) $item[
                        'net_amount'
                    ] > 0
                        ? round(
                            (
                                $grossProfit /
                                (float) $item[
                                    'net_amount'
                                ]
                            ) * 100,
                            2
                        )
                        : 0.0;

                $created =
                    $this->saleItemModel
                        ->create([
                            'sale_id' =>
                                $saleId,

                            'company_id' =>
                                $companyId,

                            'product_id' =>
                                $item[
                                    'product_id'
                                ],

                            'product_name' =>
                                $item[
                                    'product_name'
                                ],

                            'product_internal_code' =>
                                $item[
                                    'product_internal_code'
                                ],

                            'quantity' =>
                                $item[
                                    'quantity'
                                ],

                            'unit' =>
                                $item['unit'],

                            'unit_price' =>
                                $item[
                                    'unit_price'
                                ],

                            'unit_cost' =>
                                $unitCost,

                            'total_cost' =>
                                $totalCost,

                            'gross_profit' =>
                                $grossProfit,

                            'gross_margin_percent' =>
                                $grossMarginPercent,

                            'discount_amount' =>
                                $item[
                                    'discount_amount'
                                ],

                            'promotion_discount_amount' =>
                                $item[
                                    'promotion_discount_amount'
                                ] ?? 0,

                            'vat_rate' =>
                                $item[
                                    'vat_rate'
                                ],

                            'net_amount' =>
                                $item[
                                    'net_amount'
                                ],

                            'tax_amount' =>
                                $item[
                                    'tax_amount'
                                ],

                            'total_price' =>
                                $item[
                                    'total_price'
                                ],
                        ]);

                if (!$created) {
                    throw new Exception(
                        'Sale item could not be created.'
                    );
                }

                $transactionData = [
                    'company_id' =>
                        $companyId,

                    'product_id' =>
                        (int) $item[
                            'product_id'
                        ],

                    'from_warehouse_id' =>
                        $warehouseId,

                    'to_warehouse_id' =>
                        null,

                    'user_id' =>
                        $userId,

                    'type' =>
                        'sale',

                    'quantity' =>
                        (float) $item[
                            'quantity'
                        ],

                    'reference_type' =>
                        'sale',

                    'reference_id' =>
                        $saleId,

                    'note' =>
                        'Sale ' .
                        $saleNumber,
                ];

                $transactionData =
                    array_merge(
                        $transactionData,

                        $this
                            ->inventoryCostService
                            ->outgoingTransactionFields(
                                $costMovement
                            )
                    );

                $transactionCreated =
                    $this
                        ->warehouseTransactionModel
                        ->create(
                            $transactionData
                        );

                if (!$transactionCreated) {
                    throw new Exception(
                        'Warehouse transaction could not be created.'
                    );
                }
            }

            $this->promotionService
                ->recordUsage(
                    $saleId,
                    $companyId,
                    $appliedPromotion
                );

            $this->auditLogService
                ->log(
                    $companyId,
                    $userId,
                    'create',
                    'sale',
                    $saleId,
                    'Created sale ' .
                        $saleNumber
                );

            $this->db->commit();

            return [
                'success' => true,

                'sale_id' =>
                    $saleId,

                'error' => null,
            ];
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,

                'sale_id' => null,

                'error' =>
                    $exception->getMessage(),
            ];
        }
    }

    public function cancelSale(
        int $saleId,
        int $companyId,
        int $userId
    ): array {
        try {
            $this->db->beginTransaction();

            $sale =
                $this->saleModel
                    ->findByIdAndCompany(
                        $saleId,
                        $companyId
                    );

            if ($sale === null) {
                throw new Exception(
                    'Sale was not found.'
                );
            }

            if (
                (string) $sale['status'] ===
                'cancelled'
            ) {
                throw new Exception(
                    'Sale is already cancelled.'
                );
            }

            if (
                (string) $sale['status'] !==
                'completed'
            ) {
                throw new Exception(
                    'Only completed sales can be cancelled.'
                );
            }

            if (
                $this->salesReturnModel
                    ->hasActiveForSale(
                        $saleId,
                        $companyId
                    )
            ) {
                throw new Exception(
                    'A sale with draft or completed sales returns cannot be cancelled.'
                );
            }

            $items =
                $this->saleItemModel
                    ->allBySale(
                        $saleId,
                        $companyId
                    );

            if (empty($items)) {
                throw new Exception(
                    'Sale has no items.'
                );
            }

            $warehouseId =
                (int) $sale[
                    'warehouse_id'
                ];

            foreach ($items as $item) {
                /*
                 * Новите продажби имат точен cost
                 * snapshot в sale_items.unit_cost.
                 */
                $restoreUnitCost =
                    $item['unit_cost'] !== null
                        ? (float) $item[
                            'unit_cost'
                        ]
                        : 0.0;

                /*
                 * Старите продажби нямат историческа
                 * себестойност. Използваме последната
                 * покупна или ориентировъчната цена.
                 */
                if ($restoreUnitCost <= 0) {
                    $product =
                        $this->productModel
                            ->findByIdAndCompany(
                                (int) $item[
                                    'product_id'
                                ],
                                $companyId
                            );

                    if ($product === null) {
                        throw new Exception(
                            'Product was not found while cancelling sale.'
                        );
                    }

                    $restoreUnitCost =
                        (float) (
                            $product[
                                'last_purchase_cost'
                            ] ??
                            $product[
                                'purchase_price'
                            ] ??
                            0
                        );
                }

                $costMovement =
                    $this->inventoryCostService
                        ->receive(
                            $companyId,
                            (int) $item[
                                'product_id'
                            ],
                            $warehouseId,
                            (float) $item[
                                'quantity'
                            ],
                            $restoreUnitCost
                        );

                $transactionData = [
                    'company_id' =>
                        $companyId,

                    'product_id' =>
                        (int) $item[
                            'product_id'
                        ],

                    'from_warehouse_id' =>
                        null,

                    'to_warehouse_id' =>
                        $warehouseId,

                    'user_id' =>
                        $userId,

                    'type' =>
                        'sale_cancel',

                    'quantity' =>
                        (float) $item[
                            'quantity'
                        ],

                    'reference_type' =>
                        'sale',

                    'reference_id' =>
                        $saleId,

                    'note' =>
                        'Cancel sale ' .
                        (string) $sale[
                            'sale_number'
                        ],
                ];

                $transactionData =
                    array_merge(
                        $transactionData,

                        $this
                            ->inventoryCostService
                            ->incomingTransactionFields(
                                $costMovement
                            )
                    );

                $transactionCreated =
                    $this
                        ->warehouseTransactionModel
                        ->create(
                            $transactionData
                        );

                if (!$transactionCreated) {
                    throw new Exception(
                        'Warehouse transaction could not be created.'
                    );
                }
            }

            $this->promotionService
                ->releaseForCancelledSale(
                    $saleId,
                    $companyId
                );

            $cancelled =
                $this->saleModel
                    ->cancel(
                        $saleId,
                        $companyId
                    );

            if (!$cancelled) {
                throw new Exception(
                    'Sale could not be cancelled.'
                );
            }

            $this->auditLogService
                ->log(
                    $companyId,
                    $userId,
                    'cancel',
                    'sale',
                    $saleId,
                    'Cancelled sale ' .
                        (string) $sale[
                            'sale_number'
                        ]
                );

            $this->db->commit();

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,

                'error' =>
                    $exception->getMessage(),
            ];
        }
    }

    private function prepareItems(
        int $companyId,
        int $warehouseId,
        array $items,
        array $taxConfiguration
    ): array {
        $preparedItems = [];

        foreach ($items as $item) {
            $productId =
                (int) (
                    $item[
                        'product_id'
                    ] ?? 0
                );

            $quantity =
                (float) (
                    $item[
                        'quantity'
                    ] ?? 0
                );

            $unitPrice =
                (float) (
                    $item[
                        'unit_price'
                    ] ?? 0
                );

            $discountAmount =
                (float) (
                    $item[
                        'discount_amount'
                    ] ?? 0
                );

            if ($productId <= 0) {
                continue;
            }

            $product =
                $this->productModel
                    ->findByIdAndCompany(
                        $productId,
                        $companyId
                    );

            if ($product === null) {
                throw new Exception(
                    'Selected product was not found.'
                );
            }

            $taxResult =
                $this->taxService
                    ->calculateLine(
                        $quantity,
                        $unitPrice,
                        $discountAmount,
                        $taxConfiguration
                    );

            $hasEnoughStock =
                $this->stockLevelModel
                    ->hasEnoughStock(
                        $companyId,
                        $productId,
                        $warehouseId,
                        $quantity
                    );

            if (!$hasEnoughStock) {
                throw new Exception(
                    'Not enough stock for product: ' .
                    (string) $product[
                        'name'
                    ]
                );
            }

            $preparedItems[] = [
                'product_id' =>
                    $productId,

                'product_name' =>
                    $product['name'],

                'product_internal_code' =>
                    $product[
                        'internal_code'
                    ],

                'quantity' =>
                    $quantity,

                'unit' =>
                    $product['unit'],

                'unit_price' =>
                    $unitPrice,

                'discount_amount' =>
                    $taxResult[
                        'discount_amount'
                    ],

                'promotion_discount_amount' =>
                    0.00,

                'subtotal' =>
                    $taxResult[
                        'subtotal'
                    ],

                'vat_rate' =>
                    $taxResult[
                        'vat_rate'
                    ],

                'net_amount' =>
                    $taxResult[
                        'net_amount'
                    ],

                'tax_amount' =>
                    $taxResult[
                        'tax_amount'
                    ],

                'total_price' =>
                    $taxResult[
                        'total_amount'
                    ],
            ];
        }

        if (empty($preparedItems)) {
            throw new Exception(
                'Sale must have at least one product.'
            );
        }

        return $preparedItems;
    }

    private function calculateTotals(
        array $items
    ): array {
        $subtotal = 0.00;
        $discountAmount = 0.00;
        $taxAmount = 0.00;
        $totalAmount = 0.00;

        foreach ($items as $item) {
            $subtotal +=
                (float) $item[
                    'subtotal'
                ];

            $discountAmount +=
                (float) $item[
                    'discount_amount'
                ];

            $taxAmount +=
                (float) $item[
                    'tax_amount'
                ];

            $totalAmount +=
                (float) $item[
                    'total_price'
                ];
        }

        return [
            'subtotal' =>
                round(
                    $subtotal,
                    2
                ),

            'discount_amount' =>
                round(
                    $discountAmount,
                    2
                ),

            'tax_amount' =>
                round(
                    $taxAmount,
                    2
                ),

            'total_amount' =>
                round(
                    $totalAmount,
                    2
                ),
        ];
    }
}