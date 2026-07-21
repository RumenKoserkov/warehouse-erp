<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ProfitReport extends Model
{
    public function eventsByCompany(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        array $filters = []
    ): array {
        $saleWhere = [
            'sales.company_id = :sale_company_id',
            "sales.status = 'completed'",
            'sales.sale_date BETWEEN :sale_date_from AND :sale_date_to',
        ];

        $returnWhere = [
            'sales_returns.company_id = :return_company_id',
            "sales_returns.status = 'completed'",
            "sales.status = 'completed'",
            'sales_returns.return_date BETWEEN :return_date_from AND :return_date_to',
        ];

        $parameters = [
            'sale_company_id' => $companyId,
            'sale_date_from' => $dateFrom,
            'sale_date_to' => $dateTo,

            'return_company_id' => $companyId,
            'return_date_from' => $dateFrom,
            'return_date_to' => $dateTo,
        ];

        $warehouseId =
            (int) ($filters['warehouse_id'] ?? 0);

        if ($warehouseId > 0) {
            $saleWhere[] =
                'sales.warehouse_id = :sale_warehouse_id';

            $returnWhere[] =
                'sales_returns.warehouse_id = :return_warehouse_id';

            $parameters['sale_warehouse_id'] =
                $warehouseId;

            $parameters['return_warehouse_id'] =
                $warehouseId;
        }

        $clientId =
            (int) ($filters['client_id'] ?? 0);

        if ($clientId > 0) {
            $saleWhere[] =
                'sales.client_id = :sale_client_id';

            $returnWhere[] =
                'sales.client_id = :return_client_id';

            $parameters['sale_client_id'] =
                $clientId;

            $parameters['return_client_id'] =
                $clientId;
        }

        $productId =
            (int) ($filters['product_id'] ?? 0);

        if ($productId > 0) {
            $saleWhere[] =
                'sale_items.product_id = :sale_product_id';

            $returnWhere[] =
                'sales_return_items.product_id = :return_product_id';

            $parameters['sale_product_id'] =
                $productId;

            $parameters['return_product_id'] =
                $productId;
        }

        $categoryId =
            (int) ($filters['category_id'] ?? 0);

        if ($categoryId > 0) {
            $saleWhere[] =
                'products.category_id = :sale_category_id';

            $returnWhere[] =
                'products.category_id = :return_category_id';

            $parameters['sale_category_id'] =
                $categoryId;

            $parameters['return_category_id'] =
                $categoryId;
        }

        $costStatus =
            (string) ($filters['cost_status'] ?? 'all');

        if ($costStatus === 'costed') {
            $saleWhere[] =
                'sale_items.total_cost IS NOT NULL';

            $returnWhere[] = "
                (
                    sales_return_items.restock_quantity <= 0
                    OR sales_return_items.restocked_cost IS NOT NULL
                )
            ";
        }

        if ($costStatus === 'uncosted') {
            $saleWhere[] =
                'sale_items.total_cost IS NULL';

            $returnWhere[] = "
                sales_return_items.restock_quantity > 0
                AND sales_return_items.restocked_cost IS NULL
            ";
        }

        $search = trim(
            (string) ($filters['search'] ?? '')
        );

        if ($search !== '') {
            $searchTerm = '%' . $search . '%';

            $saleWhere[] = "
                (
                    sales.sale_number
                        LIKE :sale_search_sale

                    OR sale_items.product_name
                        LIKE :sale_search_product

                    OR sale_items.product_internal_code
                        LIKE :sale_search_code

                    OR clients.name
                        LIKE :sale_search_client

                    OR clients.company_name
                        LIKE :sale_search_company

                    OR warehouses.name
                        LIKE :sale_search_warehouse
                )
            ";

            $returnWhere[] = "
                (
                    sales_returns.return_number
                        LIKE :return_search_return

                    OR sales.sale_number
                        LIKE :return_search_sale

                    OR sales_return_items.product_name
                        LIKE :return_search_product

                    OR sales_return_items.product_internal_code
                        LIKE :return_search_code

                    OR clients.name
                        LIKE :return_search_client

                    OR clients.company_name
                        LIKE :return_search_company

                    OR warehouses.name
                        LIKE :return_search_warehouse
                )
            ";

            $parameters['sale_search_sale'] =
                $searchTerm;

            $parameters['sale_search_product'] =
                $searchTerm;

            $parameters['sale_search_code'] =
                $searchTerm;

            $parameters['sale_search_client'] =
                $searchTerm;

            $parameters['sale_search_company'] =
                $searchTerm;

            $parameters['sale_search_warehouse'] =
                $searchTerm;

            $parameters['return_search_return'] =
                $searchTerm;

            $parameters['return_search_sale'] =
                $searchTerm;

            $parameters['return_search_product'] =
                $searchTerm;

            $parameters['return_search_code'] =
                $searchTerm;

            $parameters['return_search_client'] =
                $searchTerm;

            $parameters['return_search_company'] =
                $searchTerm;

            $parameters['return_search_warehouse'] =
                $searchTerm;
        }

        $saleSql = "
            SELECT
                sales.sale_date AS event_date,

                'sale' AS source_type,

                sales.id AS sale_id,
                sales.sale_number,

                NULL AS sales_return_id,
                NULL AS return_number,

                sales.client_id,

                COALESCE(
                    NULLIF(
                        clients.company_name,
                        ''
                    ),
                    NULLIF(
                        clients.name,
                        ''
                    ),
                    'No Client'
                ) AS client_name,

                sales.warehouse_id,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                sale_items.product_id,
                sale_items.product_name,
                sale_items.product_internal_code,
                sale_items.unit
                    AS product_unit,

                products.category_id,

                COALESCE(
                    categories.name,
                    'Uncategorized'
                ) AS category_name,

                sale_items.quantity
                    AS signed_quantity,

                sale_items.quantity
                    AS sold_quantity,

                0.000
                    AS returned_quantity,

                0.000
                    AS restocked_quantity,

                ROUND(
                    sale_items.net_amount,
                    2
                ) AS revenue_amount,

                sale_items.total_cost
                    AS cogs_amount,

                CASE
                    WHEN sale_items.total_cost
                        IS NULL
                        THEN NULL

                    ELSE ROUND(
                        sale_items.net_amount -
                        sale_items.total_cost,
                        2
                    )
                END AS gross_profit_amount,

                CASE
                    WHEN sale_items.total_cost
                        IS NULL
                        THEN 0
                    ELSE 1
                END AS is_costed

            FROM sale_items

            INNER JOIN sales
                ON sales.id =
                    sale_items.sale_id

                AND sales.company_id =
                    sale_items.company_id

            INNER JOIN warehouses
                ON warehouses.id =
                    sales.warehouse_id

                AND warehouses.company_id =
                    sales.company_id

            LEFT JOIN clients
                ON clients.id =
                    sales.client_id

                AND clients.company_id =
                    sales.company_id

            LEFT JOIN products
                ON products.id =
                    sale_items.product_id

                AND products.company_id =
                    sale_items.company_id

            LEFT JOIN categories
                ON categories.id =
                    products.category_id

                AND categories.company_id =
                    products.company_id

            WHERE " .
            implode(
                "\nAND ",
                $saleWhere
            );

        $returnSql = "
            SELECT
                sales_returns.return_date
                    AS event_date,

                'sales_return'
                    AS source_type,

                sales.id AS sale_id,
                sales.sale_number,

                sales_returns.id
                    AS sales_return_id,

                sales_returns.return_number,

                sales.client_id,

                COALESCE(
                    NULLIF(
                        clients.company_name,
                        ''
                    ),
                    NULLIF(
                        clients.name,
                        ''
                    ),
                    'No Client'
                ) AS client_name,

                sales_returns.warehouse_id,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                sales_return_items.product_id,
                sales_return_items.product_name,
                sales_return_items.product_internal_code,

                sales_return_items.product_unit,

                products.category_id,

                COALESCE(
                    categories.name,
                    'Uncategorized'
                ) AS category_name,

                -sales_return_items.return_quantity
                    AS signed_quantity,

                0.000
                    AS sold_quantity,

                sales_return_items.return_quantity
                    AS returned_quantity,

                sales_return_items.restock_quantity
                    AS restocked_quantity,

                -ROUND(
                    sales_return_items.net_amount,
                    2
                ) AS revenue_amount,

                CASE
                    WHEN sales_return_items.restock_quantity
                        <= 0
                        THEN 0.0000

                    WHEN sales_return_items.restocked_cost
                        IS NULL
                        THEN NULL

                    ELSE -ROUND(
                        sales_return_items.restocked_cost,
                        4
                    )
                END AS cogs_amount,

                CASE
                    WHEN sales_return_items.restock_quantity
                        > 0

                    AND sales_return_items.restocked_cost
                        IS NULL
                        THEN NULL

                    ELSE ROUND(
                        -sales_return_items.net_amount
                        -
                        (
                            CASE
                                WHEN sales_return_items.restock_quantity
                                    <= 0
                                    THEN 0

                                ELSE -sales_return_items.restocked_cost
                            END
                        ),
                        2
                    )
                END AS gross_profit_amount,

                CASE
                    WHEN sales_return_items.restock_quantity
                        > 0

                    AND sales_return_items.restocked_cost
                        IS NULL
                        THEN 0

                    ELSE 1
                END AS is_costed

            FROM sales_return_items

            INNER JOIN sales_returns
                ON sales_returns.id =
                    sales_return_items.sales_return_id

                AND sales_returns.company_id =
                    sales_return_items.company_id

            INNER JOIN sales
                ON sales.id =
                    sales_returns.sale_id

                AND sales.company_id =
                    sales_returns.company_id

            INNER JOIN warehouses
                ON warehouses.id =
                    sales_returns.warehouse_id

                AND warehouses.company_id =
                    sales_returns.company_id

            LEFT JOIN clients
                ON clients.id =
                    sales.client_id

                AND clients.company_id =
                    sales.company_id

            LEFT JOIN products
                ON products.id =
                    sales_return_items.product_id

                AND products.company_id =
                    sales_return_items.company_id

            LEFT JOIN categories
                ON categories.id =
                    products.category_id

                AND categories.company_id =
                    products.company_id

            WHERE " .
            implode(
                "\nAND ",
                $returnWhere
            );

        $sql = "
            SELECT *
            FROM (
                {$saleSql}

                UNION ALL

                {$returnSql}
            ) AS profit_events

            ORDER BY
                profit_events.event_date DESC,
                profit_events.source_type ASC,
                profit_events.sale_id DESC,
                profit_events.product_name ASC
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function warehousesByCompany(
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT
                id,
                name,
                code,
                is_active

            FROM warehouses

            WHERE company_id = :company_id

            ORDER BY
                is_active DESC,
                name ASC
            "
        );

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function clientsByCompany(
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT
                id,
                name,
                company_name,
                is_active

            FROM clients

            WHERE company_id = :company_id

            ORDER BY
                is_active DESC,
                COALESCE(
                    NULLIF(company_name, ''),
                    name
                ) ASC
            "
        );

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function productsByCompany(
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT
                id,
                name,
                internal_code,
                is_active

            FROM products

            WHERE company_id = :company_id

            ORDER BY
                is_active DESC,
                name ASC
            "
        );

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function categoriesByCompany(
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT
                id,
                name,
                is_active

            FROM categories

            WHERE company_id = :company_id

            ORDER BY
                is_active DESC,
                name ASC
            "
        );

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }
}