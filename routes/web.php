<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;

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