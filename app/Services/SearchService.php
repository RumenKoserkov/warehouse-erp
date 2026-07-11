<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class SearchService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function search(int $companyId, string $query): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [
                'products' => [],
                'clients' => [],
                'suppliers' => [],
                'sales' => [],
                'purchases' => [],
            ];
        }

        return [
            'products' => $this->searchProducts($companyId, $query),
            'clients' => $this->searchClients($companyId, $query),
            'suppliers' => $this->searchSuppliers($companyId, $query),
            'sales' => $this->searchSales($companyId, $query),
            'purchases' => $this->searchPurchases($companyId, $query),
        ];
    }

    private function searchProducts(int $companyId, string $query): array
    {
        $searchTerm = '%' . $query . '%';

        $stmt = $this->db->prepare("
            SELECT
                products.id,
                products.internal_code,
                products.barcode,
                products.name,
                products.unit,
                products.purchase_price,
                products.selling_price,
                products.is_active,

                categories.name AS category_name,

                suppliers.name AS supplier_name
            FROM products
            INNER JOIN categories ON products.category_id = categories.id
            LEFT JOIN suppliers ON products.supplier_id = suppliers.id
            WHERE products.company_id = ?
            AND (
                products.internal_code LIKE ?
                OR products.barcode LIKE ?
                OR products.name LIKE ?
                OR categories.name LIKE ?
                OR suppliers.name LIKE ?
            )
            ORDER BY products.name ASC
            LIMIT 10
        ");

        $stmt->execute([
            $companyId,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
        ]);

        return $stmt->fetchAll();
    }

    private function searchClients(int $companyId, string $query): array
    {
        $searchTerm = '%' . $query . '%';

        $stmt = $this->db->prepare("
            SELECT
                id,
                name,
                phone,
                email,
                company_name,
                eik,
                contact_person,
                is_active
            FROM clients
            WHERE company_id = ?
            AND (
                name LIKE ?
                OR phone LIKE ?
                OR email LIKE ?
                OR company_name LIKE ?
                OR eik LIKE ?
                OR contact_person LIKE ?
            )
            ORDER BY name ASC
            LIMIT 10
        ");

        $stmt->execute([
            $companyId,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
        ]);

        return $stmt->fetchAll();
    }

    private function searchSuppliers(int $companyId, string $query): array
    {
        $searchTerm = '%' . $query . '%';

        $stmt = $this->db->prepare("
            SELECT
                id,
                name,
                phone,
                email,
                company_name,
                eik,
                contact_person,
                is_active
            FROM suppliers
            WHERE company_id = ?
            AND (
                name LIKE ?
                OR phone LIKE ?
                OR email LIKE ?
                OR company_name LIKE ?
                OR eik LIKE ?
                OR contact_person LIKE ?
            )
            ORDER BY name ASC
            LIMIT 10
        ");

        $stmt->execute([
            $companyId,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
        ]);

        return $stmt->fetchAll();
    }

    private function searchSales(int $companyId, string $query): array
    {
        $searchTerm = '%' . $query . '%';

        $stmt = $this->db->prepare("
            SELECT
                sales.id,
                sales.sale_number,
                sales.sale_date,
                sales.status,
                sales.total_amount,
                sales.payment_method,

                clients.name AS client_name,
                clients.company_name AS client_company_name,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code
            FROM sales
            LEFT JOIN clients ON sales.client_id = clients.id
            INNER JOIN warehouses ON sales.warehouse_id = warehouses.id
            WHERE sales.company_id = ?
            AND (
                sales.sale_number LIKE ?
                OR clients.name LIKE ?
                OR clients.company_name LIKE ?
                OR warehouses.name LIKE ?
                OR warehouses.code LIKE ?
            )
            ORDER BY sales.id DESC
            LIMIT 10
        ");

        $stmt->execute([
            $companyId,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
        ]);

        return $stmt->fetchAll();
    }

    private function searchPurchases(int $companyId, string $query): array
    {
        $searchTerm = '%' . $query . '%';

        $stmt = $this->db->prepare("
            SELECT
                purchases.id,
                purchases.purchase_number,
                purchases.purchase_date,
                purchases.status,
                purchases.total_amount,
                purchases.payment_method,

                suppliers.name AS supplier_name,
                suppliers.company_name AS supplier_company_name,

                warehouses.name AS warehouse_name,
                warehouses.code AS warehouse_code
            FROM purchases
            LEFT JOIN suppliers ON purchases.supplier_id = suppliers.id
            INNER JOIN warehouses ON purchases.warehouse_id = warehouses.id
            WHERE purchases.company_id = ?
            AND (
                purchases.purchase_number LIKE ?
                OR suppliers.name LIKE ?
                OR suppliers.company_name LIKE ?
                OR warehouses.name LIKE ?
                OR warehouses.code LIKE ?
            )
            ORDER BY purchases.id DESC
            LIMIT 10
        ");

        $stmt->execute([
            $companyId,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
        ]);

        return $stmt->fetchAll();
    }

    public function countResults(array $results): int
    {
        $total = 0;

        foreach ($results as $group) {
            $total += count($group);
        }

        return $total;
    }
}
