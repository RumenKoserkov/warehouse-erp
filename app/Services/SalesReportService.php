<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class SalesReportService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getSummary(int $companyId, string $dateFrom, string $dateTo): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS sales_count,
                COALESCE(SUM(total_amount), 0) AS total_sales,
                COALESCE(AVG(total_amount), 0) AS average_sale,
                COALESCE(SUM(discount_amount), 0) AS total_discount
            FROM sales
            WHERE company_id = ?
            AND status = 'completed'
            AND sale_date BETWEEN ? AND ?
        ");

        $stmt->execute([
            $companyId,
            $dateFrom,
            $dateTo,
        ]);

        $result = $stmt->fetch();

        if (!$result) {
            return [
                'sales_count' => 0,
                'total_sales' => 0,
                'average_sale' => 0,
                'total_discount' => 0,
            ];
        }

        return [
            'sales_count' => (int)$result['sales_count'],
            'total_sales' => (float)$result['total_sales'],
            'average_sale' => (float)$result['average_sale'],
            'total_discount' => (float)$result['total_discount'],
        ];
    }

    public function getTopProducts(int $companyId, string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $limit = $this->safeLimit($limit);

        $stmt = $this->db->prepare("
            SELECT
                sale_items.product_id,
                sale_items.product_internal_code,
                sale_items.product_name,
                sale_items.unit,
                SUM(sale_items.quantity) AS total_quantity,
                SUM(sale_items.total_price) AS total_amount
            FROM sale_items
            INNER JOIN sales ON sale_items.sale_id = sales.id
            WHERE sale_items.company_id = ?
            AND sales.company_id = ?
            AND sales.status = 'completed'
            AND sales.sale_date BETWEEN ? AND ?
            GROUP BY
                sale_items.product_id,
                sale_items.product_internal_code,
                sale_items.product_name,
                sale_items.unit
            ORDER BY total_quantity DESC
            LIMIT $limit
        ");

        $stmt->execute([
            $companyId,
            $companyId,
            $dateFrom,
            $dateTo,
        ]);

        return $stmt->fetchAll();
    }

    public function getSalesByDay(int $companyId, string $dateFrom, string $dateTo): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sale_date,
                COUNT(*) AS sales_count,
                COALESCE(SUM(total_amount), 0) AS total_amount
            FROM sales
            WHERE company_id = ?
            AND status = 'completed'
            AND sale_date BETWEEN ? AND ?
            GROUP BY sale_date
            ORDER BY sale_date ASC
        ");

        $stmt->execute([
            $companyId,
            $dateFrom,
            $dateTo,
        ]);

        return $stmt->fetchAll();
    }

    public function getRecentSales(int $companyId, string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $limit = $this->safeLimit($limit);

        $stmt = $this->db->prepare("
            SELECT
                sales.id,
                sales.sale_number,
                sales.sale_date,
                sales.total_amount,
                sales.payment_method,
                sales.created_at,

                clients.name AS client_name,
                clients.company_name AS client_company_name,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code,

                users.name AS user_name
            FROM sales
            LEFT JOIN clients ON sales.client_id = clients.id
            INNER JOIN warehouses ON sales.warehouse_id = warehouses.id
            LEFT JOIN users ON sales.user_id = users.id
            WHERE sales.company_id = ?
            AND sales.status = 'completed'
            AND sales.sale_date BETWEEN ? AND ?
            ORDER BY sales.id DESC
            LIMIT $limit
        ");

        $stmt->execute([
            $companyId,
            $dateFrom,
            $dateTo,
        ]);

        return $stmt->fetchAll();
    }

    private function safeLimit(int $limit): int
    {
        if ($limit < 1) {
            return 10;
        }

        if ($limit > 50) {
            return 50;
        }

        return $limit;
    }
}