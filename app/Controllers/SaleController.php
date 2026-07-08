<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\AuthService;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\SaleService;

class SaleController extends Controller
{
    private Sale $saleModel;
    private Client $clientModel;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private AuthService $authService;
    private SaleService $saleService;

    public function __construct()
    {
        $this->saleModel = new Sale();
        $this->clientModel = new Client();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->authService = new AuthService();
        $this->saleService = new SaleService();
    }

    public function create(): void
    {
        $currentUser = $this->authService->user();

        $companyId = (int) $currentUser['company_id'];

        $this->view('sales/create', [
            'title' => 'Create Sale',
            'saleNumber' => $this->saleModel->generateNextSaleNumber($companyId),
            'saleDate' => date('Y-m-d'),
            'clients' => $this->clientModel->activeByCompany($companyId),
            'warehouses' => $this->warehouseModel->activeByCompany($companyId),
            'products' => $this->productModel->activeByCompany($companyId),
            'paymentMethods' => $this->paymentMethods(),
            'errors' => [],
            'old' => $this->emptyOldData(),
        ]);
    }

    public function store(): void
    {
        $currentUser = $this->authService->user();

        $companyId = (int)$currentUser['company_id'];

        $clientId = null;
        $warehouseId = 0;
        $saleDate = '';
        $paymentMethod = '';
        $note = '';

        if (isset($_POST['client_id']) && (int)$_POST['client_id'] > 0) {
            $clientId = (int)$_POST['client_id'];
        }

        if (isset($_POST['warehouse_id'])) {
            $warehouseId = (int)$_POST['warehouse_id'];
        }

        if (isset($_POST['sale_date'])) {
            $saleDate = trim((string)$_POST['sale_date']);
        }

        if (isset($_POST['payment_method'])) {
            $paymentMethod = trim((string)$_POST['payment_method']);
        }

        if (isset($_POST['note'])) {
            $note = trim((string)$_POST['note']);
        }

        $validator = new Validator($_POST);

        $validator
            ->required('sale_date', 'Sale date is required.')
            ->required('warehouse_id', 'Warehouse is required.')
            ->required('payment_method', 'Payment method is required.');

        $errors = $validator->all();

        if ($warehouseId <= 0) {
            $errors[] = 'Please select a valid warehouse.';
        }

        if (!in_array($paymentMethod, $this->paymentMethods(), true)) {
            $errors[] = 'Invalid payment method.';
        }

        $warehouse = null;

        if ($warehouseId > 0) {
            $warehouse = $this->warehouseModel->findByIdAndCompany(
                $warehouseId,
                $companyId
            );

            if ($warehouse === null) {
                $errors[] = 'Selected warehouse was not found.';
            }
        }

        if ($clientId !== null) {
            $client = $this->clientModel->findByIdAndCompany(
                $clientId,
                $companyId
            );

            if ($client === null) {
                $errors[] = 'Selected client was not found.';
            }
        }

        $items = $this->getItemsFromRequest();

        if (empty($items)) {
            $errors[] = 'Sale must have at least one product.';
        }

        if (!empty($errors)) {
            $this->view('sales/create', [
                'title' => 'Create Sale',
                'saleNumber' => $this->saleModel->generateNextSaleNumber($companyId),
                'saleDate' => $saleDate,
                'clients' => $this->clientModel->activeByCompany($companyId),
                'warehouses' => $this->warehouseModel->activeByCompany($companyId),
                'products' => $this->productModel->activeByCompany($companyId),
                'paymentMethods' => $this->paymentMethods(),
                'errors' => $errors,
                'old' => [
                    'client_id' => (string)$clientId,
                    'warehouse_id' => (string)$warehouseId,
                    'payment_method' => $paymentMethod,
                    'note' => $note,
                ],
            ]);

            return;
        }

        $result = $this->saleService->createSale([
            'company_id' => $companyId,
            'client_id' => $clientId,
            'warehouse_id' => $warehouseId,
            'user_id' => (int)$currentUser['id'],
            'sale_date' => $saleDate,
            'payment_method' => $paymentMethod,
            'note' => $note,
            'items' => $items,
        ]);

        if (!$result['success']) {
            $this->view('sales/create', [
                'title' => 'Create Sale',
                'saleNumber' => $this->saleModel->generateNextSaleNumber($companyId),
                'saleDate' => $saleDate,
                'clients' => $this->clientModel->activeByCompany($companyId),
                'warehouses' => $this->warehouseModel->activeByCompany($companyId),
                'products' => $this->productModel->activeByCompany($companyId),
                'paymentMethods' => $this->paymentMethods(),
                'errors' => [$result['error']],
                'old' => [
                    'client_id' => (string)$clientId,
                    'warehouse_id' => (string)$warehouseId,
                    'payment_method' => $paymentMethod,
                    'note' => $note,
                ],
            ]);

            return;
        }

        Flash::success('Sale created successfully.');

        $this->redirect('/sales');
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

        foreach ($productIds as $index => $productId) {
            $quantity = 0;
            $unitPrice = 0;
            $discountAmount = 0;

            if (isset($_POST['quantity'][$index])) {
                $quantity = (float)$_POST['quantity'][$index];
            }

            if (isset($_POST['unit_price'][$index])) {
                $unitPrice = (float)$_POST['unit_price'][$index];
            }

            if (isset($_POST['discount_amount'][$index])) {
                $discountAmount = (float)$_POST['discount_amount'][$index];
            }

            if ((int)$productId <= 0) {
                continue;
            }

            $items[] = [
                'product_id' => (int)$productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
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
            'client_id' => '',
            'warehouse_id' => '',
            'payment_method' => 'cash',
            'note' => '',
        ];
    }
}
