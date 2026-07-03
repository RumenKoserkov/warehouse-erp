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

    public function findByIdAndCompany(int $id, int $companyId): ?array
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
                users.created_at,
                roles.name AS role_name,
                roles.slug AS role_slug
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            WHERE users.id = ?
            AND users.company_id = ?
            LIMIT 1
        ");

        $stmt->execute([$id, $companyId]);

        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        return $user;
    }

    public function allByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                users.id,
                users.name,
                users.email,
                users.is_active,
                users.last_login_at,
                users.created_at,
                roles.name AS role_name,
                roles.slug AS role_slug
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            WHERE users.company_id = ?
            ORDER BY users.id DESC
        ");

        $stmt->execute([$companyId]);

        return $stmt->fetchAll();
    }

    public function emailExistsInCompany(string $email, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND company_id = ?
            LIMIT 1
        ");

        $stmt->execute([$email, $companyId]);

        $user = $stmt->fetch();

        return $user !== false;
    }

    public function emailExistsInCompanyExceptUser(string $email, int $companyId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND company_id = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([$email, $companyId, $userId]);

        $user = $stmt->fetch();

        return $user !== false;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO users
                (company_id, role_id, name, email, password, is_active)
            VALUES
                (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['company_id'],
            $data['role_id'],
            $data['name'],
            $data['email'],
            $data['password'],
            $data['is_active'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare("
                UPDATE users
                SET 
                    role_id = ?,
                    name = ?,
                    email = ?,
                    password = ?,
                    is_active = ?
                WHERE id = ?
                AND company_id = ?
            ");

            return $stmt->execute([
                $data['role_id'],
                $data['name'],
                $data['email'],
                $data['password'],
                $data['is_active'],
                $id,
                $data['company_id'],
            ]);
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET 
                role_id = ?,
                name = ?,
                email = ?,
                is_active = ?
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $data['role_id'],
            $data['name'],
            $data['email'],
            $data['is_active'],
            $id,
            $data['company_id'],
        ]);
    }

    public function deactivate(int $id, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET is_active = 0
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([$id, $companyId]);
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