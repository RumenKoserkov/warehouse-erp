<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AuthService;

class PurchaseController extends Controller
{
    private Purchase $purchaseModel;
    private Supplier $supplierModel;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private AuthService $authService;

    public function __construct()
    {
        $this->purchaseModel = new Purchase();
        $this->supplierModel = new Supplier();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->authService = new AuthService();
    }

    public function create(): void
    {
        $currentUser = $this->authService->user();

        $companyId = (int)$currentUser['company_id'];

        $this->view('purchases/create', [
            'title' => 'Create Purchase',
            'purchaseNumber' => $this->purchaseModel->generateNextPurchaseNumber($companyId),
            'purchaseDate' => date('Y-m-d'),
            'suppliers' => $this->supplierModel->activeByCompany($companyId),
            'warehouses' => $this->warehouseModel->activeByCompany($companyId),
            'products' => $this->productModel->activeByCompany($companyId),
            'paymentMethods' => $this->paymentMethods(),
            'errors' => [],
            'old' => $this->emptyOldData(),
        ]);
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
            'payment_method' => 'bank_transfer',
            'note' => '',
        ];
    }
}