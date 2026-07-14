<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\View;

final class CsrfMiddleware
{
    public function handle(): void
    {
        $requestMethod = 'GET';

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $requestMethod = strtoupper(
                (string) $_SERVER['REQUEST_METHOD']
            );
        }

        if ($requestMethod !== 'POST') {
            return;
        }

        $submittedToken = null;

        if (
            isset($_POST['_csrf_token']) &&
            is_string($_POST['_csrf_token'])
        ) {
            $submittedToken = $_POST['_csrf_token'];
        }

        if (Csrf::validate($submittedToken)) {
            return;
        }

        http_response_code(419);

        View::render('errors/419', [
            'title' => 'Page Expired',
        ]);

        exit;
    }
}