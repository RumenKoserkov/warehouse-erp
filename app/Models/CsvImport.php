<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use InvalidArgumentException;

class CsvImport extends Model
{
    private const IMPORT_TABLES = [
        'products',
        'clients',
        'suppliers',
    ];

    private array $columnCache = [];

    public function createBatch(array $data): int
    {
        $statement = $this->db->prepare(
            "
            INSERT INTO csv_import_batches
            (
                company_id,
                import_type,
                import_mode,
                original_filename,
                delimiter_name,
                status,
                validate_only,
                created_by_user_id,
                started_at
            )
            VALUES
            (
                :company_id,
                :import_type,
                :import_mode,
                :original_filename,
                NULL,
                :status,
                :validate_only,
                :created_by_user_id,
                NOW()
            )
            "
        );

        $statement->execute([
            'company_id' =>
                $data['company_id'],

            'import_type' =>
                $data['import_type'],

            'import_mode' =>
                $data['import_mode'],

            'original_filename' =>
                $data['original_filename'],

            'status' =>
                $data['status'],

            'validate_only' =>
                $data['validate_only'],

            'created_by_user_id' =>
                $data['created_by_user_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function setDelimiter(
        int $batchId,
        int $companyId,
        string $delimiterName
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE csv_import_batches
            SET
                delimiter_name =
                    :delimiter_name,

                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
            "
        );

        return $statement->execute([
            'delimiter_name' =>
                $delimiterName,

            'id' => $batchId,
            'company_id' => $companyId,
        ]);
    }

    public function finishBatch(
        int $batchId,
        int $companyId,
        string $status,
        int $totalRows,
        int $successfulRows,
        int $failedRows,
        ?string $errorMessage = null
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE csv_import_batches
            SET
                status = :status,

                total_rows =
                    :total_rows,

                successful_rows =
                    :successful_rows,

                failed_rows =
                    :failed_rows,

                error_message =
                    :error_message,

                completed_at = NOW(),
                updated_at = NOW()

            WHERE id = :id
            AND company_id = :company_id
            "
        );

        return $statement->execute([
            'status' => $status,
            'total_rows' => $totalRows,
            'successful_rows' =>
                $successfulRows,
            'failed_rows' => $failedRows,
            'error_message' =>
                $errorMessage,
            'id' => $batchId,
            'company_id' => $companyId,
        ]);
    }

    public function addError(
        int $batchId,
        int $companyId,
        int $rowNumber,
        ?string $columnName,
        string $errorMessage,
        array $rowData
    ): int {
        $encodedRow = json_encode(
            $rowData,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        $statement = $this->db->prepare(
            "
            INSERT INTO csv_import_errors
            (
                batch_id,
                company_id,
                `row_number`,
                column_name,
                error_message,
                row_data
            )
            VALUES
            (
                :batch_id,
                :company_id,
                :row_number,
                :column_name,
                :error_message,
                :row_data
            )
            "
        );

        $statement->execute([
            'batch_id' => $batchId,
            'company_id' => $companyId,
            'row_number' => $rowNumber,
            'column_name' => $columnName,
            'error_message' =>
                mb_substr(
                    $errorMessage,
                    0,
                    1000
                ),
            'row_data' =>
                $encodedRow !== false
                    ? $encodedRow
                    : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function recentByCompany(
        int $companyId,
        int $limit = 50
    ): array {
        $limit = max(
            1,
            min($limit, 100)
        );

        $statement = $this->db->prepare(
            "
            SELECT
                csv_import_batches.*,

                users.name
                    AS created_by_user_name

            FROM csv_import_batches

            INNER JOIN users
                ON users.id =
                    csv_import_batches.created_by_user_id

            WHERE csv_import_batches.company_id =
                :company_id

            ORDER BY
                csv_import_batches.id DESC

            LIMIT {$limit}
            "
        );

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function findBatch(
        int $batchId,
        int $companyId
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT
                csv_import_batches.*,

                users.name
                    AS created_by_user_name

            FROM csv_import_batches

            INNER JOIN users
                ON users.id =
                    csv_import_batches.created_by_user_id

            WHERE csv_import_batches.id =
                :id

            AND csv_import_batches.company_id =
                :company_id

            LIMIT 1
            "
        );

        $statement->execute([
            'id' => $batchId,
            'company_id' => $companyId,
        ]);

        $batch = $statement->fetch();

        return $batch === false
            ? null
            : $batch;
    }

    public function errorsByBatch(
        int $batchId,
        int $companyId
    ): array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM csv_import_errors
            WHERE batch_id = :batch_id
            AND company_id = :company_id
            ORDER BY
                `row_number` ASC,
                id ASC
            "
        );

        $statement->execute([
            'batch_id' => $batchId,
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function tableColumns(
        string $table
    ): array {
        $this->assertImportTable($table);

        if (
            isset(
                $this->columnCache[$table]
            )
        ) {
            return $this->columnCache[
                $table
            ];
        }

        $statement = $this->db->query(
            "SHOW COLUMNS FROM `{$table}`"
        );

        $columns = [];

        foreach (
            $statement->fetchAll() as $row
        ) {
            $columns[] =
                (string) $row['Field'];
        }

        $this->columnCache[$table] =
            $columns;

        return $columns;
    }

    public function findByField(
        string $table,
        int $companyId,
        string $field,
        string $value
    ): ?array {
        $this->assertImportTable($table);

        $columns =
            $this->tableColumns($table);

        if (
            !in_array(
                $field,
                $columns,
                true
            )
        ) {
            return null;
        }

        $statement = $this->db->prepare(
            "
            SELECT *
            FROM `{$table}`
            WHERE company_id = :company_id
            AND `{$field}` = :field_value
            LIMIT 1
            "
        );

        $statement->execute([
            'company_id' => $companyId,
            'field_value' => $value,
        ]);

        $row = $statement->fetch();

        return $row === false
            ? null
            : $row;
    }

    public function insertRecord(
        string $table,
        array $data
    ): int {
        $this->assertImportTable($table);

        $data =
            $this->filterRecordData(
                $table,
                $data
            );

        if ($data === []) {
            throw new InvalidArgumentException(
                'No supported columns were provided for import.'
            );
        }

        $columns = array_keys($data);

        $quotedColumns = array_map(
            static fn (
                string $column
            ): string =>
                "`{$column}`",
            $columns
        );

        $placeholders = array_map(
            static fn (
                string $column
            ): string =>
                ':' . $column,
            $columns
        );

        $sql = "
            INSERT INTO `{$table}`
            (
                " .
                implode(
                    ', ',
                    $quotedColumns
                ) .
                "
            )
            VALUES
            (
                " .
                implode(
                    ', ',
                    $placeholders
                ) .
                "
            )
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function updateRecord(
        string $table,
        int $id,
        int $companyId,
        array $data
    ): bool {
        $this->assertImportTable($table);

        unset(
            $data['id'],
            $data['company_id'],
            $data['created_at']
        );

        $data =
            $this->filterRecordData(
                $table,
                $data
            );

        if ($data === []) {
            return true;
        }

        $assignments = [];

        foreach (
            array_keys($data) as $column
        ) {
            $assignments[] =
                "`{$column}` = :{$column}";
        }

        $columns =
            $this->tableColumns($table);

        if (
            in_array(
                'updated_at',
                $columns,
                true
            )
        ) {
            $assignments[] =
                '`updated_at` = NOW()';
        }

        $sql = "
            UPDATE `{$table}`
            SET " .
            implode(
                ', ',
                $assignments
            ) .
            "
            WHERE id = :record_id
            AND company_id = :record_company_id
        ";

        $data['record_id'] = $id;

        $data['record_company_id'] =
            $companyId;

        $statement =
            $this->db->prepare($sql);

        return $statement->execute($data);
    }

    public function findCategoryByName(
        int $companyId,
        string $name
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM categories
            WHERE company_id = :company_id
            AND name = :name
            LIMIT 1
            "
        );

        $statement->execute([
            'company_id' => $companyId,
            'name' => $name,
        ]);

        $category = $statement->fetch();

        return $category === false
            ? null
            : $category;
    }

    public function findProductByCode(
        int $companyId,
        string $internalCode,
        bool $forUpdate = false
    ): ?array {
        $sql = "
            SELECT *
            FROM products
            WHERE company_id = :company_id
            AND internal_code =
                :internal_code
            LIMIT 1
        ";

        if ($forUpdate) {
            $sql .= " FOR UPDATE";
        }

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute([
            'company_id' => $companyId,
            'internal_code' =>
                $internalCode,
        ]);

        $product = $statement->fetch();

        return $product === false
            ? null
            : $product;
    }

    public function findWarehouseByCode(
        int $companyId,
        string $code
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM warehouses
            WHERE company_id = :company_id
            AND code = :code
            LIMIT 1
            "
        );

        $statement->execute([
            'company_id' => $companyId,
            'code' => $code,
        ]);

        $warehouse =
            $statement->fetch();

        return $warehouse === false
            ? null
            : $warehouse;
    }

    public function stockState(
        int $companyId,
        int $productId,
        int $warehouseId
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM stock_levels
            WHERE company_id = :company_id
            AND product_id = :product_id
            AND warehouse_id =
                :warehouse_id
            LIMIT 1
            "
        );

        $statement->execute([
            'company_id' => $companyId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);

        $stockLevel =
            $statement->fetch();

        return $stockLevel === false
            ? null
            : $stockLevel;
    }

    public function movementCount(
        int $companyId,
        int $productId,
        int $warehouseId
    ): int {
        $statement = $this->db->prepare(
            "
            SELECT COUNT(*)
            FROM warehouse_transactions
            WHERE company_id = :company_id
            AND product_id = :product_id
            AND (
                from_warehouse_id =
                    :from_warehouse_id

                OR to_warehouse_id =
                    :to_warehouse_id
            )
            "
        );

        $statement->execute([
            'company_id' => $companyId,
            'product_id' => $productId,
            'from_warehouse_id' =>
                $warehouseId,
            'to_warehouse_id' =>
                $warehouseId,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function updateProductLastCost(
        int $productId,
        int $companyId,
        float $unitCost
    ): void {
        $columns =
            $this->tableColumns(
                'products'
            );

        if (
            !in_array(
                'last_purchase_cost',
                $columns,
                true
            )
        ) {
            return;
        }

        $set = "
            last_purchase_cost =
                :last_purchase_cost
        ";

        if (
            in_array(
                'last_purchase_at',
                $columns,
                true
            )
        ) {
            $set .= ",
                last_purchase_at = NOW()
            ";
        }

        $statement = $this->db->prepare(
            "
            UPDATE products
            SET {$set}
            WHERE id = :id
            AND company_id = :company_id
            "
        );

        $statement->execute([
            'last_purchase_cost' =>
                round($unitCost, 4),

            'id' => $productId,
            'company_id' => $companyId,
        ]);
    }

    private function filterRecordData(
        string $table,
        array $data
    ): array {
        $columns =
            $this->tableColumns($table);

        $filtered = [];

        foreach ($data as $column => $value) {
            if (
                !is_string($column) ||
                !in_array(
                    $column,
                    $columns,
                    true
                )
            ) {
                continue;
            }

            $filtered[$column] = $value;
        }

        return $filtered;
    }

    private function assertImportTable(
        string $table
    ): void {
        if (
            !in_array(
                $table,
                self::IMPORT_TABLES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported import table.'
            );
        }
    }
}