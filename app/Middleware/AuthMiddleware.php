<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Flash;
use App\Services\AuthService;

class AuthMiddleware
{
    public function handle(
        array $parameters = []
    ): void {
        $authService =
            new AuthService();

        if (!$authService->check()) {
            header(
                'Location: /login'
            );

            exit;
        }

        if (
            !$authService
                ->validateSession()
        ) {
            Flash::danger(
                'Your session expired or is no longer valid. Please log in again.'
            );

            header(
                'Location: /login'
            );

            exit;
        }
    }
}