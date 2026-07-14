<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function json(
        array $data,
        int $statusCode = 200
    ): void {
        http_response_code($statusCode);

        header('Content-Type: application/json; charset=UTF-8');

        $json = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PRESERVE_ZERO_FRACTION
        );

        if ($json === false) {
            http_response_code(500);

            echo '{"error":"Unable to create JSON response."}';

            return;
        }

        echo $json;
    }

    protected function abort(int $statusCode): void
    {
        http_response_code($statusCode);

        if ($statusCode === 403) {
            View::render('errors/403', [
                'title' => '403 - Access denied'
            ]);
            exit;
        }

        if ($statusCode === 404) {
            View::render('errors/404', [
                'title' => '404 - Page not found'
            ]);
            exit;
        }

        View::render('errors/500', [
            'title' => '500 - Server error'
        ]);

        exit;
    }
}