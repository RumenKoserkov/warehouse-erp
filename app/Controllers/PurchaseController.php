<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AuthService;
use App\Services\PurchaseService;
use App\Services\TaxService;

class PurchaseController extends Controller
{
    private Purchase $purchaseModel;
    private Supplier $supplierModel;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private AuthService $authService;
    private PurchaseService $purchaseService;
    private PurchaseItem $purchaseItemModel;
    private TaxService $taxService;

    public function __construct()
    {
        $this->purchaseModel = new Purchase();
        $this->purchaseItemModel = new PurchaseItem();
        $this->supplierModel = new Supplier();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->authService = new AuthService();
        $this->purchaseService = new PurchaseService();
        $this->taxService = new TaxService();
    }

    public function create(): void
    {
        $currentUser = $this->authService->user();

        $companyId =
            (int) $currentUser['company_id'];

        $taxConfiguration =
            $this->taxService
            ->purchaseConfiguration(
                $companyId
            );

        $this->view('purchases/create', [
            'title' => 'Create Purchase',

            'purchaseNumber' =>
            $this->purchaseModel
                ->generateNextPurchaseNumber(
                    $companyId
                ),

            'purchaseDate' => date('Y-m-d'),

            'suppliers' =>
            $this->supplierModel
                ->activeByCompany(
                    $companyId
                ),

            'warehouses' =>
            $this->warehouseModel
                ->activeByCompany(
                    $companyId
                ),

            'products' =>
            $this->productModel
                ->activeByCompany(
                    $companyId
                ),

            'paymentMethods' =>
            $this->paymentMethods(),

            'taxConfiguration' =>
            $taxConfiguration,

            'errors' => [],
            'old' => $this->emptyOldData(),
        ]);
    }

    public function store(): void
    {
        $currentUser = $this->authService->user();

        $companyId =
            (int) $currentUser['company_id'];

        $taxConfiguration =
            $this->taxService
            ->purchaseConfiguration(
                $companyId
            );

        $supplierId = null;
        $warehouseId = 0;
        $purchaseDate = '';
        $paymentMethod = '';
        $note = '';

        if (
            isset($_POST['supplier_id']) &&
            (int) $_POST['supplier_id'] > 0
        ) {
            $supplierId =
                (int) $_POST['supplier_id'];
        }

        if (isset($_POST['warehouse_id'])) {
            $warehouseId =
                (int) $_POST['warehouse_id'];
        }

        if (isset($_POST['purchase_date'])) {
            $purchaseDate = trim(
                (string) $_POST['purchase_date']
            );
        }

        if (isset($_POST['payment_method'])) {
            $paymentMethod = trim(
                (string) $_POST['payment_method']
            );
        }

        if (isset($_POST['note'])) {
            $note = trim(
                (string) $_POST['note']
            );
        }

        $validator = new Validator($_POST);

        $validator
            ->required(
                'purchase_date',
                'Purchase date is required.'
            )
            ->required(
                'warehouse_id',
                'Warehouse is required.'
            )
            ->required(
                'payment_method',
                'Payment method is required.'
            );

        $errors = $validator->all();

        if ($warehouseId <= 0) {
            $errors[] =
                'Please select a valid warehouse.';
        }

        if (!in_array(
            $paymentMethod,
            $this->paymentMethods(),
            true
        )) {
            $errors[] =
                'Invalid payment method.';
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

        if ($supplierId !== null) {
            $supplier =
                $this->supplierModel
                ->findByIdAndCompany(
                    $supplierId,
                    $companyId
                );

            if ($supplier === null) {
                $errors[] =
                    'Selected supplier was not found.';
            }
        }

        $items = $this->getItemsFromRequest();

        if (empty($items)) {
            $errors[] =
                'Purchase must have at least one product.';
        }

        if (!empty($errors)) {
            $this->view('purchases/create', [
                'title' => 'Create Purchase',

                'purchaseNumber' =>
                $this->purchaseModel
                    ->generateNextPurchaseNumber(
                        $companyId
                    ),

                'purchaseDate' =>
                $purchaseDate,

                'suppliers' =>
                $this->supplierModel
                    ->activeByCompany(
                        $companyId
                    ),

                'warehouses' =>
                $this->warehouseModel
                    ->activeByCompany(
                        $companyId
                    ),

                'products' =>
                $this->productModel
                    ->activeByCompany(
                        $companyId
                    ),

                'paymentMethods' =>
                $this->paymentMethods(),

                'taxConfiguration' =>
                $taxConfiguration,

                'errors' => $errors,

                'old' => [
                    'supplier_id' =>
                    (string) $supplierId,

                    'warehouse_id' =>
                    (string) $warehouseId,

                    'payment_method' =>
                    $paymentMethod,

                    'note' => $note,
                ],
            ]);

            return;
        }

        $result =
            $this->purchaseService
            ->createPurchase([
                'company_id' =>
                $companyId,

                'supplier_id' =>
                $supplierId,

                'warehouse_id' =>
                $warehouseId,

                'user_id' =>
                (int) $currentUser['id'],

                'purchase_date' =>
                $purchaseDate,

                'payment_method' =>
                $paymentMethod,

                'note' => $note,
                'items' => $items,
            ]);

        if (!$result['success']) {
            $this->view('purchases/create', [
                'title' => 'Create Purchase',

                'purchaseNumber' =>
                $this->purchaseModel
                    ->generateNextPurchaseNumber(
                        $companyId
                    ),

                'purchaseDate' =>
                $purchaseDate,

                'suppliers' =>
                $this->supplierModel
                    ->activeByCompany(
                        $companyId
                    ),

                'warehouses' =>
                $this->warehouseModel
                    ->activeByCompany(
                        $companyId
                    ),

                'products' =>
                $this->productModel
                    ->activeByCompany(
                        $companyId
                    ),

                'paymentMethods' =>
                $this->paymentMethods(),

                'taxConfiguration' =>
                $taxConfiguration,

                'errors' => [
                    $result['error'],
                ],

                'old' => [
                    'supplier_id' =>
                    (string) $supplierId,

                    'warehouse_id' =>
                    (string) $warehouseId,

                    'payment_method' =>
                    $paymentMethod,

                    'note' => $note,
                ],
            ]);

            return;
        }

        Flash::success(
            'Purchase created successfully.'
        );

        $this->redirect('/purchases');
    }

    public function show(): void
    {
        $currentUser = $this->authService->user();

        $id = 0;

        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }

        if ($id <= 0) {
            $this->abort(404);

            return;
        }

        $purchase =
            $this->purchaseModel
            ->findByIdAndCompany(
                $id,
                (int) $currentUser['company_id']
            );

        if ($purchase === null) {
            $this->abort(404);

            return;
        }

        $items =
            $this->purchaseItemModel
            ->allByPurchase(
                $id,
                (int) $currentUser['company_id']
            );

        $this->view('purchases/show', [
            'title' => 'Purchase Details',
            'purchase' => $purchase,
            'items' => $items,
        ]);
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        $search = '';

        if (isset($_GET['search'])) {
            $search = trim(
                (string) $_GET['search']
            );
        }

        $purchases =
            $this->purchaseModel
            ->allByCompany(
                (int) $currentUser['company_id'],
                $search
            );

        $this->view('purchases/index', [
            'title' => 'Purchases',
            'purchases' => $purchases,
            'search' => $search,
        ]);
    }

    public function cancel(): void
    {
        $currentUser = $this->authService->user();

        $id = 0;

        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }

        if ($id <= 0) {
            $this->abort(404);

            return;
        }

        $result =
            $this->purchaseService
            ->cancelPurchase(
                $id,
                (int) $currentUser['company_id'],
                (int) $currentUser['id']
            );

        if (!$result['success']) {
            Flash::danger(
                $result['error']
            );

            $this->redirect(
                '/purchases/show?id=' . $id
            );

            return;
        }

        Flash::success(
            'Purchase cancelled successfully.'
        );

        $this->redirect(
            '/purchases/show?id=' . $id
        );
    }

    private function getItemsFromRequest(): array
    {
        $items = [];

        if (!isset($_POST['product_id'])) {
            return $items;
        }

        if (!is_array($_POST['product_id'])) {
            return $items;
        }

        $productIds = $_POST['product_id'];

        foreach (
            $productIds as $index => $productId
        ) {
            $quantity = 0;
            $unitCost = 0;
            $discountAmount = 0;

            if (
                isset(
                    $_POST['quantity'][$index]
                )
            ) {
                $quantity =
                    (float) $_POST['quantity'][$index];
            }

            if (
                isset(
                    $_POST['unit_cost'][$index]
                )
            ) {
                $unitCost =
                    (float) $_POST['unit_cost'][$index];
            }

            if (
                isset(
                    $_POST['discount_amount'][$index]
                )
            ) {
                $discountAmount =
                    (float) $_POST['discount_amount'][$index];
            }

            if ((int) $productId <= 0) {
                continue;
            }

            $items[] = [
                'product_id' =>
                (int) $productId,

                'quantity' =>
                $quantity,

                'unit_cost' =>
                $unitCost,

                'discount_amount' =>
                $discountAmount,
            ];
        }

        return $items;
    }

    private function paymentMethods(): array
    {
        return [
            'cash',
            'card',
            'bank_transfer',
            'other',
        ];
    }

    private function emptyOldData(): array
    {
        return [
            'supplier_id' => '',
            'warehouse_id' => '',
            'payment_method' =>
            'bank_transfer',
            'note' => '',
        ];
    }
}
