<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\CsvImportRowException;
use App\Core\Database;
use App\Core\Environment;
use App\Models\CsvImport;
use App\Models\WarehouseTransaction;
use Exception;
use PDO;
use Throwable;

class CsvImportService
{
    private PDO $db;

    private CsvImport $importModel;

    private WarehouseTransaction $transactionModel;

    private InventoryCostService $inventoryCostService;

    private AuditLogService $auditLogService;

    private array $seenOpeningStockPairs = [];

    public function __construct()
    {
        $this->db = Database::getConnection();

        $this->importModel = new CsvImport();

        $this->transactionModel =
            new WarehouseTransaction();

        $this->inventoryCostService =
            new InventoryCostService();

        $this->auditLogService =
            new AuditLogService();
    }

    public function importTypes(): array
    {
        return [
            'products' => 'Products',
            'clients' => 'Clients',
            'suppliers' => 'Suppliers',
            'opening_stock' => 'Opening Stock',
        ];
    }

    public function importModes(): array
    {
        return [
            'create_only' =>
            'Create New Records Only',

            'upsert' =>
            'Create or Update Existing',
        ];
    }

    public function templates(): array
    {
        return [
            'products' => [
                'headers' => [
                    'name',
                    'internal_code',
                    'barcode',
                    'category_name',
                    'unit',
                    'sale_price',
                    'purchase_price',
                    'vat_rate',
                    'minimum_stock',
                    'is_active',
                    'description',
                ],

                'rows' => [
                    [
                        'Кисело мляко 3.6%',
                        'MILK-001',
                        '3800000000011',
                        'Dairy',
                        'pcs',
                        '2.40',
                        '1.60',
                        '20',
                        '5',
                        '1',
                        'Example product',
                    ],
                ],
            ],

            'clients' => [
                'headers' => [
                    'name',
                    'company_name',
                    'eik',
                    'vat_number',
                    'email',
                    'phone',
                    'contact_person',
                    'address',
                    'city',
                    'postal_code',
                    'country',
                    'is_active',
                    'notes',
                ],

                'rows' => [
                    [
                        'Иван Иванов',
                        'Клиент ООД',
                        '123456789',
                        'BG123456789',
                        'client@example.com',
                        '+359888000000',
                        'Иван Иванов',
                        'бул. България 1',
                        'София',
                        '1000',
                        'Bulgaria',
                        '1',
                        'Example client',
                    ],
                ],
            ],

            'suppliers' => [
                'headers' => [
                    'name',
                    'company_name',
                    'eik',
                    'vat_number',
                    'email',
                    'phone',
                    'contact_person',
                    'address',
                    'city',
                    'postal_code',
                    'country',
                    'is_active',
                    'notes',
                ],

                'rows' => [
                    [
                        'Доставчик ООД',
                        'Доставчик ООД',
                        '987654321',
                        'BG987654321',
                        'supplier@example.com',
                        '+359888111111',
                        'Петър Петров',
                        'ул. Индустриална 5',
                        'София',
                        '1000',
                        'Bulgaria',
                        '1',
                        'Example supplier',
                    ],
                ],
            ],

            'opening_stock' => [
                'headers' => [
                    'warehouse_code',
                    'product_internal_code',
                    'quantity',
                    'unit_cost',
                    'note',
                ],

                'rows' => [
                    [
                        'MAIN',
                        'MILK-001',
                        '100',
                        '1.6000',
                        'Opening inventory',
                    ],
                ],
            ],
        ];
    }

    public function template(string $type): array
    {
        $templates = $this->templates();

        if (!isset($templates[$type])) {
            throw new Exception(
                'Invalid CSV template type.'
            );
        }

        return $templates[$type];
    }

    public function process(
        array $file,
        string $type,
        string $mode,
        bool $validateOnly,
        int $companyId,
        int $userId
    ): array {
        if (
            !array_key_exists(
                $type,
                $this->importTypes()
            )
        ) {
            return [
                'success' => false,
                'batch_id' => null,
                'error' => 'Invalid import type.',
            ];
        }

        if (
            !array_key_exists(
                $mode,
                $this->importModes()
            )
        ) {
            $mode = 'create_only';
        }

        if ($type === 'opening_stock') {
            $mode = 'create_only';
        }

        try {
            $upload = $this->validateUpload(
                $file
            );
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'batch_id' => null,
                'error' =>
                $exception->getMessage(),
            ];
        }

        $batchId =
            $this->importModel->createBatch([
                'company_id' =>
                $companyId,

                'import_type' =>
                $type,

                'import_mode' =>
                $mode,

                'original_filename' =>
                $upload['original_filename'],

                'status' =>
                $validateOnly
                    ? 'validating'
                    : 'processing',

                'validate_only' =>
                $validateOnly ? 1 : 0,

                'created_by_user_id' =>
                $userId,
            ]);

        $handle = fopen(
            $upload['tmp_name'],
            'rb'
        );

        if ($handle === false) {
            $this->failBatch(
                $batchId,
                $companyId,
                'CSV file could not be opened.'
            );

            return [
                'success' => false,
                'batch_id' => $batchId,
                'error' =>
                'CSV file could not be opened.',
            ];
        }

        $totalRows = 0;
        $successfulRows = 0;
        $failedRows = 0;

        $this->seenOpeningStockPairs = [];

        try {
            [
                $delimiter,
                $delimiterName,
            ] = $this->detectDelimiter(
                $handle
            );

            $this->importModel->setDelimiter(
                $batchId,
                $companyId,
                $delimiterName
            );

            $headers = $this->readHeaders(
                $handle,
                $delimiter,
                $type
            );

            $maxRows = max(
                1,
                Environment::integer(
                    'CSV_IMPORT_MAX_ROWS',
                    10000
                )
            );

            $rowNumber = 1;

            while (
                (
                    $cells = fgetcsv(
                        $handle,
                        0,
                        $delimiter,
                        '"',
                        ''
                    )
                ) !== false
            ) {
                $rowNumber++;

                $cells = $this->normalizeCells(
                    $cells
                );

                if ($this->isBlankRow($cells)) {
                    continue;
                }

                $totalRows++;

                if ($totalRows > $maxRows) {
                    throw new Exception(
                        'CSV row limit exceeded. Maximum allowed rows: ' .
                            $maxRows .
                            '.'
                    );
                }

                $row = [];

                try {
                    $row = $this->combineRow(
                        $headers,
                        $cells
                    );

                    if (!$validateOnly) {
                        $this->db
                            ->beginTransaction();
                    }

                    $this->processRow(
                        $type,
                        $mode,
                        $row,
                        $companyId,
                        $userId,
                        $batchId,
                        !$validateOnly
                    );

                    if (
                        !$validateOnly &&
                        $this->db->inTransaction()
                    ) {
                        $this->db->commit();
                    }

                    $successfulRows++;
                } catch (Throwable $exception) {
                    if (
                        $this->db->inTransaction()
                    ) {
                        $this->db->rollBack();
                    }

                    $failedRows++;

                    $columnName =
                        $exception instanceof
                        CsvImportRowException
                        ? $exception
                        ->columnName()
                        : null;

                    $this->importModel->addError(
                        $batchId,
                        $companyId,
                        $rowNumber,
                        $columnName,
                        $exception->getMessage(),
                        $row !== []
                            ? $row
                            : [
                                'raw_cells' =>
                                $cells,
                            ]
                    );
                }
            }

            $status =
                $validateOnly
                ? (
                    $failedRows > 0
                    ? 'validated_with_errors'
                    : 'validated'
                )
                : (
                    $failedRows > 0
                    ? 'completed_with_errors'
                    : 'completed'
                );

            $this->importModel->finishBatch(
                $batchId,
                $companyId,
                $status,
                $totalRows,
                $successfulRows,
                $failedRows
            );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'import',
                'csv_import',
                $batchId,
                (
                    $validateOnly
                    ? 'Validated CSV import '
                    : 'Processed CSV import '
                ) .
                    '#' .
                    $batchId .
                    '. Type: ' .
                    $type .
                    ', successful rows: ' .
                    $successfulRows .
                    ', failed rows: ' .
                    $failedRows .
                    '.'
            );

            return [
                'success' => true,
                'batch_id' => $batchId,
                'status' => $status,
                'total_rows' => $totalRows,
                'successful_rows' =>
                $successfulRows,
                'failed_rows' =>
                $failedRows,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->importModel->finishBatch(
                $batchId,
                $companyId,
                'failed',
                $totalRows,
                $successfulRows,
                $failedRows,
                $exception->getMessage()
            );

            return [
                'success' => false,
                'batch_id' => $batchId,
                'error' =>
                $exception->getMessage(),
            ];
        } finally {
            fclose($handle);
        }
    }

    private function processRow(
        string $type,
        string $mode,
        array $row,
        int $companyId,
        int $userId,
        int $batchId,
        bool $apply
    ): void {
        match ($type) {
            'products' =>
            $this->processProduct(
                $row,
                $mode,
                $companyId,
                $apply
            ),

            'clients' =>
            $this->processContact(
                'clients',
                $row,
                $mode,
                $companyId,
                $apply
            ),

            'suppliers' =>
            $this->processContact(
                'suppliers',
                $row,
                $mode,
                $companyId,
                $apply
            ),

            'opening_stock' =>
            $this->processOpeningStock(
                $row,
                $companyId,
                $userId,
                $batchId,
                $apply
            ),

            default =>
            throw new Exception(
                'Unsupported import type.'
            ),
        };
    }

    private function processProduct(
        array $row,
        string $mode,
        int $companyId,
        bool $apply
    ): void {
        $name = $this->requiredText(
            $row,
            'name',
            255
        );

        $internalCode =
            $this->requiredText(
                $row,
                'internal_code',
                100
            );

        $unit = $this->requiredText(
            $row,
            'unit',
            30
        );

        $barcode = $this->optionalText(
            $row,
            'barcode',
            100
        );

        $categoryName =
            $this->requiredText(
                $row,
                'category_name',
                255
            );

        $salePrice = $this->decimal(
            $row['sale_price'] ?? '',
            'sale_price',
            false,
            4
        );

        $purchasePrice =
            $this->decimal(
                $row['purchase_price'] ?? '0',
                'purchase_price',
                true,
                4
            );

        $vatRate = $this->decimal(
            $row['vat_rate'] ?? '20',
            'vat_rate',
            true,
            2
        );

        if ($vatRate > 100) {
            $this->rowError(
                'vat_rate',
                'VAT rate cannot exceed 100%.'
            );
        }

        $minimumStock =
            $this->decimal(
                $row['minimum_stock'] ?? '0',
                'minimum_stock',
                true,
                3
            );

        $isActive =
            $this->booleanValue(
                $row['is_active'] ?? '1',
                'is_active'
            );

        $description =
            $this->optionalText(
                $row,
                'description',
                5000
            );

        $category =
            $this->importModel
            ->findCategoryByName(
                $companyId,
                $categoryName
            );

        if ($category === null) {
            $this->rowError(
                'category_name',
                'Category was not found: ' .
                    $categoryName .
                    '.'
            );
        }

        $categoryId =
            (int) $category['id'];

        $existing =
            $this->importModel
            ->findByField(
                'products',
                $companyId,
                'internal_code',
                $internalCode
            );

        if (
            $existing !== null &&
            $mode === 'create_only'
        ) {
            $this->rowError(
                'internal_code',
                'Product already exists with internal code: ' .
                    $internalCode .
                    '.'
            );
        }

        if ($barcode !== null) {
            $barcodeProduct =
                $this->importModel
                ->findByField(
                    'products',
                    $companyId,
                    'barcode',
                    $barcode
                );

            if (
                $barcodeProduct !== null &&
                (
                    $existing === null ||
                    (int) $barcodeProduct['id'] !==
                    (int) $existing['id']
                )
            ) {
                $this->rowError(
                    'barcode',
                    'Barcode already belongs to another product.'
                );
            }
        }

        $columns =
            $this->importModel
            ->tableColumns('products');

        $salePriceColumn =
            $this->firstSupportedColumn(
                $columns,
                [
                    'sale_price',
                    'selling_price',
                ]
            );

        if ($salePriceColumn === null) {
            throw new Exception(
                'Products table has no supported sale price column.'
            );
        }

        $minimumStockColumn =
            $this->firstSupportedColumn(
                $columns,
                [
                    'minimum_stock',
                    'minimum_stock_level',
                ]
            );

        $data = [
            'company_id' => $companyId,
            'name' => $name,
            'internal_code' => $internalCode,
            'barcode' => $barcode,
            'category_id' => $categoryId,
            'unit' => $unit,
            $salePriceColumn => $salePrice,
            'purchase_price' => $purchasePrice,
            'vat_rate' => $vatRate,
            'is_active' => $isActive,
            'description' => $description,
        ];

        if ($minimumStockColumn !== null) {
            $data[$minimumStockColumn] =
                $minimumStock;
        }

        if (
            in_array(
                'last_purchase_cost',
                $columns,
                true
            ) &&
            $purchasePrice > 0
        ) {
            $data['last_purchase_cost'] =
                $purchasePrice;
        }

        if (!$apply) {
            return;
        }

        if ($existing === null) {
            $this->importModel
                ->insertRecord(
                    'products',
                    $data
                );

            return;
        }

        $this->importModel
            ->updateRecord(
                'products',
                (int) $existing['id'],
                $companyId,
                $data
            );
    }

    private function processContact(
        string $table,
        array $row,
        string $mode,
        int $companyId,
        bool $apply
    ): void {
        $name = $this->requiredText(
            $row,
            'name',
            255
        );

        $companyName =
            $this->optionalText(
                $row,
                'company_name',
                255
            );

        $eik = $this->optionalText(
            $row,
            'eik',
            50
        );

        $vatNumber =
            $this->optionalText(
                $row,
                'vat_number',
                50
            );

        $email = $this->optionalText(
            $row,
            'email',
            255
        );

        if (
            $email !== null &&
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $this->rowError(
                'email',
                'Email address is invalid.'
            );
        }

        $phone = $this->optionalText(
            $row,
            'phone',
            100
        );

        $contactPerson =
            $this->optionalText(
                $row,
                'contact_person',
                255
            );

        $address = $this->optionalText(
            $row,
            'address',
            500
        );

        $city = $this->optionalText(
            $row,
            'city',
            255
        );

        $postalCode =
            $this->optionalText(
                $row,
                'postal_code',
                50
            );

        $country = $this->optionalText(
            $row,
            'country',
            100
        );

        $notes = $this->optionalText(
            $row,
            'notes',
            5000
        );

        $isActive =
            $this->booleanValue(
                $row['is_active'] ?? '1',
                'is_active'
            );

        $columns =
            $this->importModel
            ->tableColumns($table);

        $existing = null;

        $matchCandidates = [
            'vat_number' => $vatNumber,
            'eik' => $eik,
            'email' => $email,
        ];

        foreach (
            $matchCandidates as
            $field => $value
        ) {
            if (
                $value === null ||
                !in_array(
                    $field,
                    $columns,
                    true
                )
            ) {
                continue;
            }

            $matched =
                $this->importModel
                ->findByField(
                    $table,
                    $companyId,
                    $field,
                    $value
                );

            if ($matched === null) {
                continue;
            }

            if (
                $existing !== null &&
                (int) $existing['id'] !==
                (int) $matched['id']
            ) {
                $this->rowError(
                    $field,
                    'The supplied identifiers match different existing records.'
                );
            }

            $existing = $matched;
        }

        if (
            $existing !== null &&
            $mode === 'create_only'
        ) {
            $this->rowError(
                null,
                ucfirst(
                    rtrim($table, 's')
                ) .
                    ' already exists.'
            );
        }

        $data = [
            'company_id' => $companyId,
            'name' => $name,
            'company_name' => $companyName,
            'eik' => $eik,
            'vat_number' => $vatNumber,
            'email' => $email,
            'phone' => $phone,
            'contact_person' =>
            $contactPerson,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postalCode,
            'country' => $country,
            'is_active' => $isActive,
            'notes' => $notes,
        ];

        if (!$apply) {
            return;
        }

        if ($existing === null) {
            $this->importModel
                ->insertRecord(
                    $table,
                    $data
                );

            return;
        }

        $this->importModel
            ->updateRecord(
                $table,
                (int) $existing['id'],
                $companyId,
                $data
            );
    }

    private function processOpeningStock(
        array $row,
        int $companyId,
        int $userId,
        int $batchId,
        bool $apply
    ): void {
        $warehouseCode =
            $this->requiredText(
                $row,
                'warehouse_code',
                100
            );

        $productCode =
            $this->requiredText(
                $row,
                'product_internal_code',
                100
            );

        $quantity = $this->decimal(
            $row['quantity'] ?? '',
            'quantity',
            false,
            3
        );

        $unitCost = $this->decimal(
            $row['unit_cost'] ?? '',
            'unit_cost',
            true,
            4
        );

        $note = $this->optionalText(
            $row,
            'note',
            500
        );

        $pairKey =
            mb_strtoupper($warehouseCode) .
            '|' .
            mb_strtoupper($productCode);

        if (
            isset(
                $this->seenOpeningStockPairs[$pairKey]
            )
        ) {
            $this->rowError(
                null,
                'Duplicate product and warehouse combination in the CSV file.'
            );
        }

        $this->seenOpeningStockPairs[$pairKey] = true;

        $product =
            $this->importModel
            ->findProductByCode(
                $companyId,
                $productCode,
                $apply
            );

        if ($product === null) {
            $this->rowError(
                'product_internal_code',
                'Product was not found: ' .
                    $productCode .
                    '.'
            );
        }

        $warehouse =
            $this->importModel
            ->findWarehouseByCode(
                $companyId,
                $warehouseCode
            );

        if ($warehouse === null) {
            $this->rowError(
                'warehouse_code',
                'Warehouse was not found: ' .
                    $warehouseCode .
                    '.'
            );
        }

        if (
            (int) (
                $product['is_active'] ?? 1
            ) !== 1
        ) {
            $this->rowError(
                'product_internal_code',
                'Opening stock cannot be imported for an inactive product.'
            );
        }

        if (
            (int) (
                $warehouse['is_active'] ?? 1
            ) !== 1
        ) {
            $this->rowError(
                'warehouse_code',
                'Opening stock cannot be imported into an inactive warehouse.'
            );
        }

        $productId = (int) $product['id'];
        $warehouseId = (int) $warehouse['id'];

        $stockState =
            $this->importModel->stockState(
                $companyId,
                $productId,
                $warehouseId
            );

        $currentQuantity = round(
            (float) (
                $stockState['quantity'] ?? 0
            ),
            3
        );

        if (
            abs($currentQuantity) > 0.0005
        ) {
            $this->rowError(
                'quantity',
                'Opening stock is allowed only when current stock is zero. Current quantity: ' .
                    number_format(
                        $currentQuantity,
                        3,
                        '.',
                        ''
                    ) .
                    '.'
            );
        }

        $movementCount =
            $this->importModel
            ->movementCount(
                $companyId,
                $productId,
                $warehouseId
            );

        if ($movementCount > 0) {
            $this->rowError(
                null,
                'Opening stock is not allowed because this product and warehouse already have warehouse movements.'
            );
        }

        if (!$apply) {
            return;
        }

        $movement =
            $this->inventoryCostService
            ->receive(
                $companyId,
                $productId,
                $warehouseId,
                $quantity,
                $unitCost
            );

        $transactionData = array_merge(
            [
                'company_id' => $companyId,
                'product_id' => $productId,
                'from_warehouse_id' => null,
                'to_warehouse_id' =>
                $warehouseId,
                'user_id' => $userId,
                'type' => 'opening_stock',
                'quantity' => $quantity,
                'reference_type' =>
                'csv_import',
                'reference_id' => $batchId,
                'note' =>
                $note ??
                    (
                        'Opening stock from CSV import #' .
                        $batchId .
                        '.'
                    ),
            ],

            $this->inventoryCostService
                ->incomingTransactionFields(
                    $movement
                )
        );

        $created =
            $this->transactionModel
            ->create($transactionData);

        if (!$created) {
            throw new Exception(
                'Opening stock warehouse transaction could not be created.'
            );
        }

        $this->importModel
            ->updateProductLastCost(
                $productId,
                $companyId,
                $unitCost
            );
    }

    private function validateUpload(
        array $file
    ): array {
        $error = (int) (
            $file['error'] ??
            UPLOAD_ERR_NO_FILE
        );

        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new Exception(
                'Select a CSV file.'
            );
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new Exception(
                'CSV upload failed with code: ' .
                    $error .
                    '.'
            );
        }

        $tmpName = (string) (
            $file['tmp_name'] ?? ''
        );

        if (
            $tmpName === '' ||
            !is_uploaded_file($tmpName)
        ) {
            throw new Exception(
                'Uploaded CSV file is invalid.'
            );
        }

        $originalFilename = trim(
            (string) (
                $file['name'] ??
                'import.csv'
            )
        );

        $extension = strtolower(
            pathinfo(
                $originalFilename,
                PATHINFO_EXTENSION
            )
        );

        if ($extension !== 'csv') {
            throw new Exception(
                'Only .csv files are allowed.'
            );
        }

        $size = (int) (
            $file['size'] ?? 0
        );

        $maxBytes = max(
            1024,
            Environment::integer(
                'CSV_IMPORT_MAX_BYTES',
                5242880
            )
        );

        if ($size <= 0) {
            throw new Exception(
                'CSV file is empty.'
            );
        }

        if ($size > $maxBytes) {
            throw new Exception(
                'CSV file exceeds the maximum allowed size of ' .
                    number_format(
                        $maxBytes / 1048576,
                        2
                    ) .
                    ' MB.'
            );
        }

        $finfo = new \finfo(
            FILEINFO_MIME_TYPE
        );

        $mimeType = (string) $finfo->file(
            $tmpName
        );

        $allowedMimeTypes = [
            'text/plain',
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
            'application/octet-stream',
        ];

        if (
            !in_array(
                $mimeType,
                $allowedMimeTypes,
                true
            )
        ) {
            throw new Exception(
                'Uploaded file is not recognized as CSV text.'
            );
        }

        return [
            'tmp_name' => $tmpName,

            'original_filename' =>
            mb_substr(
                basename(
                    $originalFilename
                ),
                0,
                255
            ),
        ];
    }

    private function detectDelimiter(
        mixed $handle
    ): array {
        rewind($handle);

        $sampleLine = null;

        while (
            (
                $line = fgets($handle)
            ) !== false
        ) {
            $line =
                $this->normalizeEncoding(
                    $line
                );

            $line = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $line
            ) ?? $line;

            if (trim($line) === '') {
                continue;
            }

            $sampleLine = $line;
            break;
        }

        if ($sampleLine === null) {
            throw new Exception(
                'CSV file contains no data.'
            );
        }

        $candidates = [
            ';' => 'semicolon',
            ',' => 'comma',
            "\t" => 'tab',
        ];

        $bestDelimiter = null;
        $bestName = null;
        $bestCount = 0;

        foreach (
            $candidates as
            $delimiter => $name
        ) {
            $cells = str_getcsv(
                $sampleLine,
                $delimiter,
                '"',
                ''
            );

            $count = count($cells);

            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter =
                    $delimiter;
                $bestName = $name;
            }
        }

        if (
            $bestDelimiter === null ||
            $bestCount < 2
        ) {
            throw new Exception(
                'CSV delimiter could not be detected. Use semicolon, comma or TAB.'
            );
        }

        rewind($handle);

        return [
            $bestDelimiter,
            $bestName,
        ];
    }

    private function readHeaders(
        mixed $handle,
        string $delimiter,
        string $type
    ): array {
        $rawHeaders = null;

        while (
            (
                $cells = fgetcsv(
                    $handle,
                    0,
                    $delimiter,
                    '"',
                    ''
                )
            ) !== false
        ) {
            $cells =
                $this->normalizeCells(
                    $cells
                );

            if ($this->isBlankRow($cells)) {
                continue;
            }

            $rawHeaders = $cells;
            break;
        }

        if ($rawHeaders === null) {
            throw new Exception(
                'CSV header row is missing.'
            );
        }

        $headers = [];

        foreach (
            $rawHeaders as
            $index => $header
        ) {
            if ($index === 0) {
                $header = preg_replace(
                    '/^\xEF\xBB\xBF/',
                    '',
                    $header
                ) ?? $header;
            }

            $normalized =
                $this->normalizeHeader(
                    $header,
                    $type
                );

            if ($normalized === '') {
                throw new Exception(
                    'CSV contains an empty header column.'
                );
            }

            if (
                in_array(
                    $normalized,
                    $headers,
                    true
                )
            ) {
                throw new Exception(
                    'CSV contains a duplicate header: ' .
                        $normalized .
                        '.'
                );
            }

            $headers[] = $normalized;
        }

        $requiredHeaders =
            match ($type) {
                'products' => [
                    'name',
                    'internal_code',
                    'category_name',
                    'unit',
                    'sale_price',
                ],

                'clients',
                'suppliers' => [
                    'name',
                ],

                'opening_stock' => [
                    'warehouse_code',
                    'product_internal_code',
                    'quantity',
                    'unit_cost',
                ],

                default => [],
            };

        foreach (
            $requiredHeaders as $required
        ) {
            if (
                !in_array(
                    $required,
                    $headers,
                    true
                )
            ) {
                throw new Exception(
                    'Required CSV column is missing: ' .
                        $required .
                        '.'
                );
            }
        }

        return $headers;
    }

    private function normalizeHeader(
        string $header,
        string $type
    ): string {
        $header =
            $this->normalizeEncoding(
                $header
            );

        $header = mb_strtolower(
            trim($header)
        );

        $header = preg_replace(
            '/[^a-z0-9]+/u',
            '_',
            $header
        ) ?? '';

        $header = trim(
            $header,
            '_'
        );

        $aliases = [
            'product_code' =>
            'internal_code',

            'product_internal_code' =>
            $type === 'opening_stock'
                ? 'product_internal_code'
                : 'internal_code',

            'selling_price' =>
            'sale_price',

            'sales_price' =>
            'sale_price',

            'cost_price' =>
            'purchase_price',

            'category' =>
            'category_name',

            'min_stock' =>
            'minimum_stock',

            'minimum_stock_level' =>
            'minimum_stock',

            'warehouse' =>
            'warehouse_code',

            'product' =>
            'product_internal_code',

            'vat_no' =>
            'vat_number',

            'vat_id' =>
            'vat_number',

            'uin' => 'eik',

            'zip' =>
            'postal_code',

            'active' =>
            'is_active',
        ];

        return $aliases[$header] ??
            $header;
    }

    private function combineRow(
        array $headers,
        array $cells
    ): array {
        if (
            count($cells) >
            count($headers)
        ) {
            throw new CsvImportRowException(
                null,
                'CSV row contains more cells than the header.'
            );
        }

        if (
            count($cells) <
            count($headers)
        ) {
            $cells = array_pad(
                $cells,
                count($headers),
                ''
            );
        }

        $combined = array_combine(
            $headers,
            $cells
        );

        if ($combined === false) {
            throw new CsvImportRowException(
                null,
                'CSV row could not be mapped to its headers.'
            );
        }

        return $combined;
    }

    private function normalizeCells(
        array $cells
    ): array {
        $normalized = [];

        foreach ($cells as $cell) {
            $value =
                $this->normalizeEncoding(
                    (string) $cell
                );

            $value = str_replace(
                "\0",
                '',
                $value
            );

            if (mb_strlen($value) > 10000) {
                throw new CsvImportRowException(
                    null,
                    'CSV cell exceeds the maximum allowed length.'
                );
            }

            $normalized[] = trim($value);
        }

        return $normalized;
    }

    private function normalizeEncoding(
        string $value
    ): string {
        if (
            mb_check_encoding(
                $value,
                'UTF-8'
            )
        ) {
            return $value;
        }

        return mb_convert_encoding(
            $value,
            'UTF-8',
            'Windows-1251'
        );
    }

    private function isBlankRow(
        array $cells
    ): bool {
        foreach ($cells as $cell) {
            if (
                trim((string) $cell) !== ''
            ) {
                return false;
            }
        }

        return true;
    }

    private function requiredText(
        array $row,
        string $field,
        int $maxLength
    ): string {
        $value = trim(
            (string) (
                $row[$field] ?? ''
            )
        );

        if ($value === '') {
            $this->rowError(
                $field,
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $field
                    )
                ) .
                    ' is required.'
            );
        }

        if (
            mb_strlen($value) >
            $maxLength
        ) {
            $this->rowError(
                $field,
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $field
                    )
                ) .
                    ' must be maximum ' .
                    $maxLength .
                    ' characters.'
            );
        }

        return $value;
    }

    private function optionalText(
        array $row,
        string $field,
        int $maxLength
    ): ?string {
        $value = trim(
            (string) (
                $row[$field] ?? ''
            )
        );

        if ($value === '') {
            return null;
        }

        if (
            mb_strlen($value) >
            $maxLength
        ) {
            $this->rowError(
                $field,
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $field
                    )
                ) .
                    ' must be maximum ' .
                    $maxLength .
                    ' characters.'
            );
        }

        return $value;
    }

    private function decimal(
        mixed $value,
        string $field,
        bool $allowZero,
        int $scale
    ): float {
        if (!is_scalar($value)) {
            $this->rowError(
                $field,
                'Invalid numeric value.'
            );
        }

        $value = str_replace(
            [' ', ','],
            ['', '.'],
            trim((string) $value)
        );

        $pattern =
            '/^\d{1,11}(?:\.\d{1,' .
            $scale .
            '})?$/';

        if (
            preg_match(
                $pattern,
                $value
            ) !== 1
        ) {
            $this->rowError(
                $field,
                'Invalid numeric value. Maximum decimal places: ' .
                    $scale .
                    '.'
            );
        }

        $number = round(
            (float) $value,
            $scale
        );

        if (
            $allowZero &&
            $number < 0
        ) {
            $this->rowError(
                $field,
                'Value cannot be negative.'
            );
        }

        if (
            !$allowZero &&
            $number <= 0
        ) {
            $this->rowError(
                $field,
                'Value must be greater than zero.'
            );
        }

        return $number;
    }

    private function booleanValue(
        mixed $value,
        string $field
    ): int {
        $value = mb_strtolower(
            trim((string) $value)
        );

        if (
            in_array(
                $value,
                [
                    '1',
                    'yes',
                    'true',
                    'active',
                    'да',
                ],
                true
            )
        ) {
            return 1;
        }

        if (
            in_array(
                $value,
                [
                    '0',
                    'no',
                    'false',
                    'inactive',
                    'не',
                ],
                true
            )
        ) {
            return 0;
        }

        $this->rowError(
            $field,
            'Boolean value must be 1/0, yes/no or true/false.'
        );
    }

    private function firstSupportedColumn(
        array $columns,
        array $candidates
    ): ?string {
        foreach ($candidates as $candidate) {
            if (
                in_array(
                    $candidate,
                    $columns,
                    true
                )
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private function rowError(
        ?string $columnName,
        string $message
    ): never {
        throw new CsvImportRowException(
            $columnName,
            $message
        );
    }

    private function failBatch(
        int $batchId,
        int $companyId,
        string $message
    ): void {
        $this->importModel->finishBatch(
            $batchId,
            $companyId,
            'failed',
            0,
            0,
            0,
            $message
        );
    }
}