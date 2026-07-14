<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    public function allByCompany(
        int $companyId,
        string $search = ''
    ): array {
        $sql = "
            SELECT
                products.*,
                categories.name AS category_name,
                suppliers.name AS supplier_name
            FROM products
            INNER JOIN categories
                ON products.category_id = categories.id
            LEFT JOIN suppliers
                ON products.supplier_id = suppliers.id
            WHERE products.company_id = ?
        ";

        $params = [$companyId];

        if ($search !== '') {
            $sql .= "
                AND (
                    products.name LIKE ?
                    OR products.internal_code LIKE ?
                    OR products.barcode LIKE ?
                    OR categories.name LIKE ?
                    OR suppliers.name LIKE ?
                )
            ";

            $searchTerm = '%' . $search . '%';

            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY products.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function countByCompany(
        int $companyId,
        string $search = '',
        array $filters = []
    ): int {
        $sql = "
            SELECT COUNT(DISTINCT products.id)
            FROM products AS products
            LEFT JOIN categories AS categories
                ON categories.id = products.category_id
            LEFT JOIN suppliers AS suppliers
                ON suppliers.id = products.supplier_id
            WHERE products.company_id = :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        $this->applyListFilters(
            $sql,
            $parameters,
            $search,
            $filters
        );

        $statement = $this->db->prepare($sql);

        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value
            );
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function paginateByCompany(
        int $companyId,
        string $search,
        int $limit,
        int $offset,
        string $sortColumn,
        string $sortDirection,
        array $filters = []
    ): array {
        $allowedSortColumns = [
            'products.id',
            'products.name',
            'products.internal_code',
            'products.purchase_price',
            'products.selling_price',
            'products.min_stock',
            'products.is_active',
            'categories.name',
            'suppliers.name',
        ];

        if (!in_array($sortColumn, $allowedSortColumns, true)) {
            $sortColumn = 'products.id';
        }

        if (
            $sortDirection !== 'ASC' &&
            $sortDirection !== 'DESC'
        ) {
            $sortDirection = 'DESC';
        }

        $sql = "
            SELECT
                products.*,
                categories.name AS category_name,
                suppliers.name AS supplier_name
            FROM products AS products
            LEFT JOIN categories AS categories
                ON categories.id = products.category_id
            LEFT JOIN suppliers AS suppliers
                ON suppliers.id = products.supplier_id
            WHERE products.company_id = :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        $this->applyListFilters(
            $sql,
            $parameters,
            $search,
            $filters
        );

        $sql .= "
            ORDER BY {$sortColumn} {$sortDirection}
            LIMIT :limit
            OFFSET :offset
        ";

        $statement = $this->db->prepare($sql);

        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value
            );
        }

        $statement->bindValue(
            ':limit',
            $limit,
            \PDO::PARAM_INT
        );

        $statement->bindValue(
            ':offset',
            $offset,
            \PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll();
    }

    public function generateNextInternalCode(
        int $companyId
    ): string {
        $stmt = $this->db->prepare("
            SELECT internal_code
            FROM products
            WHERE company_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([$companyId]);

        $lastProduct = $stmt->fetch();

        if (!$lastProduct) {
            return 'PRD-000001';
        }

        $lastCode = $lastProduct['internal_code'];

        $number = (int) str_replace(
            'PRD-',
            '',
            $lastCode
        );

        $nextNumber = $number + 1;

        return 'PRD-' . str_pad(
            (string) $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    public function barcodeExistsInCompany(
        ?string $barcode,
        int $companyId
    ): bool {
        if ($barcode === null || $barcode === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT id
            FROM products
            WHERE barcode = ?
            AND company_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $barcode,
            $companyId,
        ]);

        return $stmt->fetch() !== false;
    }

    public function findByIdAndCompany(
        int $id,
        int $companyId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM products
            WHERE id = ?
            AND company_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id,
            $companyId,
        ]);

        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        return $product;
    }

    public function barcodeExistsInCompanyExceptProduct(
        ?string $barcode,
        int $companyId,
        int $productId
    ): bool {
        if ($barcode === null || $barcode === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT id
            FROM products
            WHERE barcode = ?
            AND company_id = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $barcode,
            $companyId,
            $productId,
        ]);

        return $stmt->fetch() !== false;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO products
                (
                    company_id,
                    category_id,
                    supplier_id,
                    internal_code,
                    barcode,
                    name,
                    unit,
                    purchase_price,
                    selling_price,
                    min_stock,
                    description,
                    image_path,
                    is_active
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['company_id'],
            $data['category_id'],
            $data['supplier_id'],
            $data['internal_code'],
            $data['barcode'],
            $data['name'],
            $data['unit'],
            $data['purchase_price'],
            $data['selling_price'],
            $data['min_stock'],
            $data['description'],
            $data['image_path'],
            $data['is_active'],
        ]);
    }

    public function update(
        int $id,
        array $data
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE products
            SET
                category_id = ?,
                supplier_id = ?,
                barcode = ?,
                name = ?,
                unit = ?,
                purchase_price = ?,
                selling_price = ?,
                min_stock = ?,
                description = ?,
                image_path = ?,
                is_active = ?
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $data['category_id'],
            $data['supplier_id'],
            $data['barcode'],
            $data['name'],
            $data['unit'],
            $data['purchase_price'],
            $data['selling_price'],
            $data['min_stock'],
            $data['description'],
            $data['image_path'],
            $data['is_active'],
            $id,
            $data['company_id'],
        ]);
    }

    public function deactivate(
        int $id,
        int $companyId
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE products
            SET is_active = 0
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $id,
            $companyId,
        ]);
    }

    public function quickLookup(
        int $companyId,
        string $query,
        int $warehouseId = 0,
        int $limit = 10
    ): array {
        if ($limit < 1) {
            $limit = 10;
        }

        if ($limit > 20) {
            $limit = 20;
        }

        $sql = "
        SELECT
            products.id,
            products.internal_code,
            products.barcode,
            products.name,
            products.unit,
            products.purchase_price,
            products.selling_price,
            products.image_path,
            categories.name AS category_name,
            suppliers.name AS supplier_name,
            COALESCE(stock_levels.quantity, 0) AS stock_quantity,

            CASE
                WHEN products.barcode = :exact_barcode THEN 1
                WHEN products.internal_code = :exact_code THEN 2
                WHEN products.name = :exact_name THEN 3
                WHEN products.barcode LIKE :barcode_prefix THEN 4
                WHEN products.internal_code LIKE :code_prefix THEN 5
                WHEN products.name LIKE :name_prefix THEN 6
                ELSE 7
            END AS match_priority

        FROM products AS products

        LEFT JOIN categories AS categories
            ON categories.id = products.category_id

        LEFT JOIN suppliers AS suppliers
            ON suppliers.id = products.supplier_id

        LEFT JOIN stock_levels AS stock_levels
            ON stock_levels.company_id = products.company_id
            AND stock_levels.product_id = products.id
            AND stock_levels.warehouse_id = :warehouse_id

        WHERE products.company_id = :company_id
            AND products.is_active = 1
            AND (
                products.barcode = :where_exact_barcode
                OR products.internal_code = :where_exact_code
                OR products.barcode LIKE :barcode_search
                OR products.internal_code LIKE :code_search
                OR products.name LIKE :name_search
            )

        ORDER BY
            match_priority ASC,
            products.name ASC

        LIMIT :limit
    ";

        $statement = $this->db->prepare($sql);

        $statement->bindValue(
            ':company_id',
            $companyId,
            \PDO::PARAM_INT
        );

        $statement->bindValue(
            ':warehouse_id',
            $warehouseId,
            \PDO::PARAM_INT
        );

        $statement->bindValue(
            ':exact_barcode',
            $query
        );

        $statement->bindValue(
            ':exact_code',
            $query
        );

        $statement->bindValue(
            ':exact_name',
            $query
        );

        $statement->bindValue(
            ':barcode_prefix',
            $query . '%'
        );

        $statement->bindValue(
            ':code_prefix',
            $query . '%'
        );

        $statement->bindValue(
            ':name_prefix',
            $query . '%'
        );

        $statement->bindValue(
            ':where_exact_barcode',
            $query
        );

        $statement->bindValue(
            ':where_exact_code',
            $query
        );

        $search = '%' . $query . '%';

        $statement->bindValue(
            ':barcode_search',
            $search
        );

        $statement->bindValue(
            ':code_search',
            $search
        );

        $statement->bindValue(
            ':name_search',
            $search
        );

        $statement->bindValue(
            ':limit',
            $limit,
            \PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll();
    }

    public function activeByCompany(
        int $companyId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                id,
                internal_code,
                name,
                unit,
                purchase_price,
                selling_price
            FROM products
            WHERE company_id = ?
            AND is_active = 1
            ORDER BY name ASC
        ");

        $stmt->execute([$companyId]);

        return $stmt->fetchAll();
    }

    private function applyListFilters(
        string &$sql,
        array &$parameters,
        string $search,
        array $filters
    ): void {
        if ($search !== '') {
            $sql .= "
                AND (
                    products.name LIKE :search_name
                    OR products.internal_code LIKE :search_code
                    OR products.barcode LIKE :search_barcode
                    OR categories.name LIKE :search_category
                    OR suppliers.name LIKE :search_supplier
                )
            ";

            $searchValue = '%' . $search . '%';

            $parameters['search_name'] = $searchValue;
            $parameters['search_code'] = $searchValue;
            $parameters['search_barcode'] = $searchValue;
            $parameters['search_category'] = $searchValue;
            $parameters['search_supplier'] = $searchValue;
        }

        if (
            isset($filters['category_id']) &&
            is_int($filters['category_id']) &&
            $filters['category_id'] > 0
        ) {
            $sql .= "
                AND products.category_id = :category_id
            ";

            $parameters['category_id'] =
                $filters['category_id'];
        }

        if (
            isset($filters['supplier_id']) &&
            is_int($filters['supplier_id']) &&
            $filters['supplier_id'] > 0
        ) {
            $sql .= "
                AND products.supplier_id = :supplier_id
            ";

            $parameters['supplier_id'] =
                $filters['supplier_id'];
        }

        if (
            isset($filters['unit']) &&
            is_string($filters['unit']) &&
            $filters['unit'] !== ''
        ) {
            $sql .= "
                AND products.unit = :unit
            ";

            $parameters['unit'] = $filters['unit'];
        }

        if (
            isset($filters['status']) &&
            $filters['status'] === 'active'
        ) {
            $sql .= "
                AND products.is_active = 1
            ";
        }

        if (
            isset($filters['status']) &&
            $filters['status'] === 'inactive'
        ) {
            $sql .= "
                AND products.is_active = 0
            ";
        }

        if (
            isset($filters['min_price']) &&
            $filters['min_price'] !== '' &&
            is_numeric($filters['min_price'])
        ) {
            $sql .= "
                AND products.selling_price >= :min_price
            ";

            $parameters['min_price'] =
                (float) $filters['min_price'];
        }

        if (
            isset($filters['max_price']) &&
            $filters['max_price'] !== '' &&
            is_numeric($filters['max_price'])
        ) {
            $sql .= "
                AND products.selling_price <= :max_price
            ";

            $parameters['max_price'] =
                (float) $filters['max_price'];
        }
    }
}
