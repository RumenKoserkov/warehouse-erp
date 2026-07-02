<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

class GuestMiddleware
{
    public function handle(): void
    {
        $authService = new AuthService();

        if ($authService->check()) {
            header('Location: /dashboard');
            exit;
        }
    }
}