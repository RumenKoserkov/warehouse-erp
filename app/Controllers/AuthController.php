<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
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
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $errors = [];

        if ($email === '') {
            $errors[] = 'Email is required.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if (!empty($errors)) {
            $this->view('auth/login', [
                'title' => 'Login',
                'errors' => $errors,
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

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->authService->logout();

        $this->redirect('/login');
    }
}