<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class AuthService
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->userModel->findByEmail($email);

        if ($user === null) {
            return false;
        }

        if ((int)$user['is_active'] !== 1) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        $this->startSession();

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'company_id' => (int)$user['company_id'],
            'role_id' => (int)$user['role_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role_name' => $user['role_name'],
            'role_slug' => $user['role_slug'],
            'company_name' => $user['company_name'],
        ];

        $this->userModel->updateLastLogin((int)$user['id']);

        return true;
    }

    public function logout(): void
    {
        $this->startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function check(): bool
    {
        $this->startSession();

        return isset($_SESSION['user']);
    }

    public function user(): ?array
    {
        $this->startSession();

        if (!isset($_SESSION['user'])) {
            return null;
        }

        return $_SESSION['user'];
    }

    public function id(): ?int
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        return (int)$user['id'];
    }

    public function hasRole(string $roleSlug): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user['role_slug'] === $roleSlug;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}