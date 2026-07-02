<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
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

        Flash::success('Login successful.');

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->authService->logout();

        Flash::success('Logged out successfully.');

        $this->redirect('/login');
    }
}