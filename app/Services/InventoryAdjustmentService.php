<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentItem;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Models\WarehouseTransaction;
use DateTimeImmutable;
use Exception;
use PDO;
use Throwable;

class InventoryAdjustmentService
{
    private PDO $db;

    private InventoryAdjustment
        $adjustmentModel;

    private InventoryAdjustmentItem
        $itemModel;

    private Warehouse $warehouseModel;

    private StockLevel $stockLevelModel;

    private WarehouseTransaction
        $transactionModel;

    private InventoryCostService
        $inventoryCostService;

    private AuditLogService
        $auditLogService;

    public function __construct()
    {
        $this->db =
            Database::getConnection();

        $this->adjustmentModel =
            new InventoryAdjustment();

        $this->itemModel =
            new InventoryAdjustmentItem();

        $this->warehouseModel =
            new Warehouse();

        $this->stockLevelModel =
            new StockLevel();

        $this->transactionModel =
            new WarehouseTransaction();

        $this->inventoryCostService =
            new InventoryCostService();

        $this->auditLogService =
            new AuditLogService();
    }

    public function reasonTypes(): array
    {
        return [
            'damage' =>
                'Damaged Goods',

            'expiry' =>
                'Expired Goods',

            'loss' =>
                'Loss or Missing Stock',

            'found_stock' =>
                'Found Additional Stock',

            'data_correction' =>
                'Data Correction',

            'other' =>
                'Other',
        ];
    }

    public function directions(): array
    {
        return [
            'increase' =>
                'Increase Stock',

            'decrease' =>
                'Decrease Stock',
        ];
    }

    public function createDraft(
        int $companyId,
        int $warehouseId,
        int $userId,
        string $adjustmentDate,
        string $reasonType,
        string $reasonDescription,
        string $notes
    ): array {
        $reasonDescription =
            trim($reasonDescription);

        $notes = trim($notes);

        if (
            !$this->validDate(
                $adjustmentDate
            )
        ) {
            return [
                'success' => false,
                'adjustment_id' => null,

                'error' =>
                    'Adjustment date is invalid.',
            ];
        }

        if (
            $adjustmentDate >
            date('Y-m-d')
        ) {
            return [
                'success' => false,
                'adjustment_id' => null,

                'error' =>
                    'Adjustment date cannot be in the future.',
            ];
        }

        if (
            !array_key_exists(
                $reasonType,
                $this->reasonTypes()
            )
        ) {
            return [
                'success' => false,
                'adjustment_id' => null,

                'error' =>
                    'Invalid adjustment reason.',
            ];
        }

        if ($reasonDescription === '') {
            return [
                'success' => false,
                'adjustment_id' => null,

                'error' =>
                    'Reason description is required.',
            ];
        }

        if (
            mb_strlen(
                $reasonDescription
            ) > 500
        ) {
            return [
                'success' => false,
                'adjustment_id' => null,

                'error' =>
                    'Reason description must be maximum 500 characters.',
            ];
        }

        if (mb_strlen($notes) > 2000) {
            return [
                'success' => false,
                'adjustment_id' => null,

                'error' =>
                    'Notes must be maximum 2000 characters.',
            ];
        }

        $warehouse =
            $this->warehouseModel
                ->findByIdAndCompany(
                    $warehouseId,
                    $companyId
                );

        if (
            $warehouse === null ||
            (int) $warehouse[
                'is_active'
            ] !== 1
        ) {
            return [
                'success' => false,
                'adjustment_id' => null,

                'error' =>
                    'Selected warehouse was not found or is inactive.',
            ];
        }

        try {
            $this->db->beginTransaction();

            $adjustmentId =
                $this->adjustmentModel
                    ->create([
                        'company_id' =>
                            $companyId,

                        'warehouse_id' =>
                            $warehouseId,

                        'adjustment_date' =>
                            $adjustmentDate,

                        'reason_type' =>
                            $reasonType,

                        'reason_description' =>
                            $reasonDescription,

                        'notes' =>
                            $this->nullableString(
                                $notes
                            ),

                        'created_by_user_id' =>
                            $userId,
                    ]);

            $adjustmentNumber =
                $this->adjustmentNumber(
                    $adjustmentId
                );

            $numberAssigned =
                $this->adjustmentModel
                    ->assignNumber(
                        $adjustmentId,
                        $companyId,
                        $adjustmentNumber
                    );

            if (!$numberAssigned) {
                throw new Exception(
                    'Adjustment number could not be assigned.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'inventory_adjustment',
                $adjustmentId,
                'Created inventory adjustment ' .
                $adjustmentNumber .
                ' for warehouse ' .
                (string) $warehouse['name'] .
                '.'
            );

            $this->db->commit();

            return [
                'success' => true,

                'adjustment_id' =>
                    $adjustmentId,

                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'adjustment_id' => null,

                'error' =>
                    $exception->getMessage(),
            ];
        }
    }

    public function addItem(
        int $adjustmentId,
        int $companyId,
        int $userId,
        int $productId,
        string $direction,
        string $quantityInput,
        string $unitCostInput,
        string $itemNote
    ): array {
        $itemNote = trim($itemNote);

        if (
            !array_key_exists(
                $direction,
                $this->directions()
            )
        ) {
            return [
                'success' => false,

                'error' =>
                    'Invalid adjustment direction.',
            ];
        }

        try {
            $quantity =
                $this->parseQuantity(
                    $quantityInput
                );

            $unitCost = null;
            $totalCost = null;

            if ($direction === 'increase') {
                $unitCost =
                    $this->parseCost(
                        $unitCostInput
                    );

                $totalCost = round(
                    $quantity * $unitCost,
                    4
                );
            }
        } catch (Throwable $exception) {
            return [
                'success' => false,

                'error' =>
                    $exception->getMessage(),
            ];
        }

        if (mb_strlen($itemNote) > 500) {
            return [
                'success' => false,

                'error' =>
                    'Item note must be maximum 500 characters.',
            ];
        }

        try {
            $this->db->beginTransaction();

            $adjustment =
                $this->adjustmentModel
                    ->findForUpdate(
                        $adjustmentId,
                        $companyId
                    );

            if ($adjustment === null) {
                throw new Exception(
                    'Inventory adjustment was not found.'
                );
            }

            if (
                (string) $adjustment[
                    'status'
                ] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft adjustments can be edited.'
                );
            }

            $product =
                $this->itemModel
                    ->findProductByCompany(
                        $productId,
                        $companyId
                    );

            if ($product === null) {
                throw new Exception(
                    'Selected product was not found.'
                );
            }

            if (
                $this->itemModel
                    ->productExists(
                        $adjustmentId,
                        $companyId,
                        $productId
                    )
            ) {
                throw new Exception(
                    'This product is already included in the adjustment.'
                );
            }

            $stockLevel =
                $this->stockLevelModel
                    ->lockForUpdate(
                        $companyId,
                        $productId,
                        (int) $adjustment[
                            'warehouse_id'
                        ]
                    );

            if ($stockLevel === null) {
                throw new Exception(
                    'Stock level was not found for the selected product.'
                );
            }

            $currentQuantity = round(
                (float) $stockLevel[
                    'quantity'
                ],
                3
            );

            if (
                $direction === 'decrease' &&
                $quantity >
                    $currentQuantity + 0.0005
            ) {
                throw new Exception(
                    'Decrease quantity cannot exceed the current stock of ' .
                    number_format(
                        $currentQuantity,
                        3,
                        '.',
                        ''
                    ) .
                    ' ' .
                    (string) $product['unit'] .
                    '.'
                );
            }

            $itemId =
                $this->itemModel
                    ->create([
                        'inventory_adjustment_id' =>
                            $adjustmentId,

                        'company_id' =>
                            $companyId,

                        'product_id' =>
                            $productId,

                        'product_name' =>
                            (string) $product[
                                'name'
                            ],

                        'product_internal_code' =>
                            (string) $product[
                                'internal_code'
                            ],

                        'product_barcode' =>
                            $this->nullableString(
                                (string) (
                                    $product[
                                        'barcode'
                                    ] ?? ''
                                )
                            ),

                        'product_unit' =>
                            (string) $product[
                                'unit'
                            ],

                        'direction' =>
                            $direction,

                        'quantity' =>
                            $quantity,

                        'unit_cost' =>
                            $unitCost,

                        'total_cost' =>
                            $totalCost,

                        'stock_quantity_at_add' =>
                            $currentQuantity,

                        'item_note' =>
                            $this->nullableString(
                                $itemNote
                            ),
                    ]);

            $auditMessage =
                'Added product ' .
                (string) $product['name'] .
                ' to inventory adjustment ' .
                (string) $adjustment[
                    'adjustment_number'
                ] .
                '. Direction: ' .
                $direction .
                ', quantity: ' .
                number_format(
                    $quantity,
                    3,
                    '.',
                    ''
                );

            if (
                $direction === 'increase' &&
                $unitCost !== null
            ) {
                $auditMessage .=
                    ', unit cost: ' .
                    number_format(
                        $unitCost,
                        4,
                        '.',
                        ''
                    );
            }

            $auditMessage .= '.';

            $this->auditLogService->log(
                $companyId,
                $userId,
                'update',
                'inventory_adjustment',
                $adjustmentId,
                $auditMessage
            );

            $this->db->commit();

            return [
                'success' => true,
                'item_id' => $itemId,
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

    public function deleteItem(
        int $adjustmentId,
        int $itemId,
        int $companyId,
        int $userId
    ): array {
        try {
            $this->db->beginTransaction();

            $adjustment =
                $this->adjustmentModel
                    ->findForUpdate(
                        $adjustmentId,
                        $companyId
                    );

            if ($adjustment === null) {
                throw new Exception(
                    'Inventory adjustment was not found.'
                );
            }

            if (
                (string) $adjustment[
                    'status'
                ] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft adjustments can be edited.'
                );
            }

            $item =
                $this->itemModel
                    ->findByIdAndAdjustment(
                        $itemId,
                        $adjustmentId,
                        $companyId
                    );

            if ($item === null) {
                throw new Exception(
                    'Adjustment item was not found.'
                );
            }

            $deleted =
                $this->itemModel
                    ->deleteDraftItem(
                        $itemId,
                        $adjustmentId,
                        $companyId
                    );

            if (!$deleted) {
                throw new Exception(
                    'Adjustment item could not be removed.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'update',
                'inventory_adjustment',
                $adjustmentId,
                'Removed product ' .
                (string) $item[
                    'product_name'
                ] .
                ' from inventory adjustment ' .
                (string) $adjustment[
                    'adjustment_number'
                ] .
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
        int $adjustmentId,
        int $companyId,
        int $userId
    ): array {
        try {
            $this->db->beginTransaction();

            $adjustment =
                $this->adjustmentModel
                    ->findForUpdate(
                        $adjustmentId,
                        $companyId
                    );

            if ($adjustment === null) {
                throw new Exception(
                    'Inventory adjustment was not found.'
                );
            }

            if (
                (string) $adjustment[
                    'status'
                ] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft adjustments can be completed.'
                );
            }

            $items =
                $this->itemModel
                    ->allForUpdate(
                        $adjustmentId,
                        $companyId
                    );

            if (empty($items)) {
                throw new Exception(
                    'Add at least one product before completing the adjustment.'
                );
            }

            $warehouseId =
                (int) $adjustment[
                    'warehouse_id'
                ];

            $increaseItems = 0;
            $decreaseItems = 0;

            foreach ($items as $item) {
                $productId =
                    (int) $item[
                        'product_id'
                    ];

                $quantity = round(
                    (float) $item[
                        'quantity'
                    ],
                    3
                );

                $direction =
                    (string) $item[
                        'direction'
                    ];

                $fromWarehouseId = null;
                $toWarehouseId = null;

                if ($direction === 'increase') {
                    if (
                        $item['unit_cost'] === null
                    ) {
                        throw new Exception(
                            'Unit cost is missing for stock increase product: ' .
                            (string) $item[
                                'product_name'
                            ] .
                            '.'
                        );
                    }

                    $costMovement =
                        $this->inventoryCostService
                            ->receive(
                                $companyId,
                                $productId,
                                $warehouseId,
                                $quantity,
                                (float) $item[
                                    'unit_cost'
                                ]
                            );

                    $transactionCostFields =
                        $this->inventoryCostService
                            ->incomingTransactionFields(
                                $costMovement
                            );

                    $toWarehouseId =
                        $warehouseId;

                    $increaseItems++;
                } elseif (
                    $direction === 'decrease'
                ) {
                    $costMovement =
                        $this->inventoryCostService
                            ->issue(
                                $companyId,
                                $productId,
                                $warehouseId,
                                $quantity
                            );

                    $transactionCostFields =
                        $this->inventoryCostService
                            ->outgoingTransactionFields(
                                $costMovement
                            );

                    $fromWarehouseId =
                        $warehouseId;

                    $decreaseItems++;
                } else {
                    throw new Exception(
                        'Invalid direction for product: ' .
                        (string) $item[
                            'product_name'
                        ] .
                        '.'
                    );
                }

                $quantityBefore = round(
                    (float) $costMovement[
                        'quantity_before'
                    ],
                    3
                );

                $quantityAfter = round(
                    (float) $costMovement[
                        'quantity_after'
                    ],
                    3
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

                $itemApplied =
                    $this->itemModel
                        ->markCostApplied(
                            (int) $item['id'],
                            $companyId,
                            $quantityBefore,
                            $quantityAfter,
                            $unitCost,
                            $totalCost
                        );

                if (!$itemApplied) {
                    throw new Exception(
                        'Could not store resulting quantity and cost for product: ' .
                        (string) $item[
                            'product_name'
                        ] .
                        '.'
                    );
                }

                $transactionData = [
                    'company_id' =>
                        $companyId,

                    'product_id' =>
                        $productId,

                    'from_warehouse_id' =>
                        $fromWarehouseId,

                    'to_warehouse_id' =>
                        $toWarehouseId,

                    'user_id' =>
                        $userId,

                    'type' =>
                        'adjustment',

                    'quantity' =>
                        $quantity,

                    'reference_type' =>
                        'inventory_adjustment',

                    'reference_id' =>
                        $adjustmentId,

                    'note' =>
                        $this->transactionNote(
                            $adjustment,
                            $item,
                            $quantityBefore,
                            $quantityAfter,
                            $unitCost,
                            $totalCost
                        ),
                ];

                $transactionData =
                    array_merge(
                        $transactionData,
                        $transactionCostFields
                    );

                $transactionCreated =
                    $this->transactionModel
                        ->create(
                            $transactionData
                        );

                if (!$transactionCreated) {
                    throw new Exception(
                        'Warehouse transaction could not be created for product: ' .
                        (string) $item[
                            'product_name'
                        ] .
                        '.'
                    );
                }
            }

            $completed =
                $this->adjustmentModel
                    ->markCompleted(
                        $adjustmentId,
                        $companyId,
                        $userId
                    );

            if (!$completed) {
                throw new Exception(
                    'Inventory adjustment could not be completed.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'complete',
                'inventory_adjustment',
                $adjustmentId,
                'Completed inventory adjustment ' .
                (string) $adjustment[
                    'adjustment_number'
                ] .
                '. Products: ' .
                count($items) .
                ', increases: ' .
                $increaseItems .
                ', decreases: ' .
                $decreaseItems .
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
        int $adjustmentId,
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

            $adjustment =
                $this->adjustmentModel
                    ->findForUpdate(
                        $adjustmentId,
                        $companyId
                    );

            if ($adjustment === null) {
                throw new Exception(
                    'Inventory adjustment was not found.'
                );
            }

            if (
                (string) $adjustment[
                    'status'
                ] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft adjustments can be cancelled.'
                );
            }

            $cancelled =
                $this->adjustmentModel
                    ->markCancelled(
                        $adjustmentId,
                        $companyId,
                        $userId,
                        $reason
                    );

            if (!$cancelled) {
                throw new Exception(
                    'Inventory adjustment could not be cancelled.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'cancel',
                'inventory_adjustment',
                $adjustmentId,
                'Cancelled inventory adjustment ' .
                (string) $adjustment[
                    'adjustment_number'
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

    private function parseQuantity(
        string $value
    ): float {
        $value = trim($value);

        $value = str_replace(
            [
                ' ',
                ',',
            ],
            [
                '',
                '.',
            ],
            $value
        );

        if (
            preg_match(
                '/^\d{1,11}(?:\.\d{1,3})?$/',
                $value
            ) !== 1
        ) {
            throw new Exception(
                'Quantity must be a positive number with maximum 3 decimal places.'
            );
        }

        $quantity = round(
            (float) $value,
            3
        );

        if ($quantity <= 0) {
            throw new Exception(
                'Quantity must be greater than zero.'
            );
        }

        return $quantity;
    }

    private function parseCost(
        string $value
    ): float {
        $value = trim($value);

        if ($value === '') {
            throw new Exception(
                'Unit cost is required for stock increases.'
            );
        }

        $value = str_replace(
            [
                ' ',
                ',',
            ],
            [
                '',
                '.',
            ],
            $value
        );

        if (
            preg_match(
                '/^\d{1,10}(?:\.\d{1,4})?$/',
                $value
            ) !== 1
        ) {
            throw new Exception(
                'Unit cost must be a non-negative number with maximum 4 decimal places.'
            );
        }

        $unitCost = round(
            (float) $value,
            4
        );

        if ($unitCost < 0) {
            throw new Exception(
                'Unit cost cannot be negative.'
            );
        }

        return $unitCost;
    }

    private function adjustmentNumber(
        int $adjustmentId
    ): string {
        return 'IA-' .
            str_pad(
                (string) $adjustmentId,
                8,
                '0',
                STR_PAD_LEFT
            );
    }

    private function transactionNote(
        array $adjustment,
        array $item,
        float $quantityBefore,
        float $quantityAfter,
        float $unitCost,
        float $totalCost
    ): string {
        $note =
            'Inventory adjustment ' .
            (string) $adjustment[
                'adjustment_number'
            ] .
            '. Reason: ' .
            (string) $adjustment[
                'reason_description'
            ] .
            '. Before: ' .
            number_format(
                $quantityBefore,
                3,
                '.',
                ''
            ) .
            ', after: ' .
            number_format(
                $quantityAfter,
                3,
                '.',
                ''
            ) .
            ', unit cost: ' .
            number_format(
                $unitCost,
                4,
                '.',
                ''
            ) .
            ', total cost: ' .
            number_format(
                $totalCost,
                4,
                '.',
                ''
            ) .
            '.';

        if (
            isset($item['item_note']) &&
            trim(
                (string) $item[
                    'item_note'
                ]
            ) !== ''
        ) {
            $note .=
                ' Item note: ' .
                trim(
                    (string) $item[
                        'item_note'
                    ]
                ) .
                '.';
        }

        return $note;
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