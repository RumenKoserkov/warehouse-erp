<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Generator;

class CsvExport extends Model
{
    public function products(
        int $companyId,
        array $filters = []
    ): Generator {
        $sql = "
        SELECT
            products.*,

            categories.name
                AS category_name,

            suppliers.name
                AS supplier_name,

            COALESCE(
                stock_totals.total_quantity,
                0
            ) AS total_stock_quantity,

            COALESCE(
                stock_totals.total_inventory_value,
                0
            ) AS total_inventory_value

        FROM products

        LEFT JOIN categories
            ON categories.id =
                products.category_id

            AND categories.company_id =
                products.company_id

        LEFT JOIN suppliers
            ON suppliers.id =
                products.supplier_id

            AND suppliers.company_id =
                products.company_id

        LEFT JOIN (
            SELECT
                company_id,
                product_id,

                SUM(quantity)
                    AS total_quantity,

                SUM(inventory_value)
                    AS total_inventory_value

            FROM stock_levels

            GROUP BY
                company_id,
                product_id
        ) AS stock_totals
            ON stock_totals.company_id =
                products.company_id

            AND stock_totals.product_id =
                products.id

        WHERE products.company_id =
            :company_id
    ";

        $parameters = [
            'company_id' => $companyId,
        ];

        $categoryId =
            (int) (
                $filters['category_id'] ??
                0
            );

        if ($categoryId > 0) {
            $sql .= "
            AND products.category_id =
                :category_id
        ";

            $parameters['category_id'] =
                $categoryId;
        }

        $supplierId =
            (int) (
                $filters['supplier_id'] ??
                0
            );

        if ($supplierId > 0) {
            $sql .= "
            AND products.supplier_id =
                :supplier_id
        ";

            $parameters['supplier_id'] =
                $supplierId;
        }

        $unit = trim(
            (string) (
                $filters['unit'] ?? ''
            )
        );

        if ($unit !== '') {
            $sql .= "
            AND products.unit =
                :unit
        ";

            $parameters['unit'] =
                $unit;
        }

        $status = trim(
            (string) (
                $filters['status'] ?? ''
            )
        );

        if ($status === 'active') {
            $sql .= "
            AND products.is_active = 1
        ";
        } elseif ($status === 'inactive') {
            $sql .= "
            AND products.is_active = 0
        ";
        }

        $minimumPrice =
            $filters['min_price'] ??
            null;

        if (
            $minimumPrice !== null &&
            $minimumPrice !== '' &&
            is_numeric($minimumPrice) &&
            (float) $minimumPrice >= 0
        ) {
            $sql .= "
            AND products.selling_price >=
                :min_price
        ";

            $parameters['min_price'] =
                (float) $minimumPrice;
        }

        $maximumPrice =
            $filters['max_price'] ??
            null;

        if (
            $maximumPrice !== null &&
            $maximumPrice !== '' &&
            is_numeric($maximumPrice) &&
            (float) $maximumPrice >= 0
        ) {
            $sql .= "
            AND products.selling_price <=
                :max_price
        ";

            $parameters['max_price'] =
                (float) $maximumPrice;
        }

        $search = trim(
            (string) (
                $filters['search'] ?? ''
            )
        );

        if ($search !== '') {
            $searchTerm =
                '%' . $search . '%';

            $sql .= "
            AND (
                products.name
                    LIKE :search_name

                OR products.internal_code
                    LIKE :search_internal_code

                OR products.barcode
                    LIKE :search_barcode

                OR categories.name
                    LIKE :search_category

                OR suppliers.name
                    LIKE :search_supplier
            )
        ";

            $parameters['search_name'] =
                $searchTerm;

            $parameters['search_internal_code'] = $searchTerm;

            $parameters['search_barcode'] =
                $searchTerm;

            $parameters['search_category'] =
                $searchTerm;

            $parameters['search_supplier'] =
                $searchTerm;
        }

        $sql .= "
        ORDER BY
            products.name ASC,
            products.id ASC
    ";

        return $this->stream(
            $sql,
            $parameters
        );
    }

    public function stock(
        int $companyId,
        array $filters = []
    ): Generator {
        $sql = "
            SELECT
                stock_levels.*,

                products.name
                    AS product_name,

                products.internal_code,

                products.barcode,

                products.unit
                    AS product_unit,

                products.is_active
                    AS product_is_active,

                categories.id
                    AS category_id,

                categories.name
                    AS category_name,

                warehouses.name
                    AS warehouse_name,

                warehouses.code
                    AS warehouse_code,

                warehouses.is_active
                    AS warehouse_is_active

            FROM stock_levels

            INNER JOIN products
                ON products.id =
                    stock_levels.product_id

                AND products.company_id =
                    stock_levels.company_id

            INNER JOIN warehouses
                ON warehouses.id =
                    stock_levels.warehouse_id

                AND warehouses.company_id =
                    stock_levels.company_id

            LEFT JOIN categories
                ON categories.id =
                    products.category_id

                AND categories.company_id =
                    products.company_id

            WHERE stock_levels.company_id =
                :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        $warehouseId =
            (int) (
                $filters['warehouse_id'] ?? 0
            );

        if ($warehouseId > 0) {
            $sql .= "
                AND stock_levels.warehouse_id =
                    :warehouse_id
            ";

            $parameters['warehouse_id'] =
                $warehouseId;
        }

        $categoryId =
            (int) (
                $filters['category_id'] ?? 0
            );

        if ($categoryId > 0) {
            $sql .= "
                AND products.category_id =
                    :category_id
            ";

            $parameters['category_id'] =
                $categoryId;
        }

        $stockStatus = trim(
            (string) (
                $filters['stock_status'] ?? ''
            )
        );

        if ($stockStatus === 'positive') {
            $sql .= "
                AND stock_levels.quantity > 0
            ";
        }

        if ($stockStatus === 'zero') {
            $sql .= "
                AND stock_levels.quantity = 0
            ";
        }

        if ($stockStatus === 'negative') {
            $sql .= "
                AND stock_levels.quantity < 0
            ";
        }

        $search = trim(
            (string) (
                $filters['search'] ?? ''
            )
        );

        if ($search !== '') {
            $searchTerm =
                '%' . $search . '%';

            $sql .= "
                AND (
                    products.name
                        LIKE :search_product

                    OR products.internal_code
                        LIKE :search_code

                    OR products.barcode
                        LIKE :search_barcode

                    OR categories.name
                        LIKE :search_category

                    OR warehouses.name
                        LIKE :search_warehouse

                    OR warehouses.code
                        LIKE :search_warehouse_code
                )
            ";

            $parameters['search_product'] =
                $searchTerm;

            $parameters['search_code'] =
                $searchTerm;

            $parameters['search_barcode'] =
                $searchTerm;

            $parameters['search_category'] =
                $searchTerm;

            $parameters['search_warehouse'] =
                $searchTerm;

            $parameters['search_warehouse_code'] = $searchTerm;
        }

        $sql .= "
            ORDER BY
                warehouses.name ASC,
                products.name ASC
        ";

        return $this->stream(
            $sql,
            $parameters
        );
    }

    public function transactions(
        int $companyId,
        array $filters = []
    ): Generator {
        $sql = "
            SELECT
                warehouse_transactions.*,

                products.name
                    AS product_name,

                products.internal_code,

                products.unit
                    AS product_unit,

                from_warehouse.name
                    AS from_warehouse_name,

                from_warehouse.code
                    AS from_warehouse_code,

                to_warehouse.name
                    AS to_warehouse_name,

                to_warehouse.code
                    AS to_warehouse_code,

                users.name
                    AS user_name

            FROM warehouse_transactions

            INNER JOIN products
                ON products.id =
                    warehouse_transactions.product_id

                AND products.company_id =
                    warehouse_transactions.company_id

            LEFT JOIN warehouses
                AS from_warehouse

                ON from_warehouse.id =
                    warehouse_transactions.from_warehouse_id

                AND from_warehouse.company_id =
                    warehouse_transactions.company_id

            LEFT JOIN warehouses
                AS to_warehouse

                ON to_warehouse.id =
                    warehouse_transactions.to_warehouse_id

                AND to_warehouse.company_id =
                    warehouse_transactions.company_id

            INNER JOIN users
                ON users.id =
                    warehouse_transactions.user_id

            WHERE warehouse_transactions.company_id =
                :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        $productId =
            (int) (
                $filters['product_id'] ?? 0
            );

        if ($productId > 0) {
            $sql .= "
                AND warehouse_transactions.product_id =
                    :product_id
            ";

            $parameters['product_id'] =
                $productId;
        }

        $warehouseId =
            (int) (
                $filters['warehouse_id'] ?? 0
            );

        if ($warehouseId > 0) {
            $sql .= "
                AND (
                    warehouse_transactions.from_warehouse_id =
                        :from_warehouse_id

                    OR warehouse_transactions.to_warehouse_id =
                        :to_warehouse_id
                )
            ";

            $parameters['from_warehouse_id'] = $warehouseId;

            $parameters['to_warehouse_id'] = $warehouseId;
        }

        $type = trim(
            (string) (
                $filters['type'] ?? ''
            )
        );

        if ($type !== '') {
            $sql .= "
                AND warehouse_transactions.type =
                    :transaction_type
            ";

            $parameters['transaction_type'] = $type;
        }

        $dateFrom = trim(
            (string) (
                $filters['date_from'] ?? ''
            )
        );

        if ($dateFrom !== '') {
            $sql .= "
                AND DATE(
                    warehouse_transactions.created_at
                ) >= :date_from
            ";

            $parameters['date_from'] =
                $dateFrom;
        }

        $dateTo = trim(
            (string) (
                $filters['date_to'] ?? ''
            )
        );

        if ($dateTo !== '') {
            $sql .= "
                AND DATE(
                    warehouse_transactions.created_at
                ) <= :date_to
            ";

            $parameters['date_to'] =
                $dateTo;
        }

        $search = trim(
            (string) (
                $filters['search'] ?? ''
            )
        );

        if ($search !== '') {
            $searchTerm =
                '%' . $search . '%';

            $sql .= "
                AND (
                    products.name
                        LIKE :search_product

                    OR products.internal_code
                        LIKE :search_code

                    OR warehouse_transactions.reference_type
                        LIKE :search_reference_type

                    OR warehouse_transactions.reference_id
                        LIKE :search_reference_id

                    OR warehouse_transactions.note
                        LIKE :search_note

                    OR users.name
                        LIKE :search_user
                )
            ";

            $parameters['search_product'] =
                $searchTerm;

            $parameters['search_code'] =
                $searchTerm;

            $parameters['search_reference_type'] = $searchTerm;

            $parameters['search_reference_id'] = $searchTerm;

            $parameters['search_note'] =
                $searchTerm;

            $parameters['search_user'] =
                $searchTerm;
        }

        $sql .= "
            ORDER BY
                warehouse_transactions.created_at DESC,
                warehouse_transactions.id DESC
        ";

        return $this->stream(
            $sql,
            $parameters
        );
    }

    public function sales(
        int $companyId,
        array $filters = []
    ): Generator {
        $sql = "
        SELECT
            sales.*,

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

            warehouses.name
                AS warehouse_name,

            warehouses.code
                AS warehouse_code,

            users.name
                AS created_by_user_name,

            COALESCE(
                item_totals.item_count,
                0
            ) AS item_count,

            COALESCE(
                item_totals.total_quantity,
                0
            ) AS total_quantity,

            item_totals.known_cogs,

            item_totals.known_gross_profit,

            COALESCE(
                item_totals.uncosted_item_count,
                0
            ) AS uncosted_item_count

        FROM sales

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

        LEFT JOIN users
            ON users.id =
                sales.user_id

            AND users.company_id =
                sales.company_id

        LEFT JOIN (
            SELECT
                company_id,
                sale_id,

                COUNT(*)
                    AS item_count,

                SUM(quantity)
                    AS total_quantity,

                SUM(
                    total_cost
                ) AS known_cogs,

                SUM(
                    gross_profit
                ) AS known_gross_profit,

                SUM(
                    total_cost IS NULL
                ) AS uncosted_item_count

            FROM sale_items

            GROUP BY
                company_id,
                sale_id
        ) AS item_totals
            ON item_totals.company_id =
                sales.company_id

            AND item_totals.sale_id =
                sales.id

        WHERE sales.company_id =
            :company_id
    ";

        $parameters = [
            'company_id' => $companyId,
        ];

        $this->appendDocumentFilters(
            $sql,
            $parameters,
            'sales',
            'sale_date',
            $filters
        );

        $clientId =
            (int) (
                $filters['client_id'] ?? 0
            );

        if ($clientId > 0) {
            $sql .= "
            AND sales.client_id =
                :client_id
        ";

            $parameters['client_id'] =
                $clientId;
        }

        $search = trim(
            (string) (
                $filters['search'] ?? ''
            )
        );

        if ($search !== '') {
            $searchTerm =
                '%' . $search . '%';

            $sql .= "
            AND (
                sales.sale_number
                    LIKE :search_number

                OR clients.name
                    LIKE :search_client_name

                OR clients.company_name
                    LIKE :search_client_company

                OR warehouses.name
                    LIKE :search_warehouse
            )
        ";

            $parameters['search_number'] =
                $searchTerm;

            $parameters['search_client_name'] =
                $searchTerm;

            $parameters['search_client_company'] =
                $searchTerm;

            $parameters['search_warehouse'] =
                $searchTerm;
        }

        $sql .= "
        ORDER BY
            sales.sale_date DESC,
            sales.id DESC
    ";

        return $this->stream(
            $sql,
            $parameters
        );
    }

    public function purchases(
        int $companyId,
        array $filters = []
    ): Generator {
        $sql = "
        SELECT
            purchases.*,

            COALESCE(
                NULLIF(
                    suppliers.company_name,
                    ''
                ),
                NULLIF(
                    suppliers.name,
                    ''
                ),
                'No Supplier'
            ) AS supplier_name,

            warehouses.name
                AS warehouse_name,

            warehouses.code
                AS warehouse_code,

            users.name
                AS created_by_user_name,

            COALESCE(
                item_totals.item_count,
                0
            ) AS item_count,

            COALESCE(
                item_totals.total_quantity,
                0
            ) AS total_quantity,

            item_totals.inventory_total_cost

        FROM purchases

        INNER JOIN warehouses
            ON warehouses.id =
                purchases.warehouse_id

            AND warehouses.company_id =
                purchases.company_id

        LEFT JOIN suppliers
            ON suppliers.id =
                purchases.supplier_id

            AND suppliers.company_id =
                purchases.company_id

        LEFT JOIN users
            ON users.id =
                purchases.user_id

            AND users.company_id =
                purchases.company_id

        LEFT JOIN (
            SELECT
                company_id,
                purchase_id,

                COUNT(*)
                    AS item_count,

                SUM(quantity)
                    AS total_quantity,

                SUM(
                    inventory_total_cost
                ) AS inventory_total_cost

            FROM purchase_items

            GROUP BY
                company_id,
                purchase_id
        ) AS item_totals
            ON item_totals.company_id =
                purchases.company_id

            AND item_totals.purchase_id =
                purchases.id

        WHERE purchases.company_id =
            :company_id
    ";

        $parameters = [
            'company_id' => $companyId,
        ];

        $this->appendDocumentFilters(
            $sql,
            $parameters,
            'purchases',
            'purchase_date',
            $filters
        );

        $supplierId =
            (int) (
                $filters['supplier_id'] ?? 0
            );

        if ($supplierId > 0) {
            $sql .= "
            AND purchases.supplier_id =
                :supplier_id
        ";

            $parameters['supplier_id'] =
                $supplierId;
        }

        $search = trim(
            (string) (
                $filters['search'] ?? ''
            )
        );

        if ($search !== '') {
            $searchTerm =
                '%' . $search . '%';

            $sql .= "
            AND (
                purchases.purchase_number
                    LIKE :search_number

                OR suppliers.name
                    LIKE :search_supplier_name

                OR suppliers.company_name
                    LIKE :search_supplier_company

                OR warehouses.name
                    LIKE :search_warehouse
            )
        ";

            $parameters['search_number'] =
                $searchTerm;

            $parameters['search_supplier_name'] =
                $searchTerm;

            $parameters['search_supplier_company'] =
                $searchTerm;

            $parameters['search_warehouse'] =
                $searchTerm;
        }

        $sql .= "
        ORDER BY
            purchases.purchase_date DESC,
            purchases.id DESC
    ";

        return $this->stream(
            $sql,
            $parameters
        );
    }

    public function invoices(
        int $companyId,
        array $filters = []
    ): Generator {
        $creditExpression = "
        COALESCE(
            credit_totals.credit_total,
            0
        )
    ";

        $paidExpression = "
        COALESCE(
            payment_totals.paid_amount,
            0
        )
    ";

        $adjustedExpression = "
        GREATEST(
            invoices.total_amount -
            {$creditExpression},
            0
        )
    ";

        $balanceExpression = "
        CASE
            WHEN invoices.status =
                'cancelled'
                THEN 0

            ELSE GREATEST(
                {$adjustedExpression} -
                {$paidExpression},
                0
            )
        END
    ";

        $sql = "
        SELECT
            invoices.id,

            COALESCE(
                NULLIF(
                    invoices.invoice_number,
                    ''
                ),
                CONCAT(
                    'DRAFT-',
                    invoices.id
                )
            ) AS invoice_number,

            invoices.invoice_date
                AS issued_at,

            invoices.due_date,

            COALESCE(
                NULLIF(
                    invoices.client_legal_name,
                    ''
                ),
                NULLIF(
                    invoices.client_display_name,
                    ''
                ),
                'No Client'
            ) AS client_name,

            sales.sale_number,

            invoices.document_type,
            invoices.status,
            invoices.currency,

            invoices.subtotal
                AS subtotal_amount,

            invoices.discount_amount,

            (
                invoices.subtotal -
                invoices.discount_amount
            ) AS net_amount,

            invoices.tax_amount,
            invoices.total_amount,

            {$creditExpression}
                AS credit_total,

            {$adjustedExpression}
                AS adjusted_total,

            {$paidExpression}
                AS paid_amount,

            {$balanceExpression}
                AS balance_due,

            users.name
                AS created_by_user_name

        FROM invoices

        LEFT JOIN users
            ON users.id =
                invoices.created_by_user_id

        LEFT JOIN sales
            ON sales.id =
                invoices.sale_id

            AND sales.company_id =
                invoices.company_id

        LEFT JOIN (
            SELECT
                company_id,
                related_invoice_id,

                SUM(
                    ABS(total_amount)
                ) AS credit_total

            FROM invoices

            WHERE document_type =
                'credit_note'

            AND status =
                'issued'

            GROUP BY
                company_id,
                related_invoice_id
        ) AS credit_totals
            ON credit_totals.company_id =
                invoices.company_id

            AND credit_totals.related_invoice_id =
                invoices.id

        LEFT JOIN (
            SELECT
                company_id,
                invoice_id,

                SUM(amount)
                    AS paid_amount

            FROM payments

            WHERE status =
                'completed'

            GROUP BY
                company_id,
                invoice_id
        ) AS payment_totals
            ON payment_totals.company_id =
                invoices.company_id

            AND payment_totals.invoice_id =
                invoices.id

        WHERE invoices.company_id =
            :company_id
    ";

        $parameters = [
            'company_id' => $companyId,
        ];

        $search = trim(
            (string) (
                $filters['search'] ?? ''
            )
        );

        if ($search !== '') {
            $searchTerm =
                '%' . $search . '%';

            $sql .= "
            AND (
                invoices.invoice_number
                    LIKE :search_number

                OR invoices.client_display_name
                    LIKE :search_display_name

                OR invoices.client_legal_name
                    LIKE :search_legal_name

                OR invoices.client_eik
                    LIKE :search_eik

                OR invoices.client_vat_number
                    LIKE :search_vat
            )
        ";

            $parameters['search_number'] =
                $searchTerm;

            $parameters['search_display_name'] =
                $searchTerm;

            $parameters['search_legal_name'] =
                $searchTerm;

            $parameters['search_eik'] =
                $searchTerm;

            $parameters['search_vat'] =
                $searchTerm;
        }

        $dueFilter = trim(
            (string) (
                $filters['due_filter'] ??
                'all'
            )
        );

        if ($dueFilter === 'overdue') {
            $sql .= "
            AND invoices.document_type =
                'invoice'

            AND invoices.status =
                'issued'

            AND invoices.due_date
                IS NOT NULL

            AND invoices.due_date <
                CURDATE()

            AND {$balanceExpression} >
                0.009
        ";
        } elseif ($dueFilter === 'due_today') {
            $sql .= "
            AND invoices.document_type =
                'invoice'

            AND invoices.status =
                'issued'

            AND invoices.due_date =
                CURDATE()

            AND {$balanceExpression} >
                0.009
        ";
        } elseif ($dueFilter === 'due_soon') {
            $sql .= "
            AND invoices.document_type =
                'invoice'

            AND invoices.status =
                'issued'

            AND invoices.due_date >
                CURDATE()

            AND invoices.due_date <=
                DATE_ADD(
                    CURDATE(),
                    INTERVAL 7 DAY
                )

            AND {$balanceExpression} >
                0.009
        ";
        } elseif ($dueFilter === 'unpaid') {
            $sql .= "
            AND invoices.document_type =
                'invoice'

            AND invoices.status =
                'issued'

            AND {$paidExpression} <=
                0.009

            AND {$balanceExpression} >
                0.009
        ";
        } elseif (
            $dueFilter === 'partially_paid'
        ) {
            $sql .= "
            AND invoices.document_type =
                'invoice'

            AND invoices.status =
                'issued'

            AND {$paidExpression} >
                0.009

            AND {$balanceExpression} >
                0.009
        ";
        } elseif ($dueFilter === 'paid') {
            $sql .= "
            AND invoices.document_type =
                'invoice'

            AND invoices.status =
                'issued'

            AND {$balanceExpression} <=
                0.009
        ";
        } elseif (
            $dueFilter === 'no_due_date'
        ) {
            $sql .= "
            AND invoices.document_type =
                'invoice'

            AND invoices.status =
                'issued'

            AND invoices.due_date
                IS NULL

            AND {$balanceExpression} >
                0.009
        ";
        }

        $sql .= "
        ORDER BY
            invoices.invoice_date DESC,
            invoices.id DESC
    ";

        return $this->stream(
            $sql,
            $parameters
        );
    }

    private function appendDocumentFilters(
        string &$sql,
        array &$parameters,
        string $table,
        string $dateColumn,
        array $filters
    ): void {
        $status = trim(
            (string) (
                $filters['status'] ?? ''
            )
        );

        if ($status !== '') {
            $sql .= "
                AND {$table}.status =
                    :status
            ";

            $parameters['status'] =
                $status;
        }

        $warehouseId =
            (int) (
                $filters['warehouse_id'] ?? 0
            );

        if ($warehouseId > 0) {
            $sql .= "
                AND {$table}.warehouse_id =
                    :warehouse_id
            ";

            $parameters['warehouse_id'] =
                $warehouseId;
        }

        $dateFrom = trim(
            (string) (
                $filters['date_from'] ?? ''
            )
        );

        if ($dateFrom !== '') {
            $sql .= "
                AND {$table}.{$dateColumn}
                    >= :date_from
            ";

            $parameters['date_from'] =
                $dateFrom;
        }

        $dateTo = trim(
            (string) (
                $filters['date_to'] ?? ''
            )
        );

        if ($dateTo !== '') {
            $sql .= "
                AND {$table}.{$dateColumn}
                    <= :date_to
            ";

            $parameters['date_to'] =
                $dateTo;
        }
    }

    private function stream(
        string $sql,
        array $parameters
    ): Generator {
        $statement =
            $this->db->prepare($sql);

        $statement->execute(
            $parameters
        );

        while (
            ($row = $statement->fetch())
            !== false
        ) {
            yield $row;
        }
    }
}
