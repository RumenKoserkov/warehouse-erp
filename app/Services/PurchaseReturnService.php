<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\WarehouseTransaction;
use DateTimeImmutable;
use Exception;
use PDO;
use Throwable;

class PurchaseReturnService
{
    private PDO $db;

    private Purchase $purchaseModel;

    private PurchaseReturn $purchaseReturnModel;

    private PurchaseReturnItem $itemModel;

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

        $this->purchaseModel =
            new Purchase();

        $this->purchaseReturnModel =
            new PurchaseReturn();

        $this->itemModel =
            new PurchaseReturnItem();

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
            'damaged_goods' =>
            'Damaged Goods',

            'wrong_goods' =>
            'Wrong Goods Delivered',

            'quality_issue' =>
            'Quality Issue',

            'excess_delivery' =>
            'Excess Delivery',

            'expired_goods' =>
            'Expired Goods',

            'other' =>
            'Other',
        ];
    }

    public function createDraft(
        int $purchaseId,
        int $companyId,
        int $userId,
        string $returnDate,
        string $reasonType,
        string $reasonDescription,
        string $notes,
        array $returnQuantities,
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

                'purchase_return_id' =>
                null,

                'error' =>
                $validationError,
            ];
        }

        try {
            $this->db->beginTransaction();

            $purchase =
                $this->purchaseModel
                ->findForUpdate(
                    $purchaseId,
                    $companyId
                );

            if ($purchase === null) {
                throw new Exception(
                    'Purchase was not found.'
                );
            }

            if (
                (string) $purchase['status'] !== 'completed'
            ) {
                throw new Exception(
                    'Only completed purchases can be returned.'
                );
            }

            if (
                $returnDate <
                (string) $purchase['purchase_date']
            ) {
                throw new Exception(
                    'Return date cannot be before the purchase date.'
                );
            }

            if (
                $this->purchaseReturnModel
                ->hasDraftForPurchase(
                    $purchaseId,
                    $companyId
                )
            ) {
                throw new Exception(
                    'This purchase already has an open return draft.'
                );
            }

            $preparedItems =
                $this->prepareItems(
                    $purchaseId,
                    $companyId,
                    $returnQuantities,
                    $itemNotes
                );

            $purchaseReturnId =
                $this->purchaseReturnModel
                ->create([
                    'company_id' =>
                    $companyId,

                    'purchase_id' =>
                    $purchaseId,

                    'warehouse_id' =>
                    (int) $purchase['warehouse_id'],

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
                    $purchaseReturnId
                );

            $numberAssigned =
                $this->purchaseReturnModel
                ->assignNumber(
                    $purchaseReturnId,
                    $companyId,
                    $returnNumber
                );

            if (!$numberAssigned) {
                throw new Exception(
                    'Purchase return number could not be assigned.'
                );
            }

            $this->storeItems(
                $purchaseReturnId,
                $companyId,
                $preparedItems
            );

            $this->purchaseReturnModel
                ->updateTotals(
                    $purchaseReturnId,
                    $companyId,
                    $this->calculateTotals(
                        $preparedItems
                    )
                );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'purchase_return',
                $purchaseReturnId,
                'Created purchase return ' .
                    $returnNumber .
                    ' for purchase ' .
                    (string) $purchase['purchase_number'] .
                    '. Items: ' .
                    count($preparedItems) .
                    '.'
            );

            $this->db->commit();

            return [
                'success' => true,

                'purchase_return_id' =>
                $purchaseReturnId,

                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,

                'purchase_return_id' =>
                null,

                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function updateDraft(
        int $purchaseReturnId,
        int $companyId,
        int $userId,
        array $returnQuantities,
        array $itemNotes
    ): array {
        try {
            $this->db->beginTransaction();

            $purchaseReturn =
                $this->purchaseReturnModel
                ->findForUpdate(
                    $purchaseReturnId,
                    $companyId
                );

            if ($purchaseReturn === null) {
                throw new Exception(
                    'Purchase return was not found.'
                );
            }

            if (
                (string) $purchaseReturn['status'] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft purchase returns can be edited.'
                );
            }

            $purchase =
                $this->purchaseModel
                ->findForUpdate(
                    (int) $purchaseReturn['purchase_id'],
                    $companyId
                );

            if (
                $purchase === null ||
                (string) $purchase['status'] !== 'completed'
            ) {
                throw new Exception(
                    'The original purchase is no longer returnable.'
                );
            }

            $preparedItems =
                $this->prepareItems(
                    (int) $purchaseReturn['purchase_id'],
                    $companyId,
                    $returnQuantities,
                    $itemNotes
                );

            $this->itemModel
                ->deleteByReturn(
                    $purchaseReturnId,
                    $companyId
                );

            $this->storeItems(
                $purchaseReturnId,
                $companyId,
                $preparedItems
            );

            $this->purchaseReturnModel
                ->updateTotals(
                    $purchaseReturnId,
                    $companyId,
                    $this->calculateTotals(
                        $preparedItems
                    )
                );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'update',
                'purchase_return',
                $purchaseReturnId,
                'Updated purchase return ' .
                    (string) $purchaseReturn['return_number'] .
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
        int $purchaseReturnId,
        int $companyId,
        int $userId
    ): array {
        try {
            $this->db->beginTransaction();

            $purchaseReturn =
                $this->purchaseReturnModel
                ->findForUpdate(
                    $purchaseReturnId,
                    $companyId
                );

            if ($purchaseReturn === null) {
                throw new Exception(
                    'Purchase return was not found.'
                );
            }

            if (
                (string) $purchaseReturn['status'] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft purchase returns can be completed.'
                );
            }

            $purchase =
                $this->purchaseModel
                ->findForUpdate(
                    (int) $purchaseReturn['purchase_id'],
                    $companyId
                );

            if (
                $purchase === null ||
                (string) $purchase['status'] !== 'completed'
            ) {
                throw new Exception(
                    'The original purchase is no longer returnable.'
                );
            }

            $items =
                $this->itemModel
                ->allForUpdate(
                    $purchaseReturnId,
                    $companyId
                );

            if (empty($items)) {
                throw new Exception(
                    'Purchase return has no items.'
                );
            }

            $returnableRows =
                $this->itemModel
                ->returnableByPurchase(
                    (int) $purchaseReturn['purchase_id'],
                    $companyId
                );

            $returnableMap = [];

            foreach ($returnableRows as $row) {
                $returnableMap[(int) $row['id']] = $row;
            }

            $warehouseId =
                (int) $purchaseReturn['warehouse_id'];

            foreach ($items as $item) {
                $purchaseItemId =
                    (int) $item['purchase_item_id'];

                if (
                    !isset(
                        $returnableMap[$purchaseItemId]
                    )
                ) {
                    throw new Exception(
                        'Original purchase item was not found.'
                    );
                }

                $remainingQuantity = round(
                    (float) $returnableMap[$purchaseItemId]['remaining_quantity'],
                    3
                );

                $returnQuantity = round(
                    (float) $item['return_quantity'],
                    3
                );

                if (
                    $returnQuantity >
                    $remainingQuantity + 0.0005
                ) {
                    throw new Exception(
                        'Return quantity exceeds the remaining quantity for product: ' .
                            (string) $item['product_name'] .
                            '.'
                    );
                }

                $productId =
                    (int) $item['product_id'];

                /*
                 * Изваждаме върнатото количество
                 * по текущата среднопретеглена
                 * себестойност на склада.
                 */
                $costMovement =
                    $this->inventoryCostService
                    ->issue(
                        $companyId,
                        $productId,
                        $warehouseId,
                        $returnQuantity
                    );

                $quantityBefore = round(
                    (float) $costMovement['quantity_before'],
                    3
                );

                $quantityAfter = round(
                    (float) $costMovement['quantity_after'],
                    3
                );

                $inventoryUnitCost = round(
                    (float) $costMovement['unit_cost'],
                    4
                );

                $totalCost = round(
                    (float) $costMovement['total_cost'],
                    4
                );

                $marked =
                    $this->itemModel
                    ->markCostApplied(
                        (int) $item['id'],
                        $companyId,
                        $quantityBefore,
                        $quantityAfter,
                        $inventoryUnitCost,
                        $totalCost
                    );

                if (!$marked) {
                    throw new Exception(
                        'Could not save resulting stock and cost for product: ' .
                            (string) $item['product_name'] .
                            '.'
                    );
                }

                $transactionData = [
                    'company_id' =>
                    $companyId,

                    'product_id' =>
                    $productId,

                    'from_warehouse_id' =>
                    $warehouseId,

                    'to_warehouse_id' =>
                    null,

                    'user_id' =>
                    $userId,

                    'type' =>
                    'purchase_return',

                    'quantity' =>
                    $returnQuantity,

                    'reference_type' =>
                    'purchase_return',

                    'reference_id' =>
                    $purchaseReturnId,

                    'note' =>
                    'Purchase return ' .
                        (string) $purchaseReturn['return_number'] .
                        ' for purchase ' .
                        (string) $purchase['purchase_number'] .
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
                        ', inventory unit cost: ' .
                        number_format(
                            $inventoryUnitCost,
                            4,
                            '.',
                            ''
                        ) .
                        '.',
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
                    $this->transactionModel
                    ->create(
                        $transactionData
                    );

                if (!$transactionCreated) {
                    throw new Exception(
                        'Warehouse transaction could not be created for product: ' .
                            (string) $item['product_name'] .
                            '.'
                    );
                }
            }

            $completed =
                $this->purchaseReturnModel
                ->markCompleted(
                    $purchaseReturnId,
                    $companyId,
                    $userId
                );

            if (!$completed) {
                throw new Exception(
                    'Purchase return could not be completed.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'complete',
                'purchase_return',
                $purchaseReturnId,
                'Completed purchase return ' .
                    (string) $purchaseReturn['return_number'] .
                    ' for purchase ' .
                    (string) $purchase['purchase_number'] .
                    '. Items: ' .
                    count($items) .
                    ', return total: ' .
                    number_format(
                        (float) $purchaseReturn['total_amount'],
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
        int $purchaseReturnId,
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

            $purchaseReturn =
                $this->purchaseReturnModel
                ->findForUpdate(
                    $purchaseReturnId,
                    $companyId
                );

            if ($purchaseReturn === null) {
                throw new Exception(
                    'Purchase return was not found.'
                );
            }

            if (
                (string) $purchaseReturn['status'] !== 'draft'
            ) {
                throw new Exception(
                    'Only draft purchase returns can be cancelled.'
                );
            }

            $cancelled =
                $this->purchaseReturnModel
                ->markCancelled(
                    $purchaseReturnId,
                    $companyId,
                    $userId,
                    $reason
                );

            if (!$cancelled) {
                throw new Exception(
                    'Purchase return could not be cancelled.'
                );
            }

            $this->auditLogService->log(
                $companyId,
                $userId,
                'cancel',
                'purchase_return',
                $purchaseReturnId,
                'Cancelled purchase return ' .
                    (string) $purchaseReturn['return_number'] .
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

    public function summaryForPurchase(
        int $purchaseId,
        int $companyId
    ): array {
        $summary =
            $this->itemModel
            ->completedSummaryByPurchase(
                $purchaseId,
                $companyId
            );

        $items =
            $this->itemModel
            ->returnableByPurchase(
                $purchaseId,
                $companyId
            );

        $hasReturnableItems = false;
        $remainingQuantity = 0.0;

        foreach ($items as $item) {
            $remaining = max(
                0,
                (float) $item['remaining_quantity']
            );

            $remainingQuantity +=
                $remaining;

            if ($remaining > 0.0005) {
                $hasReturnableItems = true;
            }
        }

        return array_merge(
            $summary,
            [
                'has_returnable_items' =>
                $hasReturnableItems,

                'remaining_quantity' =>
                round(
                    $remainingQuantity,
                    3
                ),

                'has_draft' =>
                $this->purchaseReturnModel
                    ->hasDraftForPurchase(
                        $purchaseId,
                        $companyId
                    ),
            ]
        );
    }

    private function prepareItems(
        int $purchaseId,
        int $companyId,
        array $returnQuantities,
        array $itemNotes
    ): array {
        $purchaseItems =
            $this->itemModel
            ->returnableByPurchase(
                $purchaseId,
                $companyId
            );

        $preparedItems = [];

        foreach ($purchaseItems as $item) {
            $purchaseItemId =
                (int) $item['id'];

            $quantityInput =
                $this->arrayScalar(
                    $returnQuantities,
                    $purchaseItemId
                );

            if (
                $quantityInput === '' ||
                $this->numericZero(
                    $quantityInput
                )
            ) {
                continue;
            }

            $returnQuantity =
                $this->parseQuantity(
                    $quantityInput
                );

            $remainingQuantity = round(
                (float) $item['remaining_quantity'],
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
                        (string) $item['product_name'] .
                        '.'
                );
            }

            $itemNote =
                $this->arrayScalar(
                    $itemNotes,
                    $purchaseItemId
                );

            if (mb_strlen($itemNote) > 500) {
                throw new Exception(
                    'Item note must be maximum 500 characters for product: ' .
                        (string) $item['product_name'] .
                        '.'
                );
            }

            $amounts =
                $this->calculateAmounts(
                    $item,
                    $returnQuantity
                );

            $preparedItems[] = [
                'purchase_item_id' =>
                $purchaseItemId,

                'product_id' =>
                (int) $item['product_id'],

                'product_name' =>
                (string) $item['product_name'],

                'product_internal_code' =>
                (string) $item['product_internal_code'],

                'product_unit' =>
                (string) $item['unit'],

                'purchased_quantity' =>
                round(
                    (float) $item['quantity'],
                    3
                ),

                'return_quantity' =>
                $returnQuantity,

                /*
                 * Документната покупна цена.
                 * Не се заменя със складовата цена.
                 */
                'unit_cost' =>
                (float) $item['unit_cost'],

                'subtotal_amount' =>
                $amounts['subtotal_amount'],

                'discount_amount' =>
                $amounts['discount_amount'],

                'net_amount' =>
                $amounts['net_amount'],

                'vat_rate' =>
                (float) (
                    $item['vat_rate'] ??
                    0
                ),

                'tax_amount' =>
                $amounts['tax_amount'],

                'total_amount' =>
                $amounts['total_amount'],

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

    private function calculateAmounts(
        array $item,
        float $returnQuantity
    ): array {
        $purchasedQuantity = round(
            (float) $item['quantity'],
            3
        );

        if ($purchasedQuantity <= 0) {
            throw new Exception(
                'Original purchase quantity is invalid.'
            );
        }

        $remainingQuantity = round(
            (float) $item['remaining_quantity'],
            3
        );

        $originalAmounts = [
            'subtotal_amount' =>
            round(
                $purchasedQuantity *
                    (float) $item['unit_cost'],
                2
            ),

            'discount_amount' =>
            round(
                (float) $item['discount_amount'],
                2
            ),

            'net_amount' =>
            round(
                (float) (
                    $item['net_amount'] ??
                    $item['total_price']
                ),
                2
            ),

            'tax_amount' =>
            round(
                (float) (
                    $item['tax_amount'] ?? 0
                ),
                2
            ),

            'total_amount' =>
            round(
                (float) $item['total_price'],
                2
            ),
        ];

        $alreadyReturned = [
            'subtotal_amount' =>
            round(
                (float) $item['returned_subtotal'],
                2
            ),

            'discount_amount' =>
            round(
                (float) $item['returned_discount'],
                2
            ),

            'net_amount' =>
            round(
                (float) $item['returned_net'],
                2
            ),

            'tax_amount' =>
            round(
                (float) $item['returned_tax'],
                2
            ),

            'total_amount' =>
            round(
                (float) $item['returned_total'],
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
                            $alreadyReturned[$field]
                    ),
                    2
                );

                continue;
            }

            $ratio =
                $returnQuantity /
                $purchasedQuantity;

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

    private function storeItems(
        int $purchaseReturnId,
        int $companyId,
        array $items
    ): void {
        foreach ($items as $item) {
            $this->itemModel
                ->create(
                    array_merge(
                        $item,
                        [
                            'purchase_return_id' =>
                            $purchaseReturnId,

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
                array_keys($totals) as $field
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

        if (
            trim($reasonDescription) === ''
        ) {
            return 'Reason description is required.';
        }

        if (
            mb_strlen(
                trim($reasonDescription)
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
        string $value
    ): float {
        $value = str_replace(
            [' ', ','],
            ['', '.'],
            trim($value)
        );

        if (
            preg_match(
                '/^\d{1,11}(?:\.\d{1,3})?$/',
                $value
            ) !== 1
        ) {
            throw new Exception(
                'Quantities must be positive numbers with maximum 3 decimal places.'
            );
        }

        $quantity = round(
            (float) $value,
            3
        );

        if ($quantity <= 0) {
            throw new Exception(
                'Return quantity must be greater than zero.'
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
            abs(
                (float) $value
            ) <= 0.0005;
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
            !is_scalar(
                $values[$key]
            )
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

        return $date !== false &&
            $date->format('Y-m-d') ===
            $value;
    }

    private function returnNumber(
        int $id
    ): string {
        return 'PR-' .
            str_pad(
                (string) $id,
                8,
                '0',
                STR_PAD_LEFT
            );
    }

    private function nullableString(
        string $value
    ): ?string {
        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}