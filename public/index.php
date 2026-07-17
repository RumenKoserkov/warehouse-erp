<?php

declare(strict_types=1);

use App\Core\Router;

require_once
    __DIR__ .
    '/../bootstrap/app.php';

$router = new Router();

$routes = require
    __DIR__ .
    '/../routes/web.php';

foreach ($routes as $route) {
    $router->add(
        $route['method'],
        $route['uri'],
        $route['action'],
        $route['middleware'] ?? []
    );
}

$requestUri = '/';

if (
    isset($_SERVER['REQUEST_URI']) &&
    is_string(
        $_SERVER['REQUEST_URI']
    )
) {
    $requestUri =
        $_SERVER['REQUEST_URI'];
}

$requestMethod = 'GET';

if (
    isset(
        $_SERVER[
            'REQUEST_METHOD'
        ]
    ) &&
    is_string(
        $_SERVER[
            'REQUEST_METHOD'
        ]
    )
) {
    $requestMethod =
        $_SERVER[
            'REQUEST_METHOD'
        ];
}

$router->dispatch(
    $requestUri,
    $requestMethod
);