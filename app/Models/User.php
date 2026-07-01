<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                users.id,
                users.company_id,
                users.role_id,
                users.name,
                users.email,
                users.password,
                users.is_active,
                users.last_login_at,
                roles.name AS role_name,
                roles.slug AS role_slug,
                companies.name AS company_name
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            INNER JOIN companies ON users.company_id = companies.id
            WHERE users.email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        return $user;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                users.id,
                users.company_id,
                users.role_id,
                users.name,
                users.email,
                users.is_active,
                users.last_login_at,
                roles.name AS role_name,
                roles.slug AS role_slug,
                companies.name AS company_name
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            INNER JOIN companies ON users.company_id = companies.id
            WHERE users.id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        return $user;
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET last_login_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$id]);
    }
}