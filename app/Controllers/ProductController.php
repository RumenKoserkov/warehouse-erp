<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Paginator;
use App\Core\Sorter;
use App\Core\Validator;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\AuthService;
use App\Services\ProductImageService;
use App\Services\AuditLogService;

class ProductController extends Controller
{
    private Product $productModel;
    private Category $categoryModel;
    private Supplier $supplierModel;
    private AuthService $authService;
    private ProductImageService $productImageService;
    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->supplierModel = new Supplier();
        $this->authService = new AuthService();
        $this->productImageService = new ProductImageService();
        $this->auditLogService = new AuditLogService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $search = '';

        if (isset($_GET['search'])) {
            $search = trim((string) $_GET['search']);
        }

        $filters = $this->getProductFilters();

        $page = 1;

        if (isset($_GET['page'])) {
            $requestedPage = filter_var(
                $_GET['page'],
                FILTER_VALIDATE_INT
            );

            if (
                $requestedPage !== false &&
                $requestedPage > 0
            ) {
                $page = $requestedPage;
            }
        }

        $requestedSort = '';

        if (isset($_GET['sort'])) {
            $requestedSort = trim(
                (string) $_GET['sort']
            );
        }

        $requestedDirection = '';

        if (isset($_GET['direction'])) {
            $requestedDirection = trim(
                (string) $_GET['direction']
            );
        }

        $filterParameters = [
            'search' => $search,
            'category_id' => $filters['category_id'],
            'supplier_id' => $filters['supplier_id'],
            'unit' => $filters['unit'],
            'status' => $filters['status'],
            'min_price' => $filters['min_price'],
            'max_price' => $filters['max_price'],
        ];

        $sorter = new Sorter(
            [
                'id' => 'products.id',
                'name' => 'products.name',
                'code' => 'products.internal_code',
                'category' => 'categories.name',
                'supplier' => 'suppliers.name',
                'purchase_price' => 'products.purchase_price',
                'selling_price' => 'products.selling_price',
                'min_stock' => 'products.min_stock',
                'status' => 'products.is_active',
            ],
            $requestedSort,
            $requestedDirection,
            'id',
            'desc',
            '/products',
            $filterParameters
        );

        $perPage = 10;

        $companyId = (int) $currentUser['company_id'];

        $totalProducts = $this->productModel->countByCompany(
            $companyId,
            $search,
            $filters
        );

        $paginationParameters = $filterParameters;

        $paginationParameters['sort'] = $sorter->key();
        $paginationParameters['direction'] =
            $sorter->direction();

        $paginator = new Paginator(
            $totalProducts,
            $page,
            $perPage,
            '/products',
            $paginationParameters
        );

        $products = $this->productModel->paginateByCompany(
            $companyId,
            $search,
            $paginator->perPage(),
            $paginator->offset(),
            $sorter->column(),
            $sorter->sqlDirection(),
            $filters
        );

        $categories = $this->categoryModel->allByCompany(
            $companyId
        );

        $suppliers = $this->supplierModel->allByCompany(
            $companyId
        );

        $this->view('products/index', [
            'title' => 'Products',
            'products' => $products,
            'search' => $search,
            'filters' => $filters,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'units' => $this->units(),
            'paginator' => $paginator,
            'sorter' => $sorter,
        ]);
    }

    public function create(): void
    {
        $currentUser = $this->authService->user();

        $this->view('products/create', [
            'title' => 'Create Product',
            'categories' => $this->categoryModel->activeByCompany(
                (int)$currentUser['company_id']
            ),
            'suppliers' => $this->supplierModel->activeByCompany(
                (int)$currentUser['company_id']
            ),
            'errors' => [],
            'old' => $this->emptyOldData(),
            'units' => $this->units(),
        ]);
    }

    public function store(): void
    {
        $currentUser = $this->authService->user();

        $data = $this->getFormData();

        $errors = $this->validateProductData(
            $data,
            (int) $currentUser['company_id']
        );

        if (!empty($errors)) {
            $this->view('products/create', [
                'title' => 'Create Product',
                'categories' => $this->categoryModel->activeByCompany(
                    (int) $currentUser['company_id']
                ),
                'suppliers' => $this->supplierModel->activeByCompany(
                    (int) $currentUser['company_id']
                ),
                'errors' => $errors,
                'old' => $data,
                'units' => $this->units(),
            ]);

            return;
        }

        $imageFile = [];

        if (isset($_FILES['image'])) {
            $imageFile = $_FILES['image'];
        }

        $imageResult = $this->productImageService->upload($imageFile);

        if (!$imageResult['success']) {
            $this->view('products/create', [
                'title' => 'Create Product',
                'categories' => $this->categoryModel->activeByCompany(
                    (int) $currentUser['company_id']
                ),
                'suppliers' => $this->supplierModel->activeByCompany(
                    (int) $currentUser['company_id']
                ),
                'errors' => [$imageResult['error']],
                'old' => $data,
                'units' => $this->units(),
            ]);

            return;
        }

        $data['company_id'] = (int) $currentUser['company_id'];

        $data['internal_code'] =
            $this->productModel->generateNextInternalCode(
                (int) $currentUser['company_id']
            );

        $data['image_path'] = $imageResult['path'];

        $created = $this->productModel->create($data);

        if (!$created) {
            Flash::danger('Could not create product.');

            $this->redirect('/products');

            return;
        }

        $this->auditLogService->log(
            (int) $currentUser['company_id'],
            (int) $currentUser['id'],
            'create',
            'product',
            null,
            'Created product: ' . $data['name']
        );

        Flash::success('Product created successfully.');

        $this->redirect('/products');
    }

    public function edit(): void
    {
        $currentUser = $this->authService->user();

        $id = 0;

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
        }

        if ($id <= 0) {
            $this->abort(404);
        }

        $product = $this->productModel->findByIdAndCompany(
            $id,
            (int)$currentUser['company_id']
        );

        if ($product === null) {
            $this->abort(404);
        }

        $this->view('products/edit', [
            'title' => 'Edit Product',
            'product' => $product,
            'categories' => $this->categoryModel->activeByCompany((int)$currentUser['company_id']),
            'suppliers' => $this->supplierModel->activeByCompany((int)$currentUser['company_id']),
            'errors' => [],
            'old' => $product,
            'units' => $this->units(),
        ]);
    }

    public function update(): void
    {
        $currentUser = $this->authService->user();

        $id = 0;

        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }

        if ($id <= 0) {
            $this->abort(404);
        }

        $product = $this->productModel->findByIdAndCompany(
            $id,
            (int) $currentUser['company_id']
        );

        if ($product === null) {
            $this->abort(404);
        }

        $data = $this->getFormData();

        $errors = $this->validateProductData(
            $data,
            (int) $currentUser['company_id'],
            $id
        );

        if (!empty($errors)) {
            $this->view('products/edit', [
                'title' => 'Edit Product',
                'product' => $product,
                'categories' => $this->categoryModel->activeByCompany(
                    (int) $currentUser['company_id']
                ),
                'suppliers' => $this->supplierModel->activeByCompany(
                    (int) $currentUser['company_id']
                ),
                'errors' => $errors,
                'old' => $data,
                'units' => $this->units(),
            ]);

            return;
        }

        $imageFile = [];

        if (isset($_FILES['image'])) {
            $imageFile = $_FILES['image'];
        }

        $imageResult = $this->productImageService->upload($imageFile);

        if (!$imageResult['success']) {
            $this->view('products/edit', [
                'title' => 'Edit Product',
                'product' => $product,
                'categories' => $this->categoryModel->activeByCompany(
                    (int) $currentUser['company_id']
                ),
                'suppliers' => $this->supplierModel->activeByCompany(
                    (int) $currentUser['company_id']
                ),
                'errors' => [$imageResult['error']],
                'old' => $data,
                'units' => $this->units(),
            ]);

            return;
        }

        $data['company_id'] = (int) $currentUser['company_id'];
        $data['image_path'] = $product['image_path'];

        if ($imageResult['path'] !== null) {
            $data['image_path'] = $imageResult['path'];
        }

        $updated = $this->productModel->update($id, $data);

        if (!$updated) {
            Flash::danger('Could not update product.');

            $this->redirect('/products');

            return;
        }

        $this->auditLogService->log(
            (int) $currentUser['company_id'],
            (int) $currentUser['id'],
            'update',
            'product',
            $id,
            'Updated product: ' . $data['name']
        );

        Flash::success('Product updated successfully.');

        $this->redirect('/products');
    }

    public function deactivate(): void
    {
        $currentUser = $this->authService->user();

        $id = 0;

        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
        }

        if ($id <= 0) {
            $this->abort(404);
        }

        $product = $this->productModel->findByIdAndCompany(
            $id,
            (int)$currentUser['company_id']
        );

        if ($product === null) {
            $this->abort(404);
        }

        $deactivated = $this->productModel->deactivate(
            $id,
            (int)$currentUser['company_id']
        );

        if (!$deactivated) {
            Flash::danger('Could not deactivate product.');
            $this->redirect('/products');
            return;
        }

        $this->auditLogService->log(
            (int)$currentUser['company_id'],
            (int)$currentUser['id'],
            'deactivate',
            'product',
            $id,
            'Deactivated product ID: ' . $id
        );

        Flash::success('Product deactivated successfully.');

        $this->redirect('/products');
    }


    private function validateProductData(
        array $data,
        int $companyId,
        int $productId = 0
    ): array {
        $validator = new Validator($_POST);

        $validator
            ->required(
                'name',
                'Product name is required.'
            )
            ->max(
                'name',
                255,
                'Product name must be maximum 255 characters.'
            )
            ->max(
                'barcode',
                100,
                'Barcode must be maximum 100 characters.'
            )
            ->required(
                'category_id',
                'Category is required.'
            )
            ->integer(
                'category_id',
                'Category must be valid.'
            )
            ->positive(
                'category_id',
                'Please select a valid category.'
            )
            ->integer(
                'supplier_id',
                'Supplier must be valid.'
            )
            ->positive(
                'supplier_id',
                'Supplier must be valid.'
            )
            ->required(
                'unit',
                'Unit is required.'
            )
            ->max(
                'unit',
                30,
                'Unit must be maximum 30 characters.'
            )
            ->in(
                'unit',
                $this->units(),
                'Invalid product unit.'
            )
            ->required(
                'purchase_price',
                'Purchase price is required.'
            )
            ->decimal(
                'purchase_price',
                2,
                'Purchase price must contain maximum 2 decimal places.'
            )
            ->nonNegative(
                'purchase_price',
                'Purchase price cannot be negative.'
            )
            ->required(
                'selling_price',
                'Selling price is required.'
            )
            ->decimal(
                'selling_price',
                2,
                'Selling price must contain maximum 2 decimal places.'
            )
            ->nonNegative(
                'selling_price',
                'Selling price cannot be negative.'
            )
            ->required(
                'min_stock',
                'Minimum stock is required.'
            )
            ->decimal(
                'min_stock',
                3,
                'Minimum stock must contain maximum 3 decimal places.'
            )
            ->nonNegative(
                'min_stock',
                'Minimum stock cannot be negative.'
            );

        if ($data['barcode'] !== null) {
            $barcodeExists = false;

            if ($productId > 0) {
                $barcodeExists =
                    $this->productModel
                    ->barcodeExistsInCompanyExceptProduct(
                        $data['barcode'],
                        $companyId,
                        $productId
                    );
            } else {
                $barcodeExists =
                    $this->productModel
                    ->barcodeExistsInCompany(
                        $data['barcode'],
                        $companyId
                    );
            }

            if ($barcodeExists) {
                $validator->add(
                    'barcode',
                    'Product with this barcode already exists.'
                );
            }
        }

        return $validator->all();
    }

    private function getFormData(): array
    {
        $supplierId = null;

        if (isset($_POST['supplier_id']) && (int)$_POST['supplier_id'] > 0) {
            $supplierId = (int)$_POST['supplier_id'];
        }

        $barcode = '';

        if (isset($_POST['barcode'])) {
            $barcode = trim((string)$_POST['barcode']);
        }

        if ($barcode === '') {
            $barcode = null;
        }

        $categoryId = 0;

        if (isset($_POST['category_id'])) {
            $categoryId = (int)$_POST['category_id'];
        }

        $name = '';

        if (isset($_POST['name'])) {
            $name = trim((string)$_POST['name']);
        }

        $unit = '';

        if (isset($_POST['unit'])) {
            $unit = trim((string)$_POST['unit']);
        }

        $purchasePrice = 0.00;

        if (isset($_POST['purchase_price'])) {
            $purchasePrice = (float)$_POST['purchase_price'];
        }

        $sellingPrice = 0.00;

        if (isset($_POST['selling_price'])) {
            $sellingPrice = (float)$_POST['selling_price'];
        }

        $minStock = 0.00;

        if (isset($_POST['min_stock'])) {
            $minStock = (float)$_POST['min_stock'];
        }

        $description = '';

        if (isset($_POST['description'])) {
            $description = trim((string)$_POST['description']);
        }

        return [
            'category_id' => $categoryId,
            'supplier_id' => $supplierId,
            'barcode' => $barcode,
            'name' => $name,
            'unit' => $unit,
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'min_stock' => $minStock,
            'description' => $description,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function getProductFilters(): array
    {
        $filters = [
            'category_id' => '',
            'supplier_id' => '',
            'unit' => '',
            'status' => '',
            'min_price' => '',
            'max_price' => '',
        ];

        if (isset($_GET['category_id'])) {
            $categoryId = filter_var(
                $_GET['category_id'],
                FILTER_VALIDATE_INT
            );

            if (
                $categoryId !== false &&
                $categoryId > 0
            ) {
                $filters['category_id'] = $categoryId;
            }
        }

        if (isset($_GET['supplier_id'])) {
            $supplierId = filter_var(
                $_GET['supplier_id'],
                FILTER_VALIDATE_INT
            );

            if (
                $supplierId !== false &&
                $supplierId > 0
            ) {
                $filters['supplier_id'] = $supplierId;
            }
        }

        if (isset($_GET['unit'])) {
            $unit = trim((string) $_GET['unit']);

            if (in_array($unit, $this->units(), true)) {
                $filters['unit'] = $unit;
            }
        }

        if (isset($_GET['status'])) {
            $status = trim((string) $_GET['status']);

            $allowedStatuses = [
                'active',
                'inactive',
            ];

            if (in_array($status, $allowedStatuses, true)) {
                $filters['status'] = $status;
            }
        }

        if (isset($_GET['min_price'])) {
            $minPrice = trim(
                (string) $_GET['min_price']
            );

            $minPrice = str_replace(
                ',',
                '.',
                $minPrice
            );

            if (
                is_numeric($minPrice) &&
                (float) $minPrice >= 0
            ) {
                $filters['min_price'] = $minPrice;
            }
        }

        if (isset($_GET['max_price'])) {
            $maxPrice = trim(
                (string) $_GET['max_price']
            );

            $maxPrice = str_replace(
                ',',
                '.',
                $maxPrice
            );

            if (
                is_numeric($maxPrice) &&
                (float) $maxPrice >= 0
            ) {
                $filters['max_price'] = $maxPrice;
            }
        }

        if (
            $filters['min_price'] !== '' &&
            $filters['max_price'] !== ''
        ) {
            $minPrice = (float) $filters['min_price'];
            $maxPrice = (float) $filters['max_price'];

            if ($minPrice > $maxPrice) {
                $filters['min_price'] =
                    (string) $maxPrice;

                $filters['max_price'] =
                    (string) $minPrice;
            }
        }

        return $filters;
    }

    private function emptyOldData(): array
    {
        return [
            'category_id' => '',
            'supplier_id' => '',
            'barcode' => '',
            'name' => '',
            'unit' => 'piece',
            'purchase_price' => '0.00',
            'selling_price' => '0.00',
            'min_stock' => '0',
            'description' => '',
            'is_active' => '1',
        ];
    }

    private function units(): array
    {
        return [
            'piece',
            'kg',
            'liter',
            'meter',
        ];
    }
}
