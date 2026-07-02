<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;

return [
    [
        'method' => 'GET',
        'uri' => '/',
        'action' => [DashboardController::class, 'index'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'GET',
        'uri' => '/dashboard',
        'action' => [DashboardController::class, 'index'],
        'middleware' => ['auth'],
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