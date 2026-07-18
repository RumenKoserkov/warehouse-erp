<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockLevel;
use App\Models\WarehouseTransaction;
use DateTimeImmutable;
use Exception;
use PDO;
use Throwable;

class SalesReturnService
{
    private PDO $db;

    private Sale $saleModel;

    private SalesReturn $salesReturnModel;

    private SalesReturnItem $salesReturnItemModel;

    private StockLevel $stockLevelModel;

    private WarehouseTransaction $warehouseTransactionModel;

    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->db =
            Database::getConnection();

        $this->saleModel =
            new Sale();

        $this->salesReturnModel =
            new SalesReturn();

        $this->salesReturnItemModel =
            new SalesReturnItem();

        $this->stockLevelModel =
            new StockLevel();

        $this->warehouseTransactionModel =
            new WarehouseTransaction();

        $this->auditLogService =
            new AuditLogService();
    }

    public function reasonTypes(): array
    {
        return [
            'customer_return' =>
                'Customer Return',

            'wrong_product' =>
                'Wrong Product Delivered',

            'damaged_product' =>
                'Damaged Product',

            'quality_issue' =>
                'Quality Issue',

            'warranty' =>
                'Warranty Return',

            'other' =>
                'Other',
        ];
    }

    public function createDraft(
        int $saleId,
        int $companyId,
        int $userId,
        string $returnDate,
        string $reasonType,
        string $reasonDescription,
        string $notes,
        array $returnQuantities,
        array $restockQuantities,
        array $itemNotes
    ): array {
        $validationError =
            $this->validateHeader(
                $returnDate,
                $reasonType,
                $reasonDescription,
                $notes
            );

        if ($validationError !== null) {
            return [
                'success' => false,
                'sales_return_id' => null,
                'error' => $validationError,
            ];
        }

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
                (string) $sale['status'] !==
                'completed'
            ) {
                throw new Exception(
                    'Only completed sales can be returned.'
                );
            }

            if (
                $returnDate <
                (string) $sale['sale_date']
            ) {
                throw new Exception(
                    'Return date cannot be before the sale date.'
                );
            }

            if (
                $this->salesReturnModel
                    ->hasDraftForSale(
                        $saleId,
                        $companyId
                    )
            ) {
                throw new Exception(
                    'This sale already has an open sales return draft.'
                );
            }

            $preparedItems =
                $this->prepareItems(
                    $saleId,
                    $companyId,
                    $returnQuantities,
                    $restockQuantities,
                    $itemNotes
                );

            $salesReturnId =
                $this->salesReturnModel
                    ->create([
                        'company_id' =>
                            $companyId,

                        'sale_id' =>
                            $saleId,

                        'warehouse_id' =>
                            (int) $sale[
                                'warehouse_id'
                            ],

                        'return_date' =>
                            $returnDate,

                        'reason_type' =>
                            $reasonType,

                        'reason_description' =>
                            trim(
                                $reasonDescription
                            ),

                        'notes' =>
                            $this->nullableString(
                                $notes
                            ),

                        'created_by_user_id' =>
                            $userId,
                    ]);

            $returnNumber =
                $this->returnNumber(
                    $salesReturnId
                );

            $numberAssigned =
                $this->salesReturnModel
                    ->assignNumber(
                        $salesReturnId,
                        $companyId,
                        $returnNumber
                    );

            if (!$numberAssigned) {
                throw new Exception(
                    'Sales return number could not be assigned.'
                );
            }

            $this->storePreparedItems(
                $salesReturnId,
                $companyId,
                $preparedItems
            );

            $totals =
                $this->calculateTotals(
                    $preparedItems
                );

            $this->salesReturnModel
                ->updateTotals(
                    $salesReturnId,
                    $companyId,
                    $totals
                );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'sales_return',
                $salesReturnId,
                'Created sales return ' .
                $returnNumber .
                ' for sale ' .
                (string) $sale[
                    'sale_number'
                ] .
                '. Items: ' .
                count($preparedItems) .
                '.'
            );

            $this->db->commit();

            return [
                'success' => true,

                'sales_return_id' =>
                    $salesReturnId,

                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'sales_return_id' => null,
                'error' =>
                    $exception->getMessage(),
            ];
        }
    }

    public function updateDraftItems(
        int $salesReturnId,
        int $companyId,
        int $userId,
        array $returnQuantities,
        array $restockQuantities,
        array $itemNotes
    ): array {
        try {
            $this->db->beginTransaction();

            $salesReturn =
                $this->salesReturnModel
                    ->findForUpdate(
                        $salesReturnId,
                        $companyId
                    );

            if ($salesReturn === null) {
                throw new Exception(
                    'Sales return was not found.'
                );
            }

            if (
                (string) $salesReturn[
                    'status'
                ] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft sales returns can be edited.'
                );
            }

            $sale =
                $this->saleModel
                    ->findByIdAndCompany(
                        (int) $salesReturn[
                            'sale_id'
                        ],
                        $companyId
                    );

            if (
                $sale === null ||
                (string) $sale['status'] !==
                    'completed'
            ) {
                throw new Exception(
                    'The original sale is no longer returnable.'
                );
            }

            $preparedItems =
                $this->prepareItems(
                    (int) $salesReturn[
                        'sale_id'
                    ],
                    $companyId,
                    $returnQuantities,
                    $restockQuantities,
                    $itemNotes
                );

            $this->salesReturnItemModel
                ->deleteByReturn(
                    $salesReturnId,
                    $companyId
                );

            $this->storePreparedItems(
                $salesReturnId,
                $companyId,
                $preparedItems
            );

            $this->salesReturnModel
                ->updateTotals(
                    $salesReturnId,
                    $companyId,
                    $this->calculateTotals(
                        $preparedItems
                    )
                );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'update',
                'sales_return',
                $salesReturnId,
                'Updated sales return ' .
                (string) $salesReturn[
                    'return_number'
                ] .
                '. Items: ' .
                count($preparedItems) .
                '.'
            );

            $this->db->commit();

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (Throwable $exception) {
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

    public function complete(
        int $salesReturnId,
        int $companyId,
        int $userId
    ): array {
        try {
            $this->db->beginTransaction();

            $salesReturn =
                $this->salesReturnModel
                    ->findForUpdate(
                        $salesReturnId,
                        $companyId
                    );

            if ($salesReturn === null) {
                throw new Exception(
                    'Sales return was not found.'
                );
            }

            if (
                (string) $salesReturn[
                    'status'
                ] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft sales returns can be completed.'
                );
            }

            $sale =
                $this->saleModel
                    ->findByIdAndCompany(
                        (int) $salesReturn[
                            'sale_id'
                        ],
                        $companyId
                    );

            if (
                $sale === null ||
                (string) $sale['status'] !==
                    'completed'
            ) {
                throw new Exception(
                    'The original sale is no longer returnable.'
                );
            }

            $items =
                $this->salesReturnItemModel
                    ->allForUpdate(
                        $salesReturnId,
                        $companyId
                    );

            if (empty($items)) {
                throw new Exception(
                    'Sales return has no items.'
                );
            }

            $returnableRows =
                $this->salesReturnItemModel
                    ->returnableBySale(
                        (int) $salesReturn[
                            'sale_id'
                        ],
                        $companyId
                    );

            $returnableMap = [];

            foreach (
                $returnableRows as $row
            ) {
                $returnableMap[
                    (int) $row['id']
                ] = $row;
            }

            $restockedProductCount = 0;

            foreach ($items as $item) {
                $saleItemId =
                    (int) $item[
                        'sale_item_id'
                    ];

                if (
                    !isset(
                        $returnableMap[
                            $saleItemId
                        ]
                    )
                ) {
                    throw new Exception(
                        'Original sale item was not found.'
                    );
                }

                $remainingQuantity = round(
                    (float) $returnableMap[
                        $saleItemId
                    ]['remaining_quantity'],
                    3
                );

                $returnQuantity = round(
                    (float) $item[
                        'return_quantity'
                    ],
                    3
                );

                if (
                    $returnQuantity >
                    $remainingQuantity + 0.0005
                ) {
                    throw new Exception(
                        'Return quantity now exceeds the remaining quantity for product: ' .
                        (string) $item[
                            'product_name'
                        ] .
                        '.'
                    );
                }

                $productId =
                    (int) $item[
                        'product_id'
                    ];

                $warehouseId =
                    (int) $salesReturn[
                        'warehouse_id'
                    ];

                $stockLevel =
                    $this->stockLevelModel
                        ->lockForUpdate(
                            $companyId,
                            $productId,
                            $warehouseId
                        );

                if ($stockLevel === null) {
                    throw new Exception(
                        'Stock level was not found for product: ' .
                        (string) $item[
                            'product_name'
                        ] .
                        '.'
                    );
                }

                $quantityBefore = round(
                    (float) $stockLevel[
                        'quantity'
                    ],
                    3
                );

                $quantityAfter =
                    $quantityBefore;

                $restockQuantity = round(
                    (float) $item[
                        'restock_quantity'
                    ],
                    3
                );

                if ($restockQuantity > 0) {
                    $increased =
                        $this->stockLevelModel
                            ->increase(
                                $companyId,
                                $productId,
                                $warehouseId,
                                $restockQuantity
                            );

                    if (!$increased) {
                        throw new Exception(
                            'Could not return stock for product: ' .
                            (string) $item[
                                'product_name'
                            ]
                        );
                    }

                    $quantityAfter = round(
                        $quantityBefore +
                        $restockQuantity,
                        3
                    );

                    $transactionCreated =
                        $this->warehouseTransactionModel
                            ->create([
                                'company_id' =>
                                    $companyId,

                                'product_id' =>
                                    $productId,

                                'from_warehouse_id' =>
                                    null,

                                'to_warehouse_id' =>
                                    $warehouseId,

                                'user_id' =>
                                    $userId,

                                'type' =>
                                    'sale_return',

                                'quantity' =>
                                    $restockQuantity,

                                'reference_type' =>
                                    'sales_return',

                                'reference_id' =>
                                    $salesReturnId,

                                'note' =>
                                    'Sales return ' .
                                    (string) $salesReturn[
                                        'return_number'
                                    ] .
                                    ' for sale ' .
                                    (string) $sale[
                                        'sale_number'
                                    ] .
                                    '. Returned: ' .
                                    number_format(
                                        $returnQuantity,
                                        3,
                                        '.',
                                        ''
                                    ) .
                                    ', restocked: ' .
                                    number_format(
                                        $restockQuantity,
                                        3,
                                        '.',
                                        ''
                                    ) .
                                    '.',
                            ]);

                    if (!$transactionCreated) {
                        throw new Exception(
                            'Warehouse transaction could not be created for product: ' .
                            (string) $item[
                                'product_name'
                            ]
                        );
                    }

                    $restockedProductCount++;
                }

                $marked =
                    $this->salesReturnItemModel
                        ->markApplied(
                            (int) $item['id'],
                            $companyId,
                            $quantityBefore,
                            $quantityAfter
                        );

                if (!$marked) {
                    throw new Exception(
                        'Could not save resulting stock for product: ' .
                        (string) $item[
                            'product_name'
                        ]
                    );
                }
            }

            $completed =
                $this->salesReturnModel
                    ->markCompleted(
                        $salesReturnId,
                        $companyId,
                        $userId
                    );

            if (!$completed) {
                throw new Exception(
                    'Sales return could not be completed.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'complete',
                'sales_return',
                $salesReturnId,
                'Completed sales return ' .
                (string) $salesReturn[
                    'return_number'
                ] .
                ' for sale ' .
                (string) $sale[
                    'sale_number'
                ] .
                '. Items: ' .
                count($items) .
                ', restocked products: ' .
                $restockedProductCount .
                ', return total: ' .
                number_format(
                    (float) $salesReturn[
                        'total_amount'
                    ],
                    2,
                    '.',
                    ''
                ) .
                '.'
            );

            $this->db->commit();

            return [
                'success' => true,

                'item_count' =>
                    count($items),

                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'item_count' => 0,
                'error' =>
                    $exception->getMessage(),
            ];
        }
    }

    public function cancel(
        int $salesReturnId,
        int $companyId,
        int $userId,
        string $reason
    ): array {
        $reason = trim($reason);

        if ($reason === '') {
            return [
                'success' => false,
                'error' =>
                    'Cancellation reason is required.',
            ];
        }

        if (mb_strlen($reason) > 500) {
            return [
                'success' => false,
                'error' =>
                    'Cancellation reason must be maximum 500 characters.',
            ];
        }

        try {
            $this->db->beginTransaction();

            $salesReturn =
                $this->salesReturnModel
                    ->findForUpdate(
                        $salesReturnId,
                        $companyId
                    );

            if ($salesReturn === null) {
                throw new Exception(
                    'Sales return was not found.'
                );
            }

            if (
                (string) $salesReturn[
                    'status'
                ] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft sales returns can be cancelled.'
                );
            }

            $cancelled =
                $this->salesReturnModel
                    ->markCancelled(
                        $salesReturnId,
                        $companyId,
                        $userId,
                        $reason
                    );

            if (!$cancelled) {
                throw new Exception(
                    'Sales return could not be cancelled.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'cancel',
                'sales_return',
                $salesReturnId,
                'Cancelled sales return ' .
                (string) $salesReturn[
                    'return_number'
                ] .
                '. Reason: ' .
                $reason
            );

            $this->db->commit();

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (Throwable $exception) {
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

    public function summaryForSale(
        int $saleId,
        int $companyId
    ): array {
        $completedSummary =
            $this->salesReturnItemModel
                ->completedSummaryBySale(
                    $saleId,
                    $companyId
                );

        $returnableItems =
            $this->salesReturnItemModel
                ->returnableBySale(
                    $saleId,
                    $companyId
                );

        $hasReturnableItems = false;
        $remainingQuantity = 0.0;

        foreach (
            $returnableItems as $item
        ) {
            $remaining = max(
                0,
                (float) $item[
                    'remaining_quantity'
                ]
            );

            $remainingQuantity +=
                $remaining;

            if ($remaining > 0.0005) {
                $hasReturnableItems = true;
            }
        }

        return array_merge(
            $completedSummary,
            [
                'has_returnable_items' =>
                    $hasReturnableItems,

                'remaining_quantity' =>
                    round(
                        $remainingQuantity,
                        3
                    ),

                'has_draft' =>
                    $this->salesReturnModel
                        ->hasDraftForSale(
                            $saleId,
                            $companyId
                        ),
            ]
        );
    }

    private function prepareItems(
        int $saleId,
        int $companyId,
        array $returnQuantities,
        array $restockQuantities,
        array $itemNotes
    ): array {
        $returnableItems =
            $this->salesReturnItemModel
                ->returnableBySale(
                    $saleId,
                    $companyId
                );

        $preparedItems = [];

        foreach (
            $returnableItems as $saleItem
        ) {
            $saleItemId =
                (int) $saleItem['id'];

            $returnInput =
                $this->arrayScalar(
                    $returnQuantities,
                    $saleItemId
                );

            if (
                $returnInput === '' ||
                $this->numericZero(
                    $returnInput
                )
            ) {
                continue;
            }

            $returnQuantity =
                $this->parseQuantity(
                    $returnInput,
                    false
                );

            $restockInput =
                $this->arrayScalar(
                    $restockQuantities,
                    $saleItemId
                );

            if ($restockInput === '') {
                $restockQuantity =
                    $returnQuantity;
            } else {
                $restockQuantity =
                    $this->parseQuantity(
                        $restockInput,
                        true
                    );
            }

            if (
                $restockQuantity >
                $returnQuantity + 0.0005
            ) {
                throw new Exception(
                    'Restock quantity cannot exceed return quantity for product: ' .
                    (string) $saleItem[
                        'product_name'
                    ] .
                    '.'
                );
            }

            $remainingQuantity = round(
                (float) $saleItem[
                    'remaining_quantity'
                ],
                3
            );

            if (
                $returnQuantity >
                $remainingQuantity + 0.0005
            ) {
                throw new Exception(
                    'Return quantity cannot exceed the remaining quantity of ' .
                    number_format(
                        $remainingQuantity,
                        3,
                        '.',
                        ''
                    ) .
                    ' for product: ' .
                    (string) $saleItem[
                        'product_name'
                    ] .
                    '.'
                );
            }

            $itemNote =
                $this->arrayScalar(
                    $itemNotes,
                    $saleItemId
                );

            if (mb_strlen($itemNote) > 500) {
                throw new Exception(
                    'Item note must be maximum 500 characters for product: ' .
                    (string) $saleItem[
                        'product_name'
                    ] .
                    '.'
                );
            }

            $amounts =
                $this->calculateItemAmounts(
                    $saleItem,
                    $returnQuantity
                );

            $preparedItems[] = [
                'sale_item_id' =>
                    $saleItemId,

                'product_id' =>
                    (int) $saleItem[
                        'product_id'
                    ],

                'product_name' =>
                    (string) $saleItem[
                        'product_name'
                    ],

                'product_internal_code' =>
                    (string) $saleItem[
                        'product_internal_code'
                    ],

                'product_unit' =>
                    (string) $saleItem['unit'],

                'sold_quantity' =>
                    round(
                        (float) $saleItem[
                            'quantity'
                        ],
                        3
                    ),

                'return_quantity' =>
                    $returnQuantity,

                'restock_quantity' =>
                    $restockQuantity,

                'unit_price' =>
                    (float) $saleItem[
                        'unit_price'
                    ],

                'subtotal_amount' =>
                    $amounts[
                        'subtotal_amount'
                    ],

                'discount_amount' =>
                    $amounts[
                        'discount_amount'
                    ],

                'net_amount' =>
                    $amounts[
                        'net_amount'
                    ],

                'vat_rate' =>
                    (float) $saleItem[
                        'vat_rate'
                    ],

                'tax_amount' =>
                    $amounts[
                        'tax_amount'
                    ],

                'total_amount' =>
                    $amounts[
                        'total_amount'
                    ],

                'item_note' =>
                    $this->nullableString(
                        $itemNote
                    ),
            ];
        }

        if (empty($preparedItems)) {
            throw new Exception(
                'Select at least one quantity to return.'
            );
        }

        return $preparedItems;
    }

    private function calculateItemAmounts(
        array $saleItem,
        float $returnQuantity
    ): array {
        $soldQuantity = round(
            (float) $saleItem['quantity'],
            3
        );

        if ($soldQuantity <= 0) {
            throw new Exception(
                'Original sale quantity is invalid.'
            );
        }

        $remainingQuantity = round(
            (float) $saleItem[
                'remaining_quantity'
            ],
            3
        );

        $originalSubtotal = round(
            $soldQuantity *
            (float) $saleItem[
                'unit_price'
            ],
            2
        );

        $originalAmounts = [
            'subtotal_amount' =>
                $originalSubtotal,

            'discount_amount' =>
                round(
                    (float) $saleItem[
                        'discount_amount'
                    ],
                    2
                ),

            'net_amount' =>
                round(
                    (float) $saleItem[
                        'net_amount'
                    ],
                    2
                ),

            'tax_amount' =>
                round(
                    (float) $saleItem[
                        'tax_amount'
                    ],
                    2
                ),

            'total_amount' =>
                round(
                    (float) $saleItem[
                        'total_price'
                    ],
                    2
                ),
        ];

        $alreadyReturned = [
            'subtotal_amount' =>
                round(
                    (float) $saleItem[
                        'returned_subtotal'
                    ],
                    2
                ),

            'discount_amount' =>
                round(
                    (float) $saleItem[
                        'returned_discount'
                    ],
                    2
                ),

            'net_amount' =>
                round(
                    (float) $saleItem[
                        'returned_net'
                    ],
                    2
                ),

            'tax_amount' =>
                round(
                    (float) $saleItem[
                        'returned_tax'
                    ],
                    2
                ),

            'total_amount' =>
                round(
                    (float) $saleItem[
                        'returned_total'
                    ],
                    2
                ),
        ];

        $isFinalReturn =
            abs(
                $returnQuantity -
                $remainingQuantity
            ) <= 0.0005;

        $result = [];

        foreach (
            $originalAmounts as
            $field => $originalAmount
        ) {
            if ($isFinalReturn) {
                $result[$field] = round(
                    max(
                        0,
                        $originalAmount -
                        $alreadyReturned[
                            $field
                        ]
                    ),
                    2
                );

                continue;
            }

            $ratio =
                $returnQuantity /
                $soldQuantity;

            $result[$field] = round(
                max(
                    0,
                    $originalAmount *
                    $ratio
                ),
                2
            );
        }

        return $result;
    }

    private function storePreparedItems(
        int $salesReturnId,
        int $companyId,
        array $preparedItems
    ): void {
        foreach (
            $preparedItems as $item
        ) {
            $this->salesReturnItemModel
                ->create(
                    array_merge(
                        $item,
                        [
                            'sales_return_id' =>
                                $salesReturnId,

                            'company_id' =>
                                $companyId,
                        ]
                    )
                );
        }
    }

    private function calculateTotals(
        array $items
    ): array {
        $totals = [
            'subtotal_amount' => 0.0,
            'discount_amount' => 0.0,
            'net_amount' => 0.0,
            'tax_amount' => 0.0,
            'total_amount' => 0.0,
        ];

        foreach ($items as $item) {
            foreach (
                $totals as $field => $value
            ) {
                $totals[$field] +=
                    (float) $item[$field];
            }
        }

        foreach (
            $totals as $field => $value
        ) {
            $totals[$field] = round(
                $value,
                2
            );
        }

        return $totals;
    }

    private function validateHeader(
        string $returnDate,
        string $reasonType,
        string $reasonDescription,
        string $notes
    ): ?string {
        if (!$this->validDate($returnDate)) {
            return 'Return date is invalid.';
        }

        if ($returnDate > date('Y-m-d')) {
            return 'Return date cannot be in the future.';
        }

        if (
            !array_key_exists(
                $reasonType,
                $this->reasonTypes()
            )
        ) {
            return 'Invalid return reason.';
        }

        $reasonDescription =
            trim($reasonDescription);

        if ($reasonDescription === '') {
            return 'Reason description is required.';
        }

        if (
            mb_strlen(
                $reasonDescription
            ) > 500
        ) {
            return 'Reason description must be maximum 500 characters.';
        }

        if (mb_strlen($notes) > 2000) {
            return 'Notes must be maximum 2000 characters.';
        }

        return null;
    }

    private function parseQuantity(
        string $value,
        bool $allowZero
    ): float {
        $value = str_replace(
            [
                ' ',
                ',',
            ],
            [
                '',
                '.',
            ],
            trim($value)
        );

        if (
            preg_match(
                '/^\d{1,11}(?:\.\d{1,3})?$/',
                $value
            ) !== 1
        ) {
            throw new Exception(
                'Quantities must use maximum 3 decimal places.'
            );
        }

        $quantity = round(
            (float) $value,
            3
        );

        if (
            !$allowZero &&
            $quantity <= 0
        ) {
            throw new Exception(
                'Return quantity must be greater than zero.'
            );
        }

        if (
            $allowZero &&
            $quantity < 0
        ) {
            throw new Exception(
                'Restock quantity cannot be negative.'
            );
        }

        return $quantity;
    }

    private function numericZero(
        string $value
    ): bool {
        $value = str_replace(
            ',',
            '.',
            trim($value)
        );

        return is_numeric($value) &&
            abs((float) $value) <= 0.0005;
    }

    private function arrayScalar(
        array $values,
        int $key
    ): string {
        if (
            !array_key_exists(
                $key,
                $values
            ) ||
            !is_scalar($values[$key])
        ) {
            return '';
        }

        return trim(
            (string) $values[$key]
        );
    }

    private function validDate(
        string $value
    ): bool {
        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        if ($date === false) {
            return false;
        }

        return $date->format('Y-m-d') ===
            $value;
    }

    private function returnNumber(
        int $salesReturnId
    ): string {
        return 'SR-' .
            str_pad(
                (string) $salesReturnId,
                8,
                '0',
                STR_PAD_LEFT
            );
    }

    private function nullableString(
        string $value
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}