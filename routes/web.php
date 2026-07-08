<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\ClientController;
use App\Controllers\SupplierController;
use App\Controllers\CategoryController;
use App\Controllers\WarehouseController;
use App\Controllers\ProductController;
use App\Controllers\StockController;
use App\Controllers\SaleController;

return [
    [
        'method' => 'GET',
        'uri' => '/',
        'action' => [DashboardController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'GET',
        'uri' => '/dashboard',
        'action' => [DashboardController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'GET',
        'uri' => '/login',
        'action' => [AuthController::class, 'showLogin'],
        'middleware' => ['guest'],
    ],
    [
        'method' => 'POST',
        'uri' => '/login',
        'action' => [AuthController::class, 'login'],
        'middleware' => ['guest'],
    ],
    [
        'method' => 'POST',
        'uri' => '/logout',
        'action' => [AuthController::class, 'logout'],
        'middleware' => ['auth'],
    ],

    // Users CRUD
    [
        'method' => 'GET',
        'uri' => '/users',
        'action' => [UserController::class, 'index'],
        'middleware' => ['auth', 'role:administrator'],
    ],
    [
        'method' => 'GET',
        'uri' => '/users/create',
        'action' => [UserController::class, 'create'],
        'middleware' => ['auth', 'role:administrator'],
    ],
    [
        'method' => 'POST',
        'uri' => '/users/store',
        'action' => [UserController::class, 'store'],
        'middleware' => ['auth', 'role:administrator'],
    ],

    [
        'method' => 'GET',
        'uri' => '/users/edit',
        'action' => [UserController::class, 'edit'],
        'middleware' => ['auth', 'role:administrator'],
    ],

    [
        'method' => 'POST',
        'uri' => '/users/update',
        'action' => [UserController::class, 'update'],
        'middleware' => ['auth', 'role:administrator'],
    ],

    [
        'method' => 'POST',
        'uri' => '/users/deactivate',
        'action' => [UserController::class, 'deactivate'],
        'middleware' => ['auth', 'role:administrator'],
    ],

    // Clients CRUD

    [
        'method' => 'GET',
        'uri' => '/clients',
        'action' => [ClientController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/clients/create',
        'action' => [ClientController::class, 'create'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/clients/store',
        'action' => [ClientController::class, 'store'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/clients/edit',
        'action' => [ClientController::class, 'edit'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/clients/update',
        'action' => [ClientController::class, 'update'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/clients/deactivate',
        'action' => [ClientController::class, 'deactivate'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],

    // Supplier CRUD
    [
        'method' => 'GET',
        'uri' => '/suppliers',
        'action' => [SupplierController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/suppliers/create',
        'action' => [SupplierController::class, 'create'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/suppliers/store',
        'action' => [SupplierController::class, 'store'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/suppliers/edit',
        'action' => [SupplierController::class, 'edit'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/suppliers/update',
        'action' => [SupplierController::class, 'update'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/suppliers/deactivate',
        'action' => [SupplierController::class, 'deactivate'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],

    // Category CRUD

    [
        'method' => 'GET',
        'uri' => '/categories',
        'action' => [CategoryController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/categories/create',
        'action' => [CategoryController::class, 'create'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/categories/store',
        'action' => [CategoryController::class, 'store'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/categories/edit',
        'action' => [CategoryController::class, 'edit'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/categories/update',
        'action' => [CategoryController::class, 'update'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/categories/deactivate',
        'action' => [CategoryController::class, 'deactivate'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],

    // Warehouse CRUD

    [
        'method' => 'GET',
        'uri' => '/warehouses',
        'action' => [WarehouseController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'GET',
        'uri' => '/warehouses/create',
        'action' => [WarehouseController::class, 'create'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/warehouses/store',
        'action' => [WarehouseController::class, 'store'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/warehouses/edit',
        'action' => [WarehouseController::class, 'edit'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/warehouses/update',
        'action' => [WarehouseController::class, 'update'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/warehouses/deactivate',
        'action' => [WarehouseController::class, 'deactivate'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],

    // Product CRUD
    [
        'method' => 'GET',
        'uri' => '/products',
        'action' => [ProductController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/products/create',
        'action' => [ProductController::class, 'create'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/products/store',
        'action' => [ProductController::class, 'store'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'GET',
        'uri' => '/products/edit',
        'action' => [ProductController::class, 'edit'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/products/update',
        'action' => [ProductController::class, 'update'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
    [
        'method' => 'POST',
        'uri' => '/products/deactivate',
        'action' => [ProductController::class, 'deactivate'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],

    // Stock CRUD
    [
        'method' => 'GET',
        'uri' => '/stock',
        'action' => [StockController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],

    [
        'method' => 'GET',
        'uri' => '/stock/history',
        'action' => [StockController::class, 'history'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'GET',
        'uri' => '/stock/in',
        'action' => [StockController::class, 'in'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],

    [
        'method' => 'POST',
        'uri' => '/stock/in/store',
        'action' => [StockController::class, 'storeIn'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],

    [
        'method' => 'GET',
        'uri' => '/stock/out',
        'action' => [StockController::class, 'out'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],

    [
        'method' => 'POST',
        'uri' => '/stock/out/store',
        'action' => [StockController::class, 'storeOut'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'GET',
        'uri' => '/stock/transfer',
        'action' => [StockController::class, 'transfer'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'POST',
        'uri' => '/stock/transfer/store',
        'action' => [StockController::class, 'storeTransfer'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],

    // Sales CRUD
    [
        'method' => 'GET',
        'uri' => '/sales',
        'action' => [SaleController::class, 'index'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'GET',
        'uri' => '/sales/create',
        'action' => [SaleController::class, 'create'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'POST',
        'uri' => '/sales/store',
        'action' => [SaleController::class, 'store'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'GET',
        'uri' => '/sales/show',
        'action' => [SaleController::class, 'show'],
        'middleware' => ['auth', 'role:administrator,manager,employee'],
    ],
    [
        'method' => 'POST',
        'uri' => '/sales/cancel',
        'action' => [SaleController::class, 'cancel'],
        'middleware' => ['auth', 'role:administrator,manager'],
    ],
];
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Този файл съдържа всички маршрути (routes) на приложението.
| Всеки route описва:
| - HTTP метода (GET, POST, PUT, DELETE)
| - URL адреса (URI)
| - Controller-а, който ще обработи заявката
| - Метода на Controller-а, който ще бъде извикан
|
| Поток на изпълнение:
| Browser Request
|        ↓
| public/index.php
|        ↓
| routes/web.php
|        ↓
| Router
|        ↓
| Controller@method
|        ↓
| View
|        ↓
| HTML Response
|
| Пример:
| GET /dashboard
| ↓
| DashboardController::index()
|
*/