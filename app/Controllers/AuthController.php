<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\AuthService;
use App\Services\AuditLogService;

class AuthController extends Controller
{
    private AuthService $authService;
    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->auditLogService = new AuditLogService();
    }

    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title' => 'Login',
            'errors' => [],
            'old' => [
                'email' => ''
            ]
        ]);
    }

    public function login(): void
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $validator = new Validator($_POST);

        $validator
            ->required('email', 'Email is required.')
            ->email('email', 'Email must be a valid email address.')
            ->required('password', 'Password is required.');

        if ($validator->fails()) {
            $this->view('auth/login', [
                'title' => 'Login',
                'errors' => $validator->all(),
                'old' => [
                    'email' => $email
                ]
            ]);

            return;
        }

        $isLoggedIn = $this->authService->login($email, $password);

        if (!$isLoggedIn) {
            $this->view('auth/login', [
                'title' => 'Login',
                'errors' => [
                    'Invalid email or password.'
                ],
                'old' => [
                    'email' => $email
                ]
            ]);

            return;
        }

        $currentUser = $this->authService->user();

        if ($currentUser !== null) {
            $this->auditLogService->log(
                (int)$currentUser['company_id'],
                (int)$currentUser['id'],
                'login',
                'user',
                (int)$currentUser['id'],
                'User logged in.'
            );
        }

        Flash::success('Login successful.');

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $currentUser = $this->authService->user();

        if ($currentUser !== null) {
            $this->auditLogService->log(
                (int)$currentUser['company_id'],
                (int)$currentUser['id'],
                'logout',
                'user',
                (int)$currentUser['id'],
                'User logged out.'
            );
        }

        $this->authService->logout();

        Flash::success('Logged out successfully.');

        $this->redirect('/login');
    }
}
