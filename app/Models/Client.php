<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Client extends Model
{
    public function allByCompany(int $companyId, string $search = ''): array
    {
        if ($search !== '') {
            $searchTerm = '%' . $search . '%';

            $stmt = $this->db->prepare("
                SELECT *
                FROM clients
                WHERE company_id = ?
                AND (
                    name LIKE ?
                    OR phone LIKE ?
                    OR email LIKE ?
                    OR company_name LIKE ?
                    OR eik LIKE ?
                    OR contact_person LIKE ?
                )
                ORDER BY id DESC
            ");

            $stmt->execute([
                $companyId,
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm,
            ]);

            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM clients
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
            FROM clients
            WHERE id = ?
            AND company_id = ?
            LIMIT 1
        ");

        $stmt->execute([$id, $companyId]);

        $client = $stmt->fetch();

        if (!$client) {
            return null;
        }

        return $client;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO clients
                (company_id, name, phone, email, address, company_name, eik, contact_person, is_active)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['company_id'],
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['company_name'],
            $data['eik'],
            $data['contact_person'],
            $data['is_active'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE clients
            SET
                name = ?,
                phone = ?,
                email = ?,
                address = ?,
                company_name = ?,
                eik = ?,
                contact_person = ?,
                is_active = ?
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['company_name'],
            $data['eik'],
            $data['contact_person'],
            $data['is_active'],
            $id,
            $data['company_id'],
        ]);
    }

    public function deactivate(int $id, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE clients
            SET is_active = 0
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([$id, $companyId]);
    }

    public function activeByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare("
        SELECT id, name, company_name
        FROM clients
        WHERE company_id = ?
        AND is_active = 1
        ORDER BY name ASC
    ");

        $stmt->execute([$companyId]);

        return $stmt->fetchAll();
    }
}
