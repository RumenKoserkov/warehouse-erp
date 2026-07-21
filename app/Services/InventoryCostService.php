<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\StockLevel;
use Exception;
use LogicException;
use PDO;

class InventoryCostService
{
    private PDO $db;

    private StockLevel $stockLevelModel;

    public function __construct()
    {
        $this->db =
            Database::getConnection();

        $this->stockLevelModel =
            new StockLevel();
    }

    public function receive(
        int $companyId,
        int $productId,
        int $warehouseId,
        float $quantity,
        float $unitCost
    ): array {
        $this->requireTransaction();

        $quantity =
            $this->normalizeQuantity(
                $quantity
            );

        $unitCost =
            $this->normalizeCost(
                $unitCost
            );

        $stockLevel =
            $this->stockLevelModel
                ->lockForUpdate(
                    $companyId,
                    $productId,
                    $warehouseId
                );

        $before =
            $this->normalizeState(
                $stockLevel
            );

        $incomingValue = round(
            $quantity * $unitCost,
            4
        );

        $quantityAfter = round(
            $before['quantity'] +
            $quantity,
            3
        );

        $inventoryValueAfter = round(
            $before['inventory_value'] +
            $incomingValue,
            4
        );

        $averageCostAfter =
            $quantityAfter > 0
                ? round(
                    $inventoryValueAfter /
                    $quantityAfter,
                    4
                )
                : $unitCost;

        $this->saveState(
            $companyId,
            $productId,
            $warehouseId,
            $quantityAfter,
            $averageCostAfter,
            $inventoryValueAfter
        );

        return [
            'direction' =>
                'incoming',

            'quantity' =>
                $quantity,

            'unit_cost' =>
                $unitCost,

            'total_cost' =>
                $incomingValue,

            'quantity_before' =>
                $before['quantity'],

            'quantity_after' =>
                $quantityAfter,

            'average_cost_before' =>
                $before[
                    'average_unit_cost'
                ],

            'average_cost_after' =>
                $averageCostAfter,

            'inventory_value_before' =>
                $before[
                    'inventory_value'
                ],

            'inventory_value_after' =>
                $inventoryValueAfter,
        ];
    }

    public function issue(
        int $companyId,
        int $productId,
        int $warehouseId,
        float $quantity
    ): array {
        $this->requireTransaction();

        $quantity =
            $this->normalizeQuantity(
                $quantity
            );

        $stockLevel =
            $this->stockLevelModel
                ->lockForUpdate(
                    $companyId,
                    $productId,
                    $warehouseId
                );

        $before =
            $this->normalizeState(
                $stockLevel
            );

        if (
            $quantity >
            $before['quantity'] + 0.0005
        ) {
            throw new Exception(
                'Insufficient stock. Available: ' .
                number_format(
                    $before['quantity'],
                    3,
                    '.',
                    ''
                ) .
                '.'
            );
        }

        $unitCost =
            $before['quantity'] > 0
                ? round(
                    $before[
                        'inventory_value'
                    ] /
                    $before['quantity'],
                    4
                )
                : $before[
                    'average_unit_cost'
                ];

        $quantityAfter = round(
            $before['quantity'] -
            $quantity,
            3
        );

        $fullIssue =
            abs(
                $quantity -
                $before['quantity']
            ) <= 0.0005;

        $totalCost =
            $fullIssue
                ? $before[
                    'inventory_value'
                ]
                : round(
                    $quantity *
                    $unitCost,
                    4
                );

        $inventoryValueAfter =
            $fullIssue
                ? 0.0
                : round(
                    max(
                        0,
                        $before[
                            'inventory_value'
                        ] -
                        $totalCost
                    ),
                    4
                );

        /*
         * При нулева наличност пазим
         * последната средна цена.
         */
        $averageCostAfter =
            $quantityAfter > 0
                ? round(
                    $inventoryValueAfter /
                    $quantityAfter,
                    4
                )
                : $unitCost;

        $this->saveState(
            $companyId,
            $productId,
            $warehouseId,
            $quantityAfter,
            $averageCostAfter,
            $inventoryValueAfter
        );

        return [
            'direction' =>
                'outgoing',

            'quantity' =>
                $quantity,

            'unit_cost' =>
                $unitCost,

            'total_cost' =>
                round($totalCost, 4),

            'quantity_before' =>
                $before['quantity'],

            'quantity_after' =>
                $quantityAfter,

            'average_cost_before' =>
                $before[
                    'average_unit_cost'
                ],

            'average_cost_after' =>
                $averageCostAfter,

            'inventory_value_before' =>
                $before[
                    'inventory_value'
                ],

            'inventory_value_after' =>
                $inventoryValueAfter,
        ];
    }

    public function transfer(
        int $companyId,
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity
    ): array {
        $this->requireTransaction();

        if (
            $fromWarehouseId ===
            $toWarehouseId
        ) {
            throw new Exception(
                'Source and destination warehouses must be different.'
            );
        }

        $outgoing =
            $this->issue(
                $companyId,
                $productId,
                $fromWarehouseId,
                $quantity
            );

        $incoming =
            $this->receive(
                $companyId,
                $productId,
                $toWarehouseId,
                $quantity,
                (float) $outgoing[
                    'unit_cost'
                ]
            );

        return [
            'direction' =>
                'transfer',

            'quantity' =>
                $outgoing['quantity'],

            'unit_cost' =>
                $outgoing['unit_cost'],

            'total_cost' =>
                $outgoing['total_cost'],

            'outgoing' =>
                $outgoing,

            'incoming' =>
                $incoming,
        ];
    }

    public function incomingTransactionFields(
        array $movement
    ): array {
        return [
            'cost_method' =>
                'weighted_average',

            'unit_cost' =>
                $movement['unit_cost'],

            'total_cost' =>
                $movement['total_cost'],

            'from_quantity_before' =>
                null,

            'from_quantity_after' =>
                null,

            'to_quantity_before' =>
                $movement[
                    'quantity_before'
                ],

            'to_quantity_after' =>
                $movement[
                    'quantity_after'
                ],

            'from_average_cost_before' =>
                null,

            'from_average_cost_after' =>
                null,

            'to_average_cost_before' =>
                $movement[
                    'average_cost_before'
                ],

            'to_average_cost_after' =>
                $movement[
                    'average_cost_after'
                ],

            'from_inventory_value_before' =>
                null,

            'from_inventory_value_after' =>
                null,

            'to_inventory_value_before' =>
                $movement[
                    'inventory_value_before'
                ],

            'to_inventory_value_after' =>
                $movement[
                    'inventory_value_after'
                ],
        ];
    }

    public function outgoingTransactionFields(
        array $movement
    ): array {
        return [
            'cost_method' =>
                'weighted_average',

            'unit_cost' =>
                $movement['unit_cost'],

            'total_cost' =>
                $movement['total_cost'],

            'from_quantity_before' =>
                $movement[
                    'quantity_before'
                ],

            'from_quantity_after' =>
                $movement[
                    'quantity_after'
                ],

            'to_quantity_before' =>
                null,

            'to_quantity_after' =>
                null,

            'from_average_cost_before' =>
                $movement[
                    'average_cost_before'
                ],

            'from_average_cost_after' =>
                $movement[
                    'average_cost_after'
                ],

            'to_average_cost_before' =>
                null,

            'to_average_cost_after' =>
                null,

            'from_inventory_value_before' =>
                $movement[
                    'inventory_value_before'
                ],

            'from_inventory_value_after' =>
                $movement[
                    'inventory_value_after'
                ],

            'to_inventory_value_before' =>
                null,

            'to_inventory_value_after' =>
                null,
        ];
    }

    public function transferTransactionFields(
        array $movement
    ): array {
        $outgoing =
            $movement['outgoing'];

        $incoming =
            $movement['incoming'];

        return [
            'cost_method' =>
                'weighted_average',

            'unit_cost' =>
                $movement['unit_cost'],

            'total_cost' =>
                $movement['total_cost'],

            'from_quantity_before' =>
                $outgoing[
                    'quantity_before'
                ],

            'from_quantity_after' =>
                $outgoing[
                    'quantity_after'
                ],

            'to_quantity_before' =>
                $incoming[
                    'quantity_before'
                ],

            'to_quantity_after' =>
                $incoming[
                    'quantity_after'
                ],

            'from_average_cost_before' =>
                $outgoing[
                    'average_cost_before'
                ],

            'from_average_cost_after' =>
                $outgoing[
                    'average_cost_after'
                ],

            'to_average_cost_before' =>
                $incoming[
                    'average_cost_before'
                ],

            'to_average_cost_after' =>
                $incoming[
                    'average_cost_after'
                ],

            'from_inventory_value_before' =>
                $outgoing[
                    'inventory_value_before'
                ],

            'from_inventory_value_after' =>
                $outgoing[
                    'inventory_value_after'
                ],

            'to_inventory_value_before' =>
                $incoming[
                    'inventory_value_before'
                ],

            'to_inventory_value_after' =>
                $incoming[
                    'inventory_value_after'
                ],
        ];
    }

    private function normalizeState(
        array $stockLevel
    ): array {
        $quantity = round(
            (float) (
                $stockLevel[
                    'quantity'
                ] ?? 0
            ),
            3
        );

        $averageUnitCost = round(
            (float) (
                $stockLevel[
                    'average_unit_cost'
                ] ?? 0
            ),
            4
        );

        $inventoryValue = round(
            (float) (
                $stockLevel[
                    'inventory_value'
                ] ?? 0
            ),
            4
        );

        if (
            $quantity > 0 &&
            $inventoryValue <= 0 &&
            $averageUnitCost > 0
        ) {
            $inventoryValue = round(
                $quantity *
                $averageUnitCost,
                4
            );
        }

        if (
            $quantity > 0 &&
            $inventoryValue > 0
        ) {
            $averageUnitCost = round(
                $inventoryValue /
                $quantity,
                4
            );
        }

        return [
            'quantity' =>
                $quantity,

            'average_unit_cost' =>
                $averageUnitCost,

            'inventory_value' =>
                $inventoryValue,
        ];
    }

    private function saveState(
        int $companyId,
        int $productId,
        int $warehouseId,
        float $quantity,
        float $averageUnitCost,
        float $inventoryValue
    ): void {
        $updated =
            $this->stockLevelModel
                ->updateCostState(
                    $companyId,
                    $productId,
                    $warehouseId,
                    $quantity,
                    $averageUnitCost,
                    $inventoryValue
                );

        if (!$updated) {
            throw new Exception(
                'Inventory cost state could not be updated.'
            );
        }
    }

    private function normalizeQuantity(
        float $quantity
    ): float {
        $quantity = round(
            $quantity,
            3
        );

        if ($quantity <= 0) {
            throw new Exception(
                'Quantity must be greater than zero.'
            );
        }

        return $quantity;
    }

    private function normalizeCost(
        float $unitCost
    ): float {
        $unitCost = round(
            $unitCost,
            4
        );

        if ($unitCost < 0) {
            throw new Exception(
                'Unit cost cannot be negative.'
            );
        }

        return $unitCost;
    }

    private function requireTransaction(): void
    {
        if (!$this->db->inTransaction()) {
            throw new LogicException(
                'Inventory cost operations require an active database transaction.'
            );
        }
    }
}