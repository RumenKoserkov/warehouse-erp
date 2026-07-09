<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class DashboardService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getStats(int $companyId): array
    {
        return [
            'total_products' => $this->getTotalProducts($companyId),
            'total_stock_value' => $this->getTotalStockValue($companyId),
            'today_sales_amount' => $this->getTodaySalesAmount($companyId),
            'today_purchases_amount' => $this->getTodayPurchasesAmount($companyId),
            'low_stock_count' => $this->getLowStockCount($companyId),
            'today_sales_count' => $this->getTodaySalesCount($companyId),
            'today_purchases_count' => $this->getTodayPurchasesCount($companyId),
        ];
    }

    public function getLowStockProducts(int $companyId, int $limit = 5): array
    {
        $limit = $this->safeLimit($limit);

        $stmt = $this->db->prepare("
            SELECT
                products.internal_code,
                products.name AS product_name,
                products.unit,
                products.min_stock,
                stock_levels.quantity,
                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
            AND stock_levels.quantity <= products.min_stock
            ORDER BY stock_levels.quantity ASC
            LIMIT $limit
        ");

        $stmt->execute([$companyId]);

        return $stmt->fetchAll();
    }

    public function getRecentTransactions(int $companyId, int $limit = 10): array
    {
        $limit = $this->safeLimit($limit);

        $stmt = $this->db->prepare("
            SELECT
                warehouse_transactions.type,
                warehouse_transactions.quantity,
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
            ORDER BY warehouse_transactions.id DESC
            LIMIT $limit
        ");

        $stmt->execute([$companyId]);

        return $stmt->fetchAll();
    }

    private function getTotalProducts(int $companyId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM products
            WHERE company_id = ?
            AND is_active = 1
        ");

        $stmt->execute([$companyId]);

        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        return (int)$result['total'];
    }

    private function getTotalStockValue(int $companyId): float
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(stock_levels.quantity * products.purchase_price), 0) AS total
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
        ");

        $stmt->execute([$companyId]);

        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        return (float)$result['total'];
    }

    private function getTodaySalesAmount(int $companyId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(total_amount), 0) AS total
            FROM sales
            WHERE company_id = ?
            AND sale_date = CURDATE()
            AND status = 'completed'
        ");

        $stmt->execute([$companyId]);

        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        return (float)$result['total'];
    }

    private function getTodayPurchasesAmount(int $companyId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(total_amount), 0) AS total
            FROM purchases
            WHERE company_id = ?
            AND purchase_date = CURDATE()
            AND status = 'completed'
        ");

        $stmt->execute([$companyId]);

        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        return (float)$result['total'];
    }

    private function getLowStockCount(int $companyId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
            AND stock_levels.quantity <= products.min_stock
        ");

        $stmt->execute([$companyId]);

        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        return (int)$result['total'];
    }

    private function getTodaySalesCount(int $companyId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM sales
            WHERE company_id = ?
            AND sale_date = CURDATE()
            AND status = 'completed'
        ");

        $stmt->execute([$companyId]);

        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        return (int)$result['total'];
    }

    private function getTodayPurchasesCount(int $companyId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM purchases
            WHERE company_id = ?
            AND purchase_date = CURDATE()
            AND status = 'completed'
        ");

        $stmt->execute([$companyId]);

        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        return (int)$result['total'];
    }

    private function safeLimit(int $limit): int
    {
        if ($limit < 1) {
            return 5;
        }

        if ($limit > 50) {
            return 50;
        }

        return $limit;
    }
}