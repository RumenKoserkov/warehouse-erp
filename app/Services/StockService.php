<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\WarehouseTransaction;
use Exception;
use PDO;

class StockService
{
    private PDO $db;

    private WarehouseTransaction
        $transactionModel;

    private InventoryCostService
        $inventoryCostService;

    public function __construct()
    {
        $this->db =
            Database::getConnection();

        $this->transactionModel =
            new WarehouseTransaction();

        $this->inventoryCostService =
            new InventoryCostService();
    }

    public function increase(
        array $data
    ): bool {
        try {
            $quantity =
                (float) $data['quantity'];

            $unitCost =
                (float) $data['unit_cost'];

            $this->validatePositiveQuantity(
                $quantity
            );

            if ($unitCost < 0) {
                throw new Exception(
                    'Unit cost cannot be negative.'
                );
            }

            $this->db->beginTransaction();

            $movement =
                $this->inventoryCostService
                    ->receive(
                        (int) $data[
                            'company_id'
                        ],
                        (int) $data[
                            'product_id'
                        ],
                        (int) $data[
                            'warehouse_id'
                        ],
                        $quantity,
                        $unitCost
                    );

            $transactionData = [
                'company_id' =>
                    (int) $data[
                        'company_id'
                    ],

                'product_id' =>
                    (int) $data[
                        'product_id'
                    ],

                'from_warehouse_id' =>
                    null,

                'to_warehouse_id' =>
                    (int) $data[
                        'warehouse_id'
                    ],

                'user_id' =>
                    (int) $data[
                        'user_id'
                    ],

                'type' =>
                    (string) $data['type'],

                'quantity' =>
                    $quantity,

                'reference_type' =>
                    $data[
                        'reference_type'
                    ] ?? null,

                'reference_id' =>
                    $data[
                        'reference_id'
                    ] ?? null,

                'note' =>
                    $data['note'] ?? null,
            ];

            $created =
                $this->transactionModel
                    ->create(
                        array_merge(
                            $transactionData,

                            $this
                                ->inventoryCostService
                                ->incomingTransactionFields(
                                    $movement
                                )
                        )
                    );

            if (!$created) {
                throw new Exception(
                    'Warehouse transaction could not be created.'
                );
            }

            $this->db->commit();

            return true;
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function decrease(
        array $data
    ): bool {
        try {
            $quantity =
                (float) $data['quantity'];

            $this->validatePositiveQuantity(
                $quantity
            );

            $this->db->beginTransaction();

            $movement =
                $this->inventoryCostService
                    ->issue(
                        (int) $data[
                            'company_id'
                        ],
                        (int) $data[
                            'product_id'
                        ],
                        (int) $data[
                            'warehouse_id'
                        ],
                        $quantity
                    );

            $transactionData = [
                'company_id' =>
                    (int) $data[
                        'company_id'
                    ],

                'product_id' =>
                    (int) $data[
                        'product_id'
                    ],

                'from_warehouse_id' =>
                    (int) $data[
                        'warehouse_id'
                    ],

                'to_warehouse_id' =>
                    null,

                'user_id' =>
                    (int) $data[
                        'user_id'
                    ],

                'type' =>
                    (string) $data['type'],

                'quantity' =>
                    $quantity,

                'reference_type' =>
                    $data[
                        'reference_type'
                    ] ?? null,

                'reference_id' =>
                    $data[
                        'reference_id'
                    ] ?? null,

                'note' =>
                    $data['note'] ?? null,
            ];

            $created =
                $this->transactionModel
                    ->create(
                        array_merge(
                            $transactionData,

                            $this
                                ->inventoryCostService
                                ->outgoingTransactionFields(
                                    $movement
                                )
                        )
                    );

            if (!$created) {
                throw new Exception(
                    'Warehouse transaction could not be created.'
                );
            }

            $this->db->commit();

            return true;
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function transfer(
        array $data
    ): bool {
        try {
            $quantity =
                (float) $data['quantity'];

            $fromWarehouseId =
                (int) $data[
                    'from_warehouse_id'
                ];

            $toWarehouseId =
                (int) $data[
                    'to_warehouse_id'
                ];

            $this->validatePositiveQuantity(
                $quantity
            );

            if (
                $fromWarehouseId ===
                $toWarehouseId
            ) {
                throw new Exception(
                    'Source and destination warehouses must be different.'
                );
            }

            $this->db->beginTransaction();

            $movement =
                $this->inventoryCostService
                    ->transfer(
                        (int) $data[
                            'company_id'
                        ],
                        (int) $data[
                            'product_id'
                        ],
                        $fromWarehouseId,
                        $toWarehouseId,
                        $quantity
                    );

            $transactionData = [
                'company_id' =>
                    (int) $data[
                        'company_id'
                    ],

                'product_id' =>
                    (int) $data[
                        'product_id'
                    ],

                'from_warehouse_id' =>
                    $fromWarehouseId,

                'to_warehouse_id' =>
                    $toWarehouseId,

                'user_id' =>
                    (int) $data[
                        'user_id'
                    ],

                'type' =>
                    'transfer',

                'quantity' =>
                    $quantity,

                'reference_type' =>
                    $data[
                        'reference_type'
                    ] ?? null,

                'reference_id' =>
                    $data[
                        'reference_id'
                    ] ?? null,

                'note' =>
                    $data['note'] ?? null,
            ];

            $created =
                $this->transactionModel
                    ->create(
                        array_merge(
                            $transactionData,

                            $this
                                ->inventoryCostService
                                ->transferTransactionFields(
                                    $movement
                                )
                        )
                    );

            if (!$created) {
                throw new Exception(
                    'Warehouse transaction could not be created.'
                );
            }

            $this->db->commit();

            return true;
        } catch (Exception $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    private function validatePositiveQuantity(
        float $quantity
    ): void {
        if ($quantity <= 0) {
            throw new Exception(
                'Quantity must be greater than zero.'
            );
        }
    }
}