<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\LoginThrottleService;

class AuthController extends Controller
{
    private AuthService $authService;

    private AuditLogService
        $auditLogService;

    private LoginThrottleService
        $loginThrottleService;

    public function __construct()
    {
        $this->authService =
            new AuthService();

        $this->auditLogService =
            new AuditLogService();

        $this->loginThrottleService =
            new LoginThrottleService();
    }

    public function showLogin(): void
    {
        $this->view(
            'auth/login',
            [
                'title' => 'Login',

                'errors' => [],

                'old' => [
                    'email' => '',
                ],
            ]
        );
    }

    public function login(): void
    {
        $email = '';

        if (
            isset($_POST['email']) &&
            is_scalar($_POST['email'])
        ) {
            $email = mb_strtolower(
                trim(
                    (string) $_POST['email']
                )
            );
        }

        $password = '';

        if (
            isset($_POST['password']) &&
            is_scalar($_POST['password'])
        ) {
            $password =
                (string) $_POST['password'];
        }

        $validator =
            new Validator($_POST);

        $validator
            ->required(
                'email',
                'Email is required.'
            )
            ->email(
                'email',
                'Email must be a valid email address.'
            )
            ->required(
                'password',
                'Password is required.'
            );

        $errors = $validator->all();

        if (mb_strlen($email) > 255) {
            $errors[] =
                'Email must be maximum 255 characters.';
        }

        if (mb_strlen($password) > 4096) {
            $errors[] =
                'Password input is too long.';
        }

        if (!empty($errors)) {
            $this->renderLogin(
                $errors,
                $email
            );

            return;
        }

        $ipAddress =
            $this->loginThrottleService
                ->clientIp();

        $throttleStatus =
            $this->loginThrottleService
                ->status(
                    $email,
                    $ipAddress
                );

        if (
            !$throttleStatus['allowed']
        ) {
            $minutes = max(
                1,
                (int) ceil(
                    (int) $throttleStatus[
                        'retry_after_seconds'
                    ] / 60
                )
            );

            $this->renderLogin(
                [
                    'Too many login attempts. Try again in approximately ' .
                    $minutes .
                    ' minute(s).',
                ],
                $email
            );

            return;
        }

        $isLoggedIn =
            $this->authService->login(
                $email,
                $password
            );

        if (!$isLoggedIn) {
            $failureStatus =
                $this->loginThrottleService
                    ->recordFailure(
                        $email,
                        $ipAddress
                    );

            if (
                !$failureStatus['allowed']
            ) {
                $minutes = max(
                    1,
                    (int) ceil(
                        (int) $failureStatus[
                            'retry_after_seconds'
                        ] / 60
                    )
                );

                $this->renderLogin(
                    [
                        'Too many login attempts. Try again in approximately ' .
                        $minutes .
                        ' minute(s).',
                    ],
                    $email
                );

                return;
            }

            $this->renderLogin(
                [
                    'Invalid email or password.',
                ],
                $email
            );

            return;
        }

        $this->loginThrottleService
            ->clear(
                $email,
                $ipAddress
            );

        $currentUser =
            $this->authService->user();

        if ($currentUser !== null) {
            $this->auditLogService->log(
                (int) $currentUser[
                    'company_id'
                ],

                (int) $currentUser['id'],

                'login',

                'user',

                (int) $currentUser['id'],

                'User logged in.',

                [
                    'email' =>
                        (string) $currentUser[
                            'email'
                        ],

                    'role' =>
                        (string) $currentUser[
                            'role_slug'
                        ],
                ]
            );
        }

        Flash::success(
            'Login successful.'
        );

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser !== null) {
            $this->auditLogService->log(
                (int) $currentUser[
                    'company_id'
                ],

                (int) $currentUser['id'],

                'logout',

                'user',

                (int) $currentUser['id'],

                'User logged out.'
            );
        }

        $this->authService->logout();

        Flash::success(
            'Logged out successfully.'
        );

        $this->redirect('/login');
    }

    private function renderLogin(
        array $errors,
        string $email
    ): void {
        $this->view(
            'auth/login',
            [
                'title' => 'Login',

                'errors' => $errors,

                'old' => [
                    'email' => $email,
                ],
            ]
        );
    }
}