<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
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

        Session::set('user', [
            'id' => (int)$user['id'],
            'company_id' => (int)$user['company_id'],
            'role_id' => (int)$user['role_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role_name' => $user['role_name'],
            'role_slug' => $user['role_slug'],
            'company_name' => $user['company_name'],
        ]);

        $this->userModel->updateLastLogin((int)$user['id']);

        return true;
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function check(): bool
    {
        return Session::has('user');
    }

    public function user(): ?array
    {
        $user = Session::get('user');

        if ($user === null) {
            return null;
        }

        return $user;
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

    public function hasAnyRole(array $roleSlugs): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        foreach ($roleSlugs as $roleSlug) {
            if ($user['role_slug'] === $roleSlug) {
                return true;
            }
        }

        return false;
    }
}