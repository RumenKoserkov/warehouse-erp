<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Warehouse extends Model
{
    public function allByCompany(int $companyId, string $search = ''): array
    {
        if ($search !== '') {
            $searchTerm = '%' . $search . '%';

            $stmt = $this->db->prepare("
                SELECT *
                FROM warehouses
                WHERE company_id = ?
                AND (
                    name LIKE ?
                    OR code LIKE ?
                    OR address LIKE ?
                    OR description LIKE ?
                )
                ORDER BY id DESC
            ");

            $stmt->execute([
                $companyId,
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm,
            ]);

            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM warehouses
            WHERE company_id = ?
            ORDER BY id DESC
        ");

        $stmt->execute([$companyId]);

        return $stmt->fetchAll();
    }

    public function findByIdAndCompany(int $id, int $companyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM warehouses
            WHERE id = ?
            AND company_id = ?
            LIMIT 1
        ");

        $stmt->execute([$id, $companyId]);

        $warehouse = $stmt->fetch();

        if (!$warehouse) {
            return null;
        }

        return $warehouse;
    }

    public function codeExistsInCompany(string $code, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM warehouses
            WHERE code = ?
            AND company_id = ?
            LIMIT 1
        ");

        $stmt->execute([$code, $companyId]);

        return $stmt->fetch() !== false;
    }

    public function codeExistsInCompanyExceptWarehouse(
        string $code,
        int $companyId,
        int $warehouseId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT id
            FROM warehouses
            WHERE code = ?
            AND company_id = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $code,
            $companyId,
            $warehouseId,
        ]);

        return $stmt->fetch() !== false;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO warehouses
                (
                    company_id,
                    name,
                    code,
                    address,
                    description,
                    is_active
                )
            VALUES
                (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['company_id'],
            $data['name'],
            $data['code'],
            $data['address'],
            $data['description'],
            $data['is_active'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE warehouses
            SET
                name = ?,
                code = ?,
                address = ?,
                description = ?,
                is_active = ?
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['code'],
            $data['address'],
            $data['description'],
            $data['is_active'],
            $id,
            $data['company_id'],
        ]);
    }

    public function deactivate(int $id, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE warehouses
            SET is_active = 0
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $id,
            $companyId,
        ]);
    }
}