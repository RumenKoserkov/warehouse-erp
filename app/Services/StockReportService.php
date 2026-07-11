<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class StockReportService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getSummary(int $companyId, array $filters = []): array
    {
        $sql = "
            SELECT
                COUNT(stock_levels.id) AS stock_records,
                COALESCE(SUM(stock_levels.quantity), 0) AS total_quantity,
                COALESCE(SUM(stock_levels.quantity * products.purchase_price), 0) AS total_stock_value,
                COUNT(DISTINCT stock_levels.product_id) AS products_with_stock,

                SUM(
                    CASE
                        WHEN stock_levels.quantity <= products.min_stock
                        THEN 1
                        ELSE 0
                    END
                ) AS low_stock_count,

                SUM(
                    CASE
                        WHEN stock_levels.quantity <= 0
                        THEN 1
                        ELSE 0
                    END
                ) AS out_of_stock_count
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            INNER JOIN categories ON products.category_id = categories.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
        ";

        $params = [$companyId];

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND stock_levels.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND products.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();

        if (!$result) {
            return [
                'stock_records' => 0,
                'total_quantity' => 0,
                'total_stock_value' => 0,
                'products_with_stock' => 0,
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
            ];
        }

        return [
            'stock_records' => (int)$result['stock_records'],
            'total_quantity' => (float)$result['total_quantity'],
            'total_stock_value' => (float)$result['total_stock_value'],
            'products_with_stock' => (int)$result['products_with_stock'],
            'low_stock_count' => (int)$result['low_stock_count'],
            'out_of_stock_count' => (int)$result['out_of_stock_count'],
        ];
    }

    public function getStockByWarehouse(int $companyId, array $filters = []): array
    {
        $sql = "
            SELECT
                warehouses.id AS warehouse_id,
                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                COUNT(stock_levels.id) AS stock_records,
                COUNT(DISTINCT stock_levels.product_id) AS products_count,
                COALESCE(SUM(stock_levels.quantity), 0) AS total_quantity,
                COALESCE(SUM(stock_levels.quantity * products.purchase_price), 0) AS total_value
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            INNER JOIN categories ON products.category_id = categories.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
        ";

        $params = [$companyId];

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND stock_levels.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND products.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $sql .= "
            GROUP BY warehouses.id, warehouses.name, warehouses.code
            ORDER BY total_value DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getStockByCategory(int $companyId, array $filters = []): array
    {
        $sql = "
            SELECT
                categories.id AS category_id,
                categories.name AS category_name,

                COUNT(stock_levels.id) AS stock_records,
                COUNT(DISTINCT stock_levels.product_id) AS products_count,
                COALESCE(SUM(stock_levels.quantity), 0) AS total_quantity,
                COALESCE(SUM(stock_levels.quantity * products.purchase_price), 0) AS total_value
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            INNER JOIN categories ON products.category_id = categories.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
        ";

        $params = [$companyId];

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND stock_levels.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND products.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $sql .= "
            GROUP BY categories.id, categories.name
            ORDER BY total_value DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getLowStockItems(int $companyId, array $filters = [], int $limit = 20): array
    {
        $limit = $this->safeLimit($limit);

        $sql = "
            SELECT
                products.internal_code,
                products.name AS product_name,
                products.unit,
                products.min_stock,
                products.purchase_price,

                stock_levels.quantity,
                (stock_levels.quantity * products.purchase_price) AS stock_value,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                categories.name AS category_name
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            INNER JOIN categories ON products.category_id = categories.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
            AND stock_levels.quantity <= products.min_stock
        ";

        $params = [$companyId];

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND stock_levels.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND products.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $sql .= "
            ORDER BY stock_levels.quantity ASC
            LIMIT $limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getOutOfStockItems(int $companyId, array $filters = [], int $limit = 20): array
    {
        $limit = $this->safeLimit($limit);

        $sql = "
            SELECT
                products.internal_code,
                products.name AS product_name,
                products.unit,
                products.min_stock,

                stock_levels.quantity,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                categories.name AS category_name
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            INNER JOIN categories ON products.category_id = categories.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
            AND stock_levels.quantity <= 0
        ";

        $params = [$companyId];

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND stock_levels.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND products.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $sql .= "
            ORDER BY products.name ASC
            LIMIT $limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getMostValuableStock(int $companyId, array $filters = [], int $limit = 10): array
    {
        $limit = $this->safeLimit($limit);

        $sql = "
            SELECT
                products.internal_code,
                products.name AS product_name,
                products.unit,
                products.purchase_price,

                stock_levels.quantity,
                (stock_levels.quantity * products.purchase_price) AS stock_value,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                categories.name AS category_name
            FROM stock_levels
            INNER JOIN products ON stock_levels.product_id = products.id
            INNER JOIN warehouses ON stock_levels.warehouse_id = warehouses.id
            INNER JOIN categories ON products.category_id = categories.id
            WHERE stock_levels.company_id = ?
            AND products.is_active = 1
            AND warehouses.is_active = 1
        ";

        $params = [$companyId];

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND stock_levels.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND products.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $sql .= "
            ORDER BY stock_value DESC
            LIMIT $limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function safeLimit(int $limit): int
    {
        if ($limit < 1) {
            return 10;
        }

        if ($limit > 100) {
            return 100;
        }

        return $limit;
    }
}
