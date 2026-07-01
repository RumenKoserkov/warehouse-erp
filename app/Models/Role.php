<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Role extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query("
            SELECT
                id,
                name,
                slug
            FROM roles
            ORDER BY id ASC
        ");

        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                name,
                slug
            FROM roles
            WHERE slug = ?
            LIMIT 1
        ");

        $stmt->execute([$slug]);

        $role = $stmt->fetch();

        if (!$role) {
            return null;
        }

        return $role;
    }
}