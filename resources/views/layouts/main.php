<?php

declare(strict_types=1);

use App\Core\Flash;
use App\Services\AuthService;

/** @var string $content */

$authService = new AuthService();
$currentUser = $authService->user();

$currentPath = '/';

if (isset($_SERVER['REQUEST_URI'])) {
    $parsedPath = parse_url(
        (string) $_SERVER['REQUEST_URI'],
        PHP_URL_PATH
    );

    if (
        is_string($parsedPath) &&
        $parsedPath !== ''
    ) {
        $currentPath =
            rtrim($parsedPath, '/') ?: '/';
    }
}

function navActive(
    string $currentPath,
    string $path
): string {
    return $currentPath === $path
        ? 'active'
        : '';
}

function navGroupActive(
    string $currentPath,
    array $paths
): string {
    foreach ($paths as $path) {
        if (
            $currentPath === $path ||
            str_starts_with(
                $currentPath,
                $path . '/'
            )
        ) {
            return 'active';
        }
    }

    return '';
}

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

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        .navbar .nav-link.active,
        .navbar .dropdown-toggle.active {
            color: #ffffff;
            font-weight: 600;
        }

        .navbar .dropdown-item.active {
            font-weight: 600;
        }

        .navbar-user {
            line-height: 1.2;
            white-space: nowrap;
        }

        @media (min-width: 1400px) {
            .navbar-nav .nav-link {
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>

    <nav
        class="navbar navbar-expand-xxl
        navbar-dark bg-dark mb-4">
        <div class="container-fluid">

            <a
                class="navbar-brand"
                href="/dashboard">
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

            <div
                class="collapse navbar-collapse"
                id="navbarContent">
                <?php if ($currentUser !== null): ?>

                    <ul
                        class="navbar-nav me-auto
                        mb-2 mb-xxl-0">
                        <li class="nav-item">
                            <a
                                href="/dashboard"
                                class="nav-link <?= navActive(
                                    $currentPath,
                                    '/dashboard'
                                ) ?>">
                                Dashboard
                            </a>
                        </li>

                        <?php if (
                            $authService->hasAnyRole([
                                'administrator',
                                'manager',
                            ])
                        ): ?>

                            <li class="nav-item">
                                <a
                                    href="/search"
                                    class="nav-link <?= navActive(
                                        $currentPath,
                                        '/search'
                                    ) ?>">
                                    Search
                                </a>
                            </li>

                            <li class="nav-item dropdown">
                                <a
                                    class="nav-link dropdown-toggle <?= navGroupActive(
                                        $currentPath,
                                        [
                                            '/products',
                                            '/categories',
                                            '/clients',
                                            '/suppliers',
                                        ]
                                    ) ?>"
                                    href="#"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Catalog
                                </a>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a
                                            href="/products"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/products'
                                            ) ?>">
                                            Products
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/categories"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/categories'
                                            ) ?>">
                                            Categories
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/clients"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/clients'
                                            ) ?>">
                                            Clients
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/suppliers"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/suppliers'
                                            ) ?>">
                                            Suppliers
                                        </a>
                                    </li>
                                </ul>
                            </li>

                        <?php endif; ?>

                        <?php if (
                            $authService->hasAnyRole([
                                'administrator',
                                'manager',
                                'employee',
                            ])
                        ): ?>

                            <li class="nav-item dropdown">
                                <a
                                    class="nav-link dropdown-toggle <?= navGroupActive(
                                        $currentPath,
                                        [
                                            '/warehouses',
                                            '/stock',
                                            '/inventory-counts',
                                        ]
                                    ) ?>"
                                    href="#"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Inventory
                                </a>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a
                                            href="/warehouses"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/warehouses'
                                            ) ?>">
                                            Warehouses
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/stock"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/stock'
                                            ) ?>">
                                            Current Stock
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/inventory-counts"
                                            class="dropdown-item <?= navGroupActive(
                                                $currentPath,
                                                [
                                                    '/inventory-counts',
                                                ]
                                            ) ?>">
                                            Inventory Counts
                                        </a>
                                    </li>

                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>

                                    <li>
                                        <a
                                            href="/stock/in"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/stock/in'
                                            ) ?>">
                                            Stock In
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/stock/out"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/stock/out'
                                            ) ?>">
                                            Stock Out
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/stock/transfer"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/stock/transfer'
                                            ) ?>">
                                            Transfer Stock
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/stock/history"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/stock/history'
                                            ) ?>">
                                            Stock History
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a
                                    class="nav-link dropdown-toggle <?= navGroupActive(
                                        $currentPath,
                                        ['/sales']
                                    ) ?>"
                                    href="#"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Sales
                                </a>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a
                                            href="/sales"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/sales'
                                            ) ?>">
                                            All Sales
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/sales/create"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/sales/create'
                                            ) ?>">
                                            New Sale
                                        </a>
                                    </li>

                                    <?php if (
                                        $authService->hasAnyRole([
                                            'administrator',
                                            'manager',
                                        ])
                                    ): ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>

                                        <li>
                                            <a
                                                href="/sales/report"
                                                class="dropdown-item <?= navActive(
                                                    $currentPath,
                                                    '/sales/report'
                                                ) ?>">
                                                Sales Report
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                        <?php endif; ?>

                        <?php if (
                            $authService->hasAnyRole([
                                'administrator',
                                'manager',
                            ])
                        ): ?>

                            <li class="nav-item dropdown">
                                <a
                                    class="nav-link dropdown-toggle <?= navGroupActive(
                                        $currentPath,
                                        ['/purchases']
                                    ) ?>"
                                    href="#"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Purchases
                                </a>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a
                                            href="/purchases"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/purchases'
                                            ) ?>">
                                            All Purchases
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/purchases/create"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/purchases/create'
                                            ) ?>">
                                            New Purchase
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a
                                    class="nav-link dropdown-toggle <?= navGroupActive(
                                        $currentPath,
                                        ['/invoices']
                                    ) ?>"
                                    href="#"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Invoices
                                </a>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a
                                            href="/invoices"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/invoices'
                                            ) ?>">
                                            All Invoices
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/invoices/create"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/invoices/create'
                                            ) ?>">
                                            Create Invoice Draft
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a
                                    href="/payments"
                                    class="nav-link <?= navActive(
                                        $currentPath,
                                        '/payments'
                                    ) ?>">
                                    Payments
                                </a>
                            </li>

                            <li class="nav-item dropdown">
                                <a
                                    class="nav-link dropdown-toggle <?= navGroupActive(
                                        $currentPath,
                                        [
                                            '/stock/report',
                                            '/product-movement/report',
                                            '/receivables',
                                        ]
                                    ) ?>"
                                    href="#"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Reports
                                </a>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a
                                            href="/stock/report"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/stock/report'
                                            ) ?>">
                                            Stock Report
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/product-movement/report"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/product-movement/report'
                                            ) ?>">
                                            Product Movement
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/receivables"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/receivables'
                                            ) ?>">
                                            Receivables
                                        </a>
                                    </li>
                                </ul>
                            </li>

                        <?php endif; ?>

                        <?php if (
                            $authService->hasRole(
                                'administrator'
                            )
                        ): ?>

                            <li class="nav-item dropdown">
                                <a
                                    class="nav-link dropdown-toggle <?= navGroupActive(
                                        $currentPath,
                                        [
                                            '/users',
                                            '/settings',
                                            '/audit-logs',
                                        ]
                                    ) ?>"
                                    href="#"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Administration
                                </a>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a
                                            href="/users"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/users'
                                            ) ?>">
                                            Users
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/settings"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/settings'
                                            ) ?>">
                                            Settings
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="/audit-logs"
                                            class="dropdown-item <?= navActive(
                                                $currentPath,
                                                '/audit-logs'
                                            ) ?>">
                                            Audit Logs
                                        </a>
                                    </li>
                                </ul>
                            </li>

                        <?php endif; ?>

                    </ul>

                    <div
                        class="d-flex align-items-xxl-center
                        gap-3 mt-3 mt-xxl-0 flex-shrink-0">
                        <div class="navbar-user text-white">
                            <div class="fw-semibold">
                                <?= htmlspecialchars(
                                    (string) $currentUser['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                            <small class="text-white-50">
                                <?= htmlspecialchars(
                                    (string) $currentUser['role_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </small>
                        </div>

                        <form
                            action="/logout"
                            method="POST"
                            class="mb-0">
                            <?= \App\Core\Csrf::field() ?>

                            <button
                                type="submit"
                                class="btn btn-outline-light
                                btn-sm">
                                Logout
                            </button>
                        </form>
                    </div>

                <?php else: ?>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a
                                href="/login"
                                class="nav-link <?= navActive(
                                    $currentPath,
                                    '/login'
                                ) ?>">
                                Login
                            </a>
                        </li>
                    </ul>

                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">

        <?php foreach (
            $flashMessages as $type => $messages
        ): ?>
            <?php foreach ($messages as $message): ?>
                <div
                    class="alert alert-<?= htmlspecialchars(
                        (string) $type,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?> alert-dismissible fade show"
                    role="alert">
                    <?= htmlspecialchars(
                        (string) $message,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

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

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>