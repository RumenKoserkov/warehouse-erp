<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Models\WarehouseTransaction;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\ProductMovementReportService;
use App\Services\StockReportService;
use App\Services\StockService;

class StockController extends Controller
{
    private Product $productModel;

    private Warehouse $warehouseModel;

    private StockService $stockService;

    private AuthService $authService;

    private StockLevel $stockLevelModel;

    private WarehouseTransaction
        $warehouseTransactionModel;

    private Category $categoryModel;

    private StockReportService
        $stockReportService;

    private ProductMovementReportService
        $productMovementReportService;

    private AuditLogService
        $auditLogService;

    public function __construct()
    {
        $this->productModel =
            new Product();

        $this->warehouseModel =
            new Warehouse();

        $this->categoryModel =
            new Category();

        $this->stockLevelModel =
            new StockLevel();

        $this->warehouseTransactionModel =
            new WarehouseTransaction();

        $this->stockService =
            new StockService();

        $this->stockReportService =
            new StockReportService();

        $this->productMovementReportService =
            new ProductMovementReportService();

        $this->authService =
            new AuthService();

        $this->auditLogService =
            new AuditLogService();
    }

    public function index(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $search = '';

        if (isset($_GET['search'])) {
            $search = trim(
                (string) $_GET['search']
            );
        }

        $stockLevels =
            $this->stockLevelModel
                ->allByCompany(
                    $companyId,
                    $search
                );

        $canViewCosts = in_array(
            (string) $currentUser[
                'role_slug'
            ],
            [
                'administrator',
                'manager',
            ],
            true
        );

        $this->view('stock/index', [
            'title' =>
                'Stock Levels',

            'stockLevels' =>
                $stockLevels,

            'search' =>
                $search,

            'canViewCosts' =>
                $canViewCosts,
        ]);
    }

    public function in(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $this->view('stock/in', [
            'title' =>
                'Stock In',

            'products' =>
                $this->productModel
                    ->activeByCompany(
                        $companyId
                    ),

            'warehouses' =>
                $this->warehouseModel
                    ->activeByCompany(
                        $companyId
                    ),

            'errors' => [],

            'old' => [
                'product_id' => '',
                'warehouse_id' => '',
                'quantity' => '',
                'unit_cost' => '',
                'note' => '',
            ],
        ]);
    }

    public function storeIn(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $userId =
            (int) $currentUser['id'];

        $productId =
            (int) (
                $_POST['product_id'] ??
                0
            );

        $warehouseId =
            (int) (
                $_POST['warehouse_id'] ??
                0
            );

        $quantity =
            (float) (
                $_POST['quantity'] ??
                0
            );

        $unitCost = isset(
            $_POST['unit_cost']
        )
            ? (float) $_POST[
                'unit_cost'
            ]
            : 0.0;

        $note = trim(
            (string) (
                $_POST['note'] ??
                ''
            )
        );

        $validator =
            new Validator($_POST);

        $validator
            ->required(
                'product_id',
                'Product is required.'
            )
            ->required(
                'warehouse_id',
                'Warehouse is required.'
            )
            ->required(
                'quantity',
                'Quantity is required.'
            )
            ->numeric(
                'quantity',
                'Quantity must be numeric.'
            )
            ->required(
                'unit_cost',
                'Unit cost is required.'
            )
            ->numeric(
                'unit_cost',
                'Unit cost must be numeric.'
            );

        $errors =
            $validator->all();

        if ($productId <= 0) {
            $errors[] =
                'Please select a valid product.';
        }

        if ($warehouseId <= 0) {
            $errors[] =
                'Please select a valid warehouse.';
        }

        if ($quantity <= 0) {
            $errors[] =
                'Quantity must be greater than zero.';
        }

        if ($unitCost < 0) {
            $errors[] =
                'Unit cost cannot be negative.';
        }

        if ($productId > 0) {
            $product =
                $this->productModel
                    ->findByIdAndCompany(
                        $productId,
                        $companyId
                    );

            if ($product === null) {
                $errors[] =
                    'Selected product was not found.';
            }
        }

        if ($warehouseId > 0) {
            $warehouse =
                $this->warehouseModel
                    ->findByIdAndCompany(
                        $warehouseId,
                        $companyId
                    );

            if ($warehouse === null) {
                $errors[] =
                    'Selected warehouse was not found.';
            }
        }

        if (!empty($errors)) {
            $this->view('stock/in', [
                'title' =>
                    'Stock In',

                'products' =>
                    $this->productModel
                        ->activeByCompany(
                            $companyId
                        ),

                'warehouses' =>
                    $this->warehouseModel
                        ->activeByCompany(
                            $companyId
                        ),

                'errors' =>
                    $errors,

                'old' => [
                    'product_id' =>
                        (string) $productId,

                    'warehouse_id' =>
                        (string) $warehouseId,

                    'quantity' =>
                        (string) $quantity,

                    'unit_cost' =>
                        (string) $unitCost,

                    'note' =>
                        $note,
                ],
            ]);

            return;
        }

        $success =
            $this->stockService
                ->increase([
                    'company_id' =>
                        $companyId,

                    'product_id' =>
                        $productId,

                    'warehouse_id' =>
                        $warehouseId,

                    'user_id' =>
                        $userId,

                    'type' =>
                        'in',

                    'quantity' =>
                        $quantity,

                    'unit_cost' =>
                        $unitCost,

                    'reference_type' =>
                        'manual',

                    'reference_id' =>
                        null,

                    'note' =>
                        $note,
                ]);

        if (!$success) {
            Flash::danger(
                'Could not increase stock.'
            );

            $this->redirect('/stock/in');

            return;
        }

        $this->auditLogService->log(
            $companyId,
            $userId,
            'stock_in',
            'stock',
            null,
            'Manual stock in. Product ID: ' .
                $productId .
                ', warehouse ID: ' .
                $warehouseId .
                ', quantity: ' .
                $quantity .
                ', unit cost: ' .
                number_format(
                    $unitCost,
                    4,
                    '.',
                    ''
                )
        );

        Flash::success(
            'Stock increased successfully.'
        );

        $this->redirect('/stock/in');
    }

    public function out(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $this->view('stock/out', [
            'title' =>
                'Stock Out',

            'products' =>
                $this->productModel
                    ->activeByCompany(
                        $companyId
                    ),

            'warehouses' =>
                $this->warehouseModel
                    ->activeByCompany(
                        $companyId
                    ),

            'errors' => [],

            'old' => [
                'product_id' => '',
                'warehouse_id' => '',
                'quantity' => '',
                'note' => '',
            ],
        ]);
    }

    public function storeOut(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $userId =
            (int) $currentUser['id'];

        $productId =
            (int) (
                $_POST['product_id'] ??
                0
            );

        $warehouseId =
            (int) (
                $_POST['warehouse_id'] ??
                0
            );

        $quantity =
            (float) (
                $_POST['quantity'] ??
                0
            );

        $note = trim(
            (string) (
                $_POST['note'] ??
                ''
            )
        );

        $validator =
            new Validator($_POST);

        $validator
            ->required(
                'product_id',
                'Product is required.'
            )
            ->required(
                'warehouse_id',
                'Warehouse is required.'
            )
            ->required(
                'quantity',
                'Quantity is required.'
            )
            ->numeric(
                'quantity',
                'Quantity must be numeric.'
            );

        $errors =
            $validator->all();

        if ($productId <= 0) {
            $errors[] =
                'Please select a valid product.';
        }

        if ($warehouseId <= 0) {
            $errors[] =
                'Please select a valid warehouse.';
        }

        if ($quantity <= 0) {
            $errors[] =
                'Quantity must be greater than zero.';
        }

        if ($productId > 0) {
            $product =
                $this->productModel
                    ->findByIdAndCompany(
                        $productId,
                        $companyId
                    );

            if ($product === null) {
                $errors[] =
                    'Selected product was not found.';
            }
        }

        if ($warehouseId > 0) {
            $warehouse =
                $this->warehouseModel
                    ->findByIdAndCompany(
                        $warehouseId,
                        $companyId
                    );

            if ($warehouse === null) {
                $errors[] =
                    'Selected warehouse was not found.';
            }
        }

        if (!empty($errors)) {
            $this->view('stock/out', [
                'title' =>
                    'Stock Out',

                'products' =>
                    $this->productModel
                        ->activeByCompany(
                            $companyId
                        ),

                'warehouses' =>
                    $this->warehouseModel
                        ->activeByCompany(
                            $companyId
                        ),

                'errors' =>
                    $errors,

                'old' => [
                    'product_id' =>
                        (string) $productId,

                    'warehouse_id' =>
                        (string) $warehouseId,

                    'quantity' =>
                        (string) $quantity,

                    'note' =>
                        $note,
                ],
            ]);

            return;
        }

        $success =
            $this->stockService
                ->decrease([
                    'company_id' =>
                        $companyId,

                    'product_id' =>
                        $productId,

                    'warehouse_id' =>
                        $warehouseId,

                    'user_id' =>
                        $userId,

                    'type' =>
                        'out',

                    'quantity' =>
                        $quantity,

                    'reference_type' =>
                        'manual',

                    'reference_id' =>
                        null,

                    'note' =>
                        $note,
                ]);

        if (!$success) {
            Flash::danger(
                'Not enough stock or operation failed.'
            );

            $this->redirect('/stock/out');

            return;
        }

        $this->auditLogService->log(
            $companyId,
            $userId,
            'stock_out',
            'stock',
            null,
            'Manual stock out. Product ID: ' .
                $productId .
                ', warehouse ID: ' .
                $warehouseId .
                ', quantity: ' .
                $quantity
        );

        Flash::success(
            'Stock decreased successfully.'
        );

        $this->redirect('/stock/out');
    }

    public function transfer(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $this->view('stock/transfer', [
            'title' =>
                'Transfer Stock',

            'products' =>
                $this->productModel
                    ->activeByCompany(
                        $companyId
                    ),

            'warehouses' =>
                $this->warehouseModel
                    ->activeByCompany(
                        $companyId
                    ),

            'errors' => [],

            'old' => [
                'product_id' => '',
                'from_warehouse_id' => '',
                'to_warehouse_id' => '',
                'quantity' => '',
                'note' => '',
            ],
        ]);
    }

    public function storeTransfer(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $userId =
            (int) $currentUser['id'];

        $productId = 0;
        $fromWarehouseId = 0;
        $toWarehouseId = 0;
        $quantity = 0.0;
        $note = '';

        if (isset($_POST['product_id'])) {
            $productId =
                (int) $_POST['product_id'];
        }

        if (
            isset(
                $_POST[
                    'from_warehouse_id'
                ]
            )
        ) {
            $fromWarehouseId =
                (int) $_POST[
                    'from_warehouse_id'
                ];
        }

        if (
            isset(
                $_POST[
                    'to_warehouse_id'
                ]
            )
        ) {
            $toWarehouseId =
                (int) $_POST[
                    'to_warehouse_id'
                ];
        }

        if (isset($_POST['quantity'])) {
            $quantity =
                (float) $_POST['quantity'];
        }

        if (isset($_POST['note'])) {
            $note = trim(
                (string) $_POST['note']
            );
        }

        $validator =
            new Validator($_POST);

        $validator
            ->required(
                'product_id',
                'Product is required.'
            )
            ->required(
                'from_warehouse_id',
                'From warehouse is required.'
            )
            ->required(
                'to_warehouse_id',
                'To warehouse is required.'
            )
            ->required(
                'quantity',
                'Quantity is required.'
            )
            ->numeric(
                'quantity',
                'Quantity must be numeric.'
            );

        $errors =
            $validator->all();

        if ($productId <= 0) {
            $errors[] =
                'Please select a valid product.';
        }

        if ($fromWarehouseId <= 0) {
            $errors[] =
                'Please select a valid source warehouse.';
        }

        if ($toWarehouseId <= 0) {
            $errors[] =
                'Please select a valid destination warehouse.';
        }

        if (
            $fromWarehouseId > 0 &&
            $toWarehouseId > 0 &&
            $fromWarehouseId ===
                $toWarehouseId
        ) {
            $errors[] =
                'Source and destination warehouses must be different.';
        }

        if ($quantity <= 0) {
            $errors[] =
                'Quantity must be greater than zero.';
        }

        if ($productId > 0) {
            $product =
                $this->productModel
                    ->findByIdAndCompany(
                        $productId,
                        $companyId
                    );

            if ($product === null) {
                $errors[] =
                    'Selected product was not found.';
            }
        }

        if ($fromWarehouseId > 0) {
            $fromWarehouse =
                $this->warehouseModel
                    ->findByIdAndCompany(
                        $fromWarehouseId,
                        $companyId
                    );

            if ($fromWarehouse === null) {
                $errors[] =
                    'Source warehouse was not found.';
            }
        }

        if ($toWarehouseId > 0) {
            $toWarehouse =
                $this->warehouseModel
                    ->findByIdAndCompany(
                        $toWarehouseId,
                        $companyId
                    );

            if ($toWarehouse === null) {
                $errors[] =
                    'Destination warehouse was not found.';
            }
        }

        if (!empty($errors)) {
            $this->view(
                'stock/transfer',
                [
                    'title' =>
                        'Transfer Stock',

                    'products' =>
                        $this->productModel
                            ->activeByCompany(
                                $companyId
                            ),

                    'warehouses' =>
                        $this->warehouseModel
                            ->activeByCompany(
                                $companyId
                            ),

                    'errors' =>
                        $errors,

                    'old' => [
                        'product_id' =>
                            (string) $productId,

                        'from_warehouse_id' =>
                            (string) $fromWarehouseId,

                        'to_warehouse_id' =>
                            (string) $toWarehouseId,

                        'quantity' =>
                            (string) $quantity,

                        'note' =>
                            $note,
                    ],
                ]
            );

            return;
        }

        $success =
            $this->stockService
                ->transfer([
                    'company_id' =>
                        $companyId,

                    'product_id' =>
                        $productId,

                    'from_warehouse_id' =>
                        $fromWarehouseId,

                    'to_warehouse_id' =>
                        $toWarehouseId,

                    'user_id' =>
                        $userId,

                    'quantity' =>
                        $quantity,

                    'reference_type' =>
                        'manual',

                    'reference_id' =>
                        null,

                    'note' =>
                        $note,
                ]);

        if (!$success) {
            Flash::danger(
                'Not enough stock or transfer failed.'
            );

            $this->redirect(
                '/stock/transfer'
            );

            return;
        }

        $this->auditLogService->log(
            $companyId,
            $userId,
            'stock_transfer',
            'stock',
            null,
            'Manual stock transfer. Product ID: ' .
                $productId .
                ', from warehouse ID: ' .
                $fromWarehouseId .
                ', to warehouse ID: ' .
                $toWarehouseId .
                ', quantity: ' .
                $quantity
        );

        Flash::success(
            'Stock transferred successfully.'
        );

        $this->redirect(
            '/stock/transfer'
        );
    }

    public function history(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $search = '';
        $type = '';
        $productId = '';
        $warehouseId = '';

        if (isset($_GET['search'])) {
            $search = trim(
                (string) $_GET['search']
            );
        }

        if (isset($_GET['type'])) {
            $type = trim(
                (string) $_GET['type']
            );
        }

        if (isset($_GET['product_id'])) {
            $productId = trim(
                (string) $_GET[
                    'product_id'
                ]
            );
        }

        if (
            isset(
                $_GET[
                    'warehouse_id'
                ]
            )
        ) {
            $warehouseId = trim(
                (string) $_GET[
                    'warehouse_id'
                ]
            );
        }

        $filters = [
            'search' =>
                $search,

            'type' =>
                $type,

            'product_id' =>
                $productId,

            'warehouse_id' =>
                $warehouseId,
        ];

        $transactions =
            $this
                ->warehouseTransactionModel
                ->allByCompany(
                    $companyId,
                    $filters
                );

        $canViewCosts = in_array(
            (string) $currentUser[
                'role_slug'
            ],
            [
                'administrator',
                'manager',
            ],
            true
        );

        $this->view('stock/history', [
            'title' =>
                'Stock History',

            'transactions' =>
                $transactions,

            'products' =>
                $this->productModel
                    ->activeByCompany(
                        $companyId
                    ),

            'warehouses' =>
                $this->warehouseModel
                    ->activeByCompany(
                        $companyId
                    ),

            'types' =>
                $this
                    ->warehouseTransactionModel
                    ->types(),

            'filters' =>
                $filters,

            'canViewCosts' =>
                $canViewCosts,
        ]);
    }

    public function report(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $warehouseId = '';
        $categoryId = '';

        if (
            isset(
                $_GET[
                    'warehouse_id'
                ]
            )
        ) {
            $warehouseId = trim(
                (string) $_GET[
                    'warehouse_id'
                ]
            );
        }

        if (
            isset(
                $_GET[
                    'category_id'
                ]
            )
        ) {
            $categoryId = trim(
                (string) $_GET[
                    'category_id'
                ]
            );
        }

        $filters = [
            'warehouse_id' =>
                $warehouseId,

            'category_id' =>
                $categoryId,
        ];

        $this->view('stock/report', [
            'title' =>
                'Stock Report',

            'filters' =>
                $filters,

            'warehouses' =>
                $this->warehouseModel
                    ->activeByCompany(
                        $companyId
                    ),

            'categories' =>
                $this->categoryModel
                    ->activeByCompany(
                        $companyId
                    ),

            'summary' =>
                $this->stockReportService
                    ->getSummary(
                        $companyId,
                        $filters
                    ),

            'stockByWarehouse' =>
                $this->stockReportService
                    ->getStockByWarehouse(
                        $companyId,
                        $filters
                    ),

            'stockByCategory' =>
                $this->stockReportService
                    ->getStockByCategory(
                        $companyId,
                        $filters
                    ),

            'lowStockItems' =>
                $this->stockReportService
                    ->getLowStockItems(
                        $companyId,
                        $filters,
                        20
                    ),

            'outOfStockItems' =>
                $this->stockReportService
                    ->getOutOfStockItems(
                        $companyId,
                        $filters,
                        20
                    ),

            'mostValuableStock' =>
                $this->stockReportService
                    ->getMostValuableStock(
                        $companyId,
                        $filters,
                        10
                    ),
        ]);
    }

    public function productMovementReport(): void
    {
        $currentUser =
            $this->authService->user();

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $productId = 0;
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-d');

        if (isset($_GET['product_id'])) {
            $productId =
                (int) $_GET[
                    'product_id'
                ];
        }

        if (
            isset($_GET['date_from']) &&
            trim(
                (string) $_GET[
                    'date_from'
                ]
            ) !== ''
        ) {
            $dateFrom = trim(
                (string) $_GET[
                    'date_from'
                ]
            );
        }

        if (
            isset($_GET['date_to']) &&
            trim(
                (string) $_GET[
                    'date_to'
                ]
            ) !== ''
        ) {
            $dateTo = trim(
                (string) $_GET[
                    'date_to'
                ]
            );
        }

        if ($dateFrom > $dateTo) {
            $temporaryDate =
                $dateFrom;

            $dateFrom =
                $dateTo;

            $dateTo =
                $temporaryDate;
        }

        $selectedProduct = null;
        $summary = null;
        $warehouseSummary = [];
        $movements = [];

        if ($productId > 0) {
            $selectedProduct =
                $this->productModel
                    ->findByIdAndCompany(
                        $productId,
                        $companyId
                    );

            if ($selectedProduct !== null) {
                $summary =
                    $this
                        ->productMovementReportService
                        ->getSummary(
                            $companyId,
                            $productId,
                            $dateFrom,
                            $dateTo
                        );

                $warehouseSummary =
                    $this
                        ->productMovementReportService
                        ->getWarehouseSummary(
                            $companyId,
                            $productId,
                            $dateFrom,
                            $dateTo
                        );

                $movements =
                    $this
                        ->productMovementReportService
                        ->getMovements(
                            $companyId,
                            $productId,
                            $dateFrom,
                            $dateTo
                        );
            }
        }

        $this->view(
            'stock/product_movement_report',
            [
                'title' =>
                    'Product Movement Report',

                'products' =>
                    $this->productModel
                        ->activeByCompany(
                            $companyId
                        ),

                'productId' =>
                    $productId,

                'selectedProduct' =>
                    $selectedProduct,

                'dateFrom' =>
                    $dateFrom,

                'dateTo' =>
                    $dateTo,

                'summary' =>
                    $summary,

                'warehouseSummary' =>
                    $warehouseSummary,

                'movements' =>
                    $movements,
            ]
        );
    }
}