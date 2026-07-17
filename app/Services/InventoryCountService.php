<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Models\WarehouseTransaction;
use DateTimeImmutable;
use Exception;
use PDO;
use Throwable;

class InventoryCountService
{
    private PDO $db;

    private InventoryCount
        $inventoryCountModel;

    private InventoryCountItem
        $inventoryCountItemModel;

    private Warehouse $warehouseModel;

    private StockLevel $stockLevelModel;

    private WarehouseTransaction
        $warehouseTransactionModel;

    private AuditLogService
        $auditLogService;

    public function __construct()
    {
        $this->db =
            Database::getConnection();

        $this->inventoryCountModel =
            new InventoryCount();

        $this->inventoryCountItemModel =
            new InventoryCountItem();

        $this->warehouseModel =
            new Warehouse();

        $this->stockLevelModel =
            new StockLevel();

        $this->warehouseTransactionModel =
            new WarehouseTransaction();

        $this->auditLogService =
            new AuditLogService();
    }

    public function createDraft(
        int $companyId,
        int $warehouseId,
        int $userId,
        string $countDate,
        string $notes
    ): array {
        $notes = trim($notes);

        if (!$this->validDate($countDate)) {
            return [
                'success' => false,
                'inventory_count_id' => null,
                'error' =>
                'Inventory count date is invalid.',
            ];
        }

        if ($countDate > date('Y-m-d')) {
            return [
                'success' => false,
                'inventory_count_id' => null,
                'error' =>
                'Inventory count date cannot be in the future.',
            ];
        }

        if (mb_strlen($notes) > 2000) {
            return [
                'success' => false,
                'inventory_count_id' => null,
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
            (int) $warehouse['is_active'] !== 1
        ) {
            return [
                'success' => false,
                'inventory_count_id' => null,
                'error' =>
                'Selected warehouse was not found or is inactive.',
            ];
        }

        try {
            $this->db->beginTransaction();

            if (
                $this->inventoryCountModel
                ->hasOpenForWarehouse(
                    $companyId,
                    $warehouseId
                )
            ) {
                throw new Exception(
                    'This warehouse already has an open inventory count.'
                );
            }

            $transactionBefore =
                $this->warehouseTransactionModel
                ->latestIdForWarehouse(
                    $companyId,
                    $warehouseId
                );

            $snapshotAt =
                date('Y-m-d H:i:s');

            $inventoryCountId =
                $this->inventoryCountModel
                ->create([
                    'company_id' =>
                    $companyId,

                    'warehouse_id' =>
                    $warehouseId,

                    'count_date' =>
                    $countDate,

                    'snapshot_transaction_id' =>
                    $transactionBefore,

                    'snapshot_at' =>
                    $snapshotAt,

                    'notes' =>
                    $this->nullableString(
                        $notes
                    ),

                    'created_by_user_id' =>
                    $userId,
                ]);

            $countNumber =
                $this->countNumber(
                    $inventoryCountId
                );

            $numberAssigned =
                $this->inventoryCountModel
                ->assignNumber(
                    $inventoryCountId,
                    $companyId,
                    $countNumber
                );

            if (!$numberAssigned) {
                throw new Exception(
                    'Inventory count number could not be assigned.'
                );
            }

            $itemCount =
                $this->inventoryCountItemModel
                ->createWarehouseSnapshot(
                    $inventoryCountId,
                    $companyId,
                    $warehouseId
                );

            if ($itemCount <= 0) {
                throw new Exception(
                    'The warehouse does not contain products that can be counted.'
                );
            }

            $transactionAfter =
                $this->warehouseTransactionModel
                ->latestIdForWarehouse(
                    $companyId,
                    $warehouseId
                );

            if (
                $transactionBefore !==
                $transactionAfter
            ) {
                throw new Exception(
                    'Stock changed while the inventory snapshot was being created. Try again.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'inventory_count',
                $inventoryCountId,
                'Created inventory count ' .
                    $countNumber .
                    ' for warehouse ' .
                    (string) $warehouse['name'] .
                    '. Products: ' .
                    $itemCount
            );

            $this->db->commit();

            return [
                'success' => true,

                'inventory_count_id' =>
                $inventoryCountId,

                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'inventory_count_id' => null,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function saveCounts(
        int $inventoryCountId,
        int $companyId,
        int $userId,
        array $submittedQuantities
    ): array {
        try {
            $this->db->beginTransaction();

            $inventoryCount =
                $this->inventoryCountModel
                ->findForUpdate(
                    $inventoryCountId,
                    $companyId
                );

            if ($inventoryCount === null) {
                throw new Exception(
                    'Inventory count was not found.'
                );
            }

            if (
                (string) $inventoryCount['status']
                !== 'draft'
            ) {
                throw new Exception(
                    'Only draft inventory counts can be edited.'
                );
            }

            $items =
                $this->inventoryCountItemModel
                ->allForUpdate(
                    $inventoryCountId,
                    $companyId
                );

            $this->applySubmittedQuantities(
                $inventoryCountId,
                $companyId,
                $items,
                $submittedQuantities,
                false
            );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'update',
                'inventory_count',
                $inventoryCountId,
                'Updated counted quantities for inventory count ' .
                    (string) $inventoryCount['count_number']
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
        int $inventoryCountId,
        int $companyId,
        int $userId,
        array $submittedQuantities
    ): array {
        try {
            $this->db->beginTransaction();

            $inventoryCount =
                $this->inventoryCountModel
                ->findForUpdate(
                    $inventoryCountId,
                    $companyId
                );

            if ($inventoryCount === null) {
                throw new Exception(
                    'Inventory count was not found.'
                );
            }

            if (
                (string) $inventoryCount['status']
                !== 'draft'
            ) {
                throw new Exception(
                    'Only draft inventory counts can be completed.'
                );
            }

            $warehouseId =
                (int) $inventoryCount['warehouse_id'];

            $stockChanged =
                $this->warehouseTransactionModel
                ->existsForWarehouseAfterId(
                    $companyId,
                    $warehouseId,
                    (int) $inventoryCount['snapshot_transaction_id']
                );

            if ($stockChanged) {
                throw new Exception(
                    'Stock movements were recorded after this inventory count started. Cancel it and create a new inventory count.'
                );
            }

            $items =
                $this->inventoryCountItemModel
                ->allForUpdate(
                    $inventoryCountId,
                    $companyId
                );

            $this->applySubmittedQuantities(
                $inventoryCountId,
                $companyId,
                $items,
                $submittedQuantities,
                true
            );

            $differenceItemCount = 0;
            $positiveAdjustments = 0;
            $negativeAdjustments = 0;

            foreach ($items as $item) {
                if (
                    $item['counted_quantity'] ===
                    null
                ) {
                    throw new Exception(
                        'All products must have a counted quantity before completion.'
                    );
                }

                $productId =
                    (int) $item['product_id'];

                $systemQuantity = round(
                    (float) $item['system_quantity'],
                    3
                );

                $countedQuantity = round(
                    (float) $item['counted_quantity'],
                    3
                );

                $stockLevel =
                    $this->stockLevelModel
                    ->lockForUpdate(
                        $companyId,
                        $productId,
                        $warehouseId
                    );

                $currentQuantity = round(
                    (float) $stockLevel['quantity'],
                    3
                );

                if (
                    abs(
                        $currentQuantity -
                            $systemQuantity
                    ) > 0.0005
                ) {
                    throw new Exception(
                        'Current stock no longer matches the snapshot for product: ' .
                            (string) $item['product_name'] .
                            '. Cancel the count and create a new one.'
                    );
                }

                $difference = round(
                    $countedQuantity -
                        $systemQuantity,
                    3
                );

                if (
                    abs($difference) <=
                    0.0005
                ) {
                    continue;
                }

                $differenceItemCount++;

                $absoluteDifference =
                    abs($difference);

                $fromWarehouseId = null;
                $toWarehouseId = null;

                if ($difference > 0) {
                    $increased =
                        $this->stockLevelModel
                        ->increase(
                            $companyId,
                            $productId,
                            $warehouseId,
                            $absoluteDifference
                        );

                    if (!$increased) {
                        throw new Exception(
                            'Could not increase stock for product: ' .
                                (string) $item['product_name']
                        );
                    }

                    $toWarehouseId =
                        $warehouseId;

                    $positiveAdjustments++;
                } else {
                    $decreased =
                        $this->stockLevelModel
                        ->decrease(
                            $companyId,
                            $productId,
                            $warehouseId,
                            $absoluteDifference
                        );

                    if (!$decreased) {
                        throw new Exception(
                            'Could not decrease stock for product: ' .
                                (string) $item['product_name']
                        );
                    }

                    $fromWarehouseId =
                        $warehouseId;

                    $negativeAdjustments++;
                }

                $transactionCreated =
                    $this->warehouseTransactionModel
                    ->create([
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
                        $absoluteDifference,

                        'reference_type' =>
                        'inventory_count',

                        'reference_id' =>
                        $inventoryCountId,

                        'note' =>
                        'Inventory count ' .
                            (string) $inventoryCount['count_number'] .
                            '. System: ' .
                            number_format(
                                $systemQuantity,
                                3,
                                '.',
                                ''
                            ) .
                            ', counted: ' .
                            number_format(
                                $countedQuantity,
                                3,
                                '.',
                                ''
                            ) .
                            '.',
                    ]);

                if (!$transactionCreated) {
                    throw new Exception(
                        'Warehouse adjustment transaction could not be created.'
                    );
                }
            }

            $completed =
                $this->inventoryCountModel
                ->markCompleted(
                    $inventoryCountId,
                    $companyId,
                    $userId
                );

            if (!$completed) {
                throw new Exception(
                    'Inventory count could not be completed.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'complete',
                'inventory_count',
                $inventoryCountId,
                'Completed inventory count ' .
                    (string) $inventoryCount['count_number'] .
                    '. Adjusted products: ' .
                    $differenceItemCount .
                    ', increases: ' .
                    $positiveAdjustments .
                    ', decreases: ' .
                    $negativeAdjustments .
                    '.'
            );

            $this->db->commit();

            return [
                'success' => true,

                'difference_item_count' =>
                $differenceItemCount,

                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,

                'difference_item_count' =>
                0,

                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function cancel(
        int $inventoryCountId,
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

            $inventoryCount =
                $this->inventoryCountModel
                ->findForUpdate(
                    $inventoryCountId,
                    $companyId
                );

            if ($inventoryCount === null) {
                throw new Exception(
                    'Inventory count was not found.'
                );
            }

            if (
                (string) $inventoryCount['status']
                !== 'draft'
            ) {
                throw new Exception(
                    'Only draft inventory counts can be cancelled.'
                );
            }

            $cancelled =
                $this->inventoryCountModel
                ->markCancelled(
                    $inventoryCountId,
                    $companyId,
                    $userId,
                    $reason
                );

            if (!$cancelled) {
                throw new Exception(
                    'Inventory count could not be cancelled.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'cancel',
                'inventory_count',
                $inventoryCountId,
                'Cancelled inventory count ' .
                    (string) $inventoryCount['count_number'] .
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

    public function stockChangedSinceSnapshot(
        array $inventoryCount
    ): bool {
        if (
            (string) $inventoryCount['status']
            !== 'draft'
        ) {
            return false;
        }

        return $this->warehouseTransactionModel
            ->existsForWarehouseAfterId(
                (int) $inventoryCount['company_id'],

                (int) $inventoryCount['warehouse_id'],

                (int) $inventoryCount['snapshot_transaction_id']
            );
    }

    private function applySubmittedQuantities(
        int $inventoryCountId,
        int $companyId,
        array &$items,
        array $submittedQuantities,
        bool $requireAll
    ): void {
        foreach ($items as &$item) {
            $itemId = (int) $item['id'];

            if (
                array_key_exists(
                    $itemId,
                    $submittedQuantities
                )
            ) {
                $rawValue =
                    $submittedQuantities[$itemId];

                if (!is_scalar($rawValue)) {
                    throw new Exception(
                        'Invalid counted quantity for product: ' .
                            (string) $item['product_name']
                    );
                }

                $countedQuantity =
                    $this->parseQuantity(
                        (string) $rawValue
                    );

                $updated =
                    $this->inventoryCountItemModel
                    ->updateCountedQuantity(
                        $itemId,
                        $inventoryCountId,
                        $companyId,
                        $countedQuantity
                    );

                if (!$updated) {
                    throw new Exception(
                        'Could not save counted quantity for product: ' .
                            (string) $item['product_name']
                    );
                }

                $item['counted_quantity'] =
                    $countedQuantity;
            }

            if (
                $requireAll &&
                $item['counted_quantity'] ===
                null
            ) {
                throw new Exception(
                    'Enter a counted quantity for every product before completion.'
                );
            }
        }

        unset($item);
    }

    private function parseQuantity(
        string $value
    ): ?float {
        $value = trim($value);

        if ($value === '') {
            return null;
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
                '/^\d{1,11}(?:\.\d{1,3})?$/',
                $value
            ) !== 1
        ) {
            throw new Exception(
                'Counted quantities must be positive numbers with maximum 3 decimal places.'
            );
        }

        $quantity = round(
            (float) $value,
            3
        );

        if ($quantity < 0) {
            throw new Exception(
                'Counted quantities cannot be negative.'
            );
        }

        return $quantity;
    }

    private function countNumber(
        int $inventoryCountId
    ): string {
        return 'IC-' .
            str_pad(
                (string) $inventoryCountId,
                8,
                '0',
                STR_PAD_LEFT
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
