<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RoleMiddleware;


class Router
{
    private array $routes = [];

    private array $middlewareMap = [
        'auth' => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
        'role' => RoleMiddleware::class,
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
            $this->notFound();
            return;
        }

        $route = $this->routes[$requestMethod][$path];

        if ($requestMethod === 'POST') {
            $csrfMiddleware = new CsrfMiddleware();

            $csrfMiddleware->handle();
        }

        $this->runMiddleware($route['middleware']);

        [$controllerClass, $method] = $route['action'];

        $controller = new $controllerClass();

        $controller->$method();
    }

    private function runMiddleware(array $middlewares): void
    {
        foreach ($middlewares as $middlewareDefinition) {
            $middlewareName = $middlewareDefinition;
            $parameters = [];

            if (str_contains($middlewareDefinition, ':')) {
                [$middlewareName, $parameterString] = explode(':', $middlewareDefinition, 2);

                $rawParameters = explode(',', $parameterString);

                foreach ($rawParameters as $rawParameter) {
                    $parameter = trim($rawParameter);

                    if ($parameter !== '') {
                        $parameters[] = $parameter;
                    }
                }
            }

            if (!isset($this->middlewareMap[$middlewareName])) {
                throw new \Exception("Middleware {$middlewareName} is not registered.");
            }

            $middlewareClass = $this->middlewareMap[$middlewareName];

            $middleware = new $middlewareClass();

            $middleware->handle($parameters);
        }
    }

    private function notFound(): void
    {
        http_response_code(404);

        View::render('errors/404', [
            'title' => '404 - Page not found'
        ]);
    }
}
