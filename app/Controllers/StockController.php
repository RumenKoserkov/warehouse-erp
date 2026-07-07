<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\AuthService;
use App\Services\StockService;

class StockController extends Controller
{
    private Product $productModel;
    private Warehouse $warehouseModel;
    private StockService $stockService;
    private AuthService $authService;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->stockService = new StockService();
        $this->authService = new AuthService();
    }

    public function in(): void
    {
        $currentUser = $this->authService->user();

        $this->view('stock/in', [
            'title' => 'Stock In',
            'products' => $this->productModel->activeByCompany((int)$currentUser['company_id']),
            'warehouses' => $this->warehouseModel->activeByCompany((int)$currentUser['company_id']),
            'errors' => [],
            'old' => [
                'product_id' => '',
                'warehouse_id' => '',
                'quantity' => '',
                'note' => '',
            ],
        ]);
    }

    public function storeIn(): void
    {
        $currentUser = $this->authService->user();

        $productId = (int)($_POST['product_id'] ?? 0);
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $note = trim((string)($_POST['note'] ?? ''));

        $validator = new Validator($_POST);

        $validator
            ->required('product_id', 'Product is required.')
            ->required('warehouse_id', 'Warehouse is required.')
            ->required('quantity', 'Quantity is required.')
            ->numeric('quantity', 'Quantity must be numeric.');

        $errors = $validator->all();

        if ($productId <= 0) {
            $errors[] = 'Please select a valid product.';
        }

        if ($warehouseId <= 0) {
            $errors[] = 'Please select a valid warehouse.';
        }

        if ($quantity <= 0) {
            $errors[] = 'Quantity must be greater than zero.';
        }

        $product = null;

        if ($productId > 0) {
            $product = $this->productModel->findByIdAndCompany(
                $productId,
                (int)$currentUser['company_id']
            );

            if ($product === null) {
                $errors[] = 'Selected product was not found.';
            }
        }

        $warehouse = null;

        if ($warehouseId > 0) {
            $warehouse = $this->warehouseModel->findByIdAndCompany(
                $warehouseId,
                (int)$currentUser['company_id']
            );

            if ($warehouse === null) {
                $errors[] = 'Selected warehouse was not found.';
            }
        }

        if (!empty($errors)) {
            $this->view('stock/in', [
                'title' => 'Stock In',
                'products' => $this->productModel->activeByCompany((int)$currentUser['company_id']),
                'warehouses' => $this->warehouseModel->activeByCompany((int)$currentUser['company_id']),
                'errors' => $errors,
                'old' => [
                    'product_id' => (string)$productId,
                    'warehouse_id' => (string)$warehouseId,
                    'quantity' => (string)$quantity,
                    'note' => $note,
                ],
            ]);

            return;
        }

        $success = $this->stockService->increase([
            'company_id' => (int)$currentUser['company_id'],
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'user_id' => (int)$currentUser['id'],
            'type' => 'in',
            'quantity' => $quantity,
            'reference_type' => 'manual',
            'reference_id' => null,
            'note' => $note,
        ]);

        if (!$success) {
            Flash::danger('Could not increase stock.');
            $this->redirect('/stock/in');
        }

        Flash::success('Stock increased successfully.');

        $this->redirect('/stock/in');
    }

    public function out(): void
    {
        $currentUser = $this->authService->user();

        $this->view('stock/out', [
            'title' => 'Stock Out',
            'products' => $this->productModel->activeByCompany((int)$currentUser['company_id']),
            'warehouses' => $this->warehouseModel->activeByCompany((int)$currentUser['company_id']),
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
        $currentUser = $this->authService->user();

        $productId = (int)($_POST['product_id'] ?? 0);
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $note = trim((string)($_POST['note'] ?? ''));

        $validator = new Validator($_POST);

        $validator
            ->required('product_id', 'Product is required.')
            ->required('warehouse_id', 'Warehouse is required.')
            ->required('quantity', 'Quantity is required.')
            ->numeric('quantity', 'Quantity must be numeric.');

        $errors = $validator->all();

        if ($productId <= 0) {
            $errors[] = 'Please select a valid product.';
        }

        if ($warehouseId <= 0) {
            $errors[] = 'Please select a valid warehouse.';
        }

        if ($quantity <= 0) {
            $errors[] = 'Quantity must be greater than zero.';
        }

        if ($productId > 0) {
            $product = $this->productModel->findByIdAndCompany(
                $productId,
                (int)$currentUser['company_id']
            );

            if ($product === null) {
                $errors[] = 'Selected product was not found.';
            }
        }

        if ($warehouseId > 0) {
            $warehouse = $this->warehouseModel->findByIdAndCompany(
                $warehouseId,
                (int)$currentUser['company_id']
            );

            if ($warehouse === null) {
                $errors[] = 'Selected warehouse was not found.';
            }
        }

        if (!empty($errors)) {
            $this->view('stock/out', [
                'title' => 'Stock Out',
                'products' => $this->productModel->activeByCompany((int)$currentUser['company_id']),
                'warehouses' => $this->warehouseModel->activeByCompany((int)$currentUser['company_id']),
                'errors' => $errors,
                'old' => [
                    'product_id' => (string)$productId,
                    'warehouse_id' => (string)$warehouseId,
                    'quantity' => (string)$quantity,
                    'note' => $note,
                ],
            ]);

            return;
        }

        $success = $this->stockService->decrease([
            'company_id' => (int)$currentUser['company_id'],
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'user_id' => (int)$currentUser['id'],
            'type' => 'out',
            'quantity' => $quantity,
            'reference_type' => 'manual',
            'reference_id' => null,
            'note' => $note,
        ]);

        if (!$success) {
            Flash::danger('Not enough stock or operation failed.');
            $this->redirect('/stock/out');
        }

        Flash::success('Stock decreased successfully.');

        $this->redirect('/stock/out');
    }

    public function transfer(): void
    {
        $currentUser = $this->authService->user();

        $this->view('stock/transfer', [
            'title' => 'Transfer Stock',
            'products' => $this->productModel->activeByCompany((int)$currentUser['company_id']),
            'warehouses' => $this->warehouseModel->activeByCompany((int)$currentUser['company_id']),
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
        $currentUser = $this->authService->user();

        $productId = 0;
        $fromWarehouseId = 0;
        $toWarehouseId = 0;
        $quantity = 0;
        $note = '';

        if (isset($_POST['product_id'])) {
            $productId = (int)$_POST['product_id'];
        }

        if (isset($_POST['from_warehouse_id'])) {
            $fromWarehouseId = (int)$_POST['from_warehouse_id'];
        }

        if (isset($_POST['to_warehouse_id'])) {
            $toWarehouseId = (int)$_POST['to_warehouse_id'];
        }

        if (isset($_POST['quantity'])) {
            $quantity = (float)$_POST['quantity'];
        }

        if (isset($_POST['note'])) {
            $note = trim((string)$_POST['note']);
        }

        $validator = new Validator($_POST);

        $validator
            ->required('product_id', 'Product is required.')
            ->required('from_warehouse_id', 'From warehouse is required.')
            ->required('to_warehouse_id', 'To warehouse is required.')
            ->required('quantity', 'Quantity is required.')
            ->numeric('quantity', 'Quantity must be numeric.');

        $errors = $validator->all();

        if ($productId <= 0) {
            $errors[] = 'Please select a valid product.';
        }

        if ($fromWarehouseId <= 0) {
            $errors[] = 'Please select a valid source warehouse.';
        }

        if ($toWarehouseId <= 0) {
            $errors[] = 'Please select a valid destination warehouse.';
        }

        if ($fromWarehouseId > 0 && $toWarehouseId > 0 && $fromWarehouseId === $toWarehouseId) {
            $errors[] = 'Source and destination warehouses must be different.';
        }

        if ($quantity <= 0) {
            $errors[] = 'Quantity must be greater than zero.';
        }

        if ($productId > 0) {
            $product = $this->productModel->findByIdAndCompany(
                $productId,
                (int)$currentUser['company_id']
            );

            if ($product === null) {
                $errors[] = 'Selected product was not found.';
            }
        }

        if ($fromWarehouseId > 0) {
            $fromWarehouse = $this->warehouseModel->findByIdAndCompany(
                $fromWarehouseId,
                (int)$currentUser['company_id']
            );

            if ($fromWarehouse === null) {
                $errors[] = 'Source warehouse was not found.';
            }
        }

        if ($toWarehouseId > 0) {
            $toWarehouse = $this->warehouseModel->findByIdAndCompany(
                $toWarehouseId,
                (int)$currentUser['company_id']
            );

            if ($toWarehouse === null) {
                $errors[] = 'Destination warehouse was not found.';
            }
        }

        if (!empty($errors)) {
            $this->view('stock/transfer', [
                'title' => 'Transfer Stock',
                'products' => $this->productModel->activeByCompany((int)$currentUser['company_id']),
                'warehouses' => $this->warehouseModel->activeByCompany((int)$currentUser['company_id']),
                'errors' => $errors,
                'old' => [
                    'product_id' => (string)$productId,
                    'from_warehouse_id' => (string)$fromWarehouseId,
                    'to_warehouse_id' => (string)$toWarehouseId,
                    'quantity' => (string)$quantity,
                    'note' => $note,
                ],
            ]);

            return;
        }

        $success = $this->stockService->transfer([
            'company_id' => (int)$currentUser['company_id'],
            'product_id' => $productId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'user_id' => (int)$currentUser['id'],
            'quantity' => $quantity,
            'reference_type' => 'manual',
            'reference_id' => null,
            'note' => $note,
        ]);

        if (!$success) {
            Flash::danger('Not enough stock or transfer failed.');
            $this->redirect('/stock/transfer');
        }

        Flash::success('Stock transferred successfully.');

        $this->redirect('/stock/transfer');
    }
}
