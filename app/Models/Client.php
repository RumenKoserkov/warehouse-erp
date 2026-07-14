<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Client extends Model
{
    public function allByCompany(
        int $companyId,
        string $search = ''
    ): array {
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
                    OR vat_number LIKE ?
                    OR contact_person LIKE ?
                    OR billing_email LIKE ?
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

    public function findByIdAndCompany(
        int $id,
        int $companyId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM clients
            WHERE id = ?
            AND company_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id,
            $companyId,
        ]);

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
            (
                company_id,
                name,
                phone,
                email,
                address,
                company_name,
                eik,
                contact_person,
                client_type,
                vat_number,
                billing_address,
                billing_city,
                billing_postal_code,
                billing_country,
                billing_email,
                is_active
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?
            )
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
            $data['client_type'],
            $data['vat_number'],
            $data['billing_address'],
            $data['billing_city'],
            $data['billing_postal_code'],
            $data['billing_country'],
            $data['billing_email'],
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
                client_type = ?,
                vat_number = ?,
                billing_address = ?,
                billing_city = ?,
                billing_postal_code = ?,
                billing_country = ?,
                billing_email = ?,
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
            $data['client_type'],
            $data['vat_number'],
            $data['billing_address'],
            $data['billing_city'],
            $data['billing_postal_code'],
            $data['billing_country'],
            $data['billing_email'],
            $data['is_active'],
            $id,
            $data['company_id'],
        ]);
    }

    public function eikExistsInCompany(
        string $eik,
        int $companyId,
        int $exceptClientId = 0
    ): bool {
        $sql = "
            SELECT id
            FROM clients
            WHERE company_id = ?
            AND eik = ?
        ";

        $parameters = [
            $companyId,
            $eik,
        ];

        if ($exceptClientId > 0) {
            $sql .= "
                AND id != ?
            ";

            $parameters[] = $exceptClientId;
        }

        $sql .= "
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parameters);

        return $stmt->fetch() !== false;
    }

    public function vatNumberExistsInCompany(
        string $vatNumber,
        int $companyId,
        int $exceptClientId = 0
    ): bool {
        $sql = "
            SELECT id
            FROM clients
            WHERE company_id = ?
            AND vat_number = ?
        ";

        $parameters = [
            $companyId,
            $vatNumber,
        ];

        if ($exceptClientId > 0) {
            $sql .= "
                AND id != ?
            ";

            $parameters[] = $exceptClientId;
        }

        $sql .= "
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parameters);

        return $stmt->fetch() !== false;
    }

    public function deactivate(
        int $id,
        int $companyId
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE clients
            SET is_active = 0
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $id,
            $companyId,
        ]);
    }

    public function activeByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                name,
                company_name
            FROM clients
            WHERE company_id = ?
            AND is_active = 1
            ORDER BY name ASC
        ");

        $stmt->execute([$companyId]);

        return $stmt->fetchAll();
    }
}