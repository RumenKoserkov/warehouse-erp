<?php

declare(strict_types=1);

use App\Core\Flash;
use App\Services\AuthService;

/** @var string $content */

$authService = new AuthService();
$currentUser = $authService->user();

$flashMessages = Flash::all();

$pageTitle = 'Warehouse ERP';

if (isset($title)) {
    $pageTitle = $title;
}

?>

<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">

        <div class="container-fluid">

            <a class="navbar-brand" href="/dashboard">
                Warehouse ERP
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarContent"
                aria-controls="navbarContent"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">

                <?php if ($currentUser !== null): ?>

                    <ul class="navbar-nav me-auto">

                        <li class="nav-item">
                            <a href="/dashboard" class="nav-link">
                                Dashboard
                            </a>
                        </li>

                        <?php if ($authService->hasAnyRole(['administrator', 'manager'])): ?>

                            <li class="nav-item">
                                <a href="/products" class="nav-link">
                                    Products
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/clients" class="nav-link">
                                    Clients
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/suppliers" class="nav-link">
                                    Suppliers
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/categories" class="nav-link">
                                    Categories
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/purchases" class="nav-link">
                                    Purchases
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/reports" class="nav-link">
                                    Reports
                                </a>
                            </li>

                        <?php endif; ?>

                        <?php if ($authService->hasAnyRole(['administrator', 'manager', 'employee'])): ?>

                            <li class="nav-item">
                                <a href="/warehouses" class="nav-link">
                                    Warehouses
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/stock" class="nav-link">
                                    Stock
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/stock/in" class="nav-link">
                                    Stock In
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/stock/out" class="nav-link">
                                    Stock Out
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/stock/transfer" class="nav-link">
                                    Transfer Stock
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/stock/history" class="nav-link">Stock History</a>
                            </li>

                            <li class="nav-item">
                                <a href="/sales" class="nav-link">Sales</a>
                            </li>

                            <li class="nav-item">
                                <a href="/sales/create" class="nav-link">New Sale</a>
                            </li>

                            <li class="nav-item">
                                <a href="/purchases" class="nav-link">Purchases</a>
                            </li>

                            <li class="nav-item">
                                <a href="/purchases/create" class="nav-link">New Purchase</a>
                            </li>

                        <?php endif; ?>

                        <?php if ($authService->hasRole('administrator')): ?>

                            <li class="nav-item">
                                <a href="/users" class="nav-link">
                                    Users
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/settings" class="nav-link">
                                    Settings
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="/logs" class="nav-link">
                                    Audit Log
                                </a>
                            </li>

                        <?php endif; ?>

                    </ul>

                    <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">

                        <span class="text-white">
                            <?= htmlspecialchars($currentUser['name']) ?>
                            |
                            <?= htmlspecialchars($currentUser['role_name']) ?>
                        </span>

                        <form action="/logout" method="POST" class="mb-0">

                            <button
                                type="submit"
                                class="btn btn-outline-light btn-sm">
                                Logout
                            </button>

                        </form>

                    </div>

                <?php else: ?>

                    <ul class="navbar-nav ms-auto">

                        <li class="nav-item">
                            <a href="/login" class="nav-link">
                                Login
                            </a>
                        </li>

                    </ul>

                <?php endif; ?>

            </div>

        </div>

    </nav>

    <main class="container">

        <?php foreach ($flashMessages as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close">
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?= $content ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>