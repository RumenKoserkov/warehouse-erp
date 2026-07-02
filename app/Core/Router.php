<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

class Router
{
    private array $routes = [];

    private array $middlewareMap = [
        'auth' => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
    ];

    public function get(string $uri, array $action, array $middleware = []): void
    {
        $this->add('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, array $action, array $middleware = []): void
    {
        $this->add('POST', $uri, $action, $middleware);
    }

    public function add(string $method, string $uri, array $action, array $middleware = []): void
    {
        $this->routes[strtoupper($method)][$uri] = [
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $requestUri, string $requestMethod): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $requestMethod = strtoupper($requestMethod);

        if (!isset($this->routes[$requestMethod][$path])) {
            http_response_code(404);
            echo '404 - Page not found';
            return;
        }

        $route = $this->routes[$requestMethod][$path];

        $this->runMiddleware($route['middleware']);

        [$controllerClass, $method] = $route['action'];

        $controller = new $controllerClass();

        $controller->$method();
    }

    private function runMiddleware(array $middlewares): void
    {
        foreach ($middlewares as $middlewareName) {
            if (!isset($this->middlewareMap[$middlewareName])) {
                throw new \Exception("Middleware {$middlewareName} is not registered.");
            }

            $middlewareClass = $this->middlewareMap[$middlewareName];

            $middleware = new $middlewareClass();

            $middleware->handle();
        }
    }
}