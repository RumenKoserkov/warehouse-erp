<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\AuthService;

class SaleController extends Controller
{
    private Sale $saleModel;
    private Client $clientModel;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private AuthService $authService;

    public function __construct()
    {
        $this->saleModel = new Sale();
        $this->clientModel = new Client();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->authService = new AuthService();
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