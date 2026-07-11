<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class ProductMovementReportService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getSummary(
        int $companyId,
        int $productId,
        string $dateFrom,
        string $dateTo
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS movements_count,

                COALESCE(SUM(
                    CASE
                        WHEN type IN ('purchase', 'in', 'sale_cancel')
                        THEN quantity
                        ELSE 0
                    END
                ), 0) AS incoming_quantity,

                COALESCE(SUM(
                    CASE
                        WHEN type IN ('sale', 'out', 'purchase_cancel')
                        THEN quantity
                        ELSE 0
                    END
                ), 0) AS outgoing_quantity,

                COALESCE(SUM(
                    CASE
                        WHEN type = 'transfer'
                        THEN quantity
                        ELSE 0
                    END
                ), 0) AS transfer_quantity
            FROM warehouse_transactions
            WHERE company_id = ?
            AND product_id = ?
            AND DATE(created_at) BETWEEN ? AND ?
        ");

        $stmt->execute([
            $companyId,
            $productId,
            $dateFrom,
            $dateTo,
        ]);

        $result = $stmt->fetch();

        if (!$result) {
            return [
                'movements_count' => 0,
                'incoming_quantity' => 0,
                'outgoing_quantity' => 0,
                'transfer_quantity' => 0,
                'net_change' => 0,
            ];
        }

        $incomingQuantity = (float)$result['incoming_quantity'];
        $outgoingQuantity = (float)$result['outgoing_quantity'];

        return [
            'movements_count' => (int)$result['movements_count'],
            'incoming_quantity' => $incomingQuantity,
            'outgoing_quantity' => $outgoingQuantity,
            'transfer_quantity' => (float)$result['transfer_quantity'],
            'net_change' => $incomingQuantity - $outgoingQuantity,
        ];
    }

    public function getMovements(
        int $companyId,
        int $productId,
        string $dateFrom,
        string $dateTo
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                warehouse_transactions.id,
                warehouse_transactions.type,
                warehouse_transactions.quantity,
                warehouse_transactions.reference_type,
                warehouse_transactions.reference_id,
                warehouse_transactions.note,
                warehouse_transactions.created_at,

                products.internal_code,
                products.name AS product_name,
                products.unit,

                from_warehouse.name AS from_warehouse_name,
                from_warehouse.code AS from_warehouse_code,

                to_warehouse.name AS to_warehouse_name,
                to_warehouse.code AS to_warehouse_code,

                users.name AS user_name
            FROM warehouse_transactions
            INNER JOIN products
                ON warehouse_transactions.product_id = products.id

            LEFT JOIN warehouses AS from_warehouse
                ON warehouse_transactions.from_warehouse_id = from_warehouse.id

            LEFT JOIN warehouses AS to_warehouse
                ON warehouse_transactions.to_warehouse_id = to_warehouse.id

            LEFT JOIN users
                ON warehouse_transactions.user_id = users.id

            WHERE warehouse_transactions.company_id = ?
            AND warehouse_transactions.product_id = ?
            AND DATE(warehouse_transactions.created_at) BETWEEN ? AND ?
            ORDER BY warehouse_transactions.id DESC
        ");

        $stmt->execute([
            $companyId,
            $productId,
            $dateFrom,
            $dateTo,
        ]);

        return $stmt->fetchAll();
    }

    public function getWarehouseSummary(
        int $companyId,
        int $productId,
        string $dateFrom,
        string $dateTo
    ): array {
        $movements = $this->getMovements(
            $companyId,
            $productId,
            $dateFrom,
            $dateTo
        );

        $warehouses = [];

        foreach ($movements as $movement) {
            $type = $movement['type'];
            $quantity = (float)$movement['quantity'];

            if ($type === 'purchase' || $type === 'in' || $type === 'sale_cancel') {
                $this->addWarehouseMovement(
                    $warehouses,
                    $movement['to_warehouse_code'],
                    $movement['to_warehouse_name'],
                    $quantity,
                    0,
                    0,
                    0
                );
            }

            if ($type === 'sale' || $type === 'out' || $type === 'purchase_cancel') {
                $this->addWarehouseMovement(
                    $warehouses,
                    $movement['from_warehouse_code'],
                    $movement['from_warehouse_name'],
                    0,
                    $quantity,
                    0,
                    0
                );
            }

            if ($type === 'transfer') {
                $this->addWarehouseMovement(
                    $warehouses,
                    $movement['from_warehouse_code'],
                    $movement['from_warehouse_name'],
                    0,
                    0,
                    0,
                    $quantity
                );

                $this->addWarehouseMovement(
                    $warehouses,
                    $movement['to_warehouse_code'],
                    $movement['to_warehouse_name'],
                    0,
                    0,
                    $quantity,
                    0
                );
            }
        }

        return array_values($warehouses);
    }

    private function addWarehouseMovement(
        array &$warehouses,
        ?string $warehouseCode,
        ?string $warehouseName,
        float $incoming,
        float $outgoing,
        float $transferIn,
        float $transferOut
    ): void {
        if ($warehouseCode === null || $warehouseName === null) {
            return;
        }

        $key = $warehouseCode . ' - ' . $warehouseName;

        if (!isset($warehouses[$key])) {
            $warehouses[$key] = [
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $warehouseName,
                'incoming_quantity' => 0,
                'outgoing_quantity' => 0,
                'transfer_in_quantity' => 0,
                'transfer_out_quantity' => 0,
                'net_change' => 0,
            ];
        }

        $warehouses[$key]['incoming_quantity'] += $incoming;
        $warehouses[$key]['outgoing_quantity'] += $outgoing;
        $warehouses[$key]['transfer_in_quantity'] += $transferIn;
        $warehouses[$key]['transfer_out_quantity'] += $transferOut;

        $warehouses[$key]['net_change'] =
            $warehouses[$key]['incoming_quantity']
            - $warehouses[$key]['outgoing_quantity']
            + $warehouses[$key]['transfer_in_quantity']
            - $warehouses[$key]['transfer_out_quantity'];
    }
}
