<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class AuditLogService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function log(
        ?int $companyId,
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        string $description
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO logs
                (
                    company_id,
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    description,
                    ip_address,
                    user_agent
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $companyId,
            $userId,
            $action,
            $entityType,
            $entityId,
            $description,
            $this->getIpAddress(),
            $this->getUserAgent(),
        ]);
    }

    public function allByCompany(int $companyId, array $filters = []): array
    {
        $sql = "
            SELECT
                logs.*,
                users.name AS user_name,
                users.email AS user_email
            FROM logs
            LEFT JOIN users ON logs.user_id = users.id
            WHERE logs.company_id = ?
        ";

        $params = [$companyId];

        if (!empty($filters['action'])) {
            $sql .= " AND logs.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $sql .= " AND logs.entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(logs.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(logs.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= "
                AND (
                    logs.description LIKE ?
                    OR users.name LIKE ?
                    OR users.email LIKE ?
                    OR logs.ip_address LIKE ?
                )
            ";

            $searchTerm = '%' . $filters['search'] . '%';

            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= "
            ORDER BY logs.id DESC
            LIMIT 200
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function actions(): array
    {
        return [
            'create',
            'update',
            'deactivate',
            'cancel',
            'stock_in',
            'stock_out',
            'stock_transfer',
            'login',
            'logout',
        ];
    }

    public function entityTypes(): array
    {
        return [
            'product',
            'sale',
            'purchase',
            'stock',
            'client',
            'supplier',
            'category',
            'warehouse',
            'user',
            'settings',
            'company',
        ];
    }

    private function getIpAddress(): string
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return (string)$_SERVER['REMOTE_ADDR'];
        }

        return 'unknown';
    }

    private function getUserAgent(): string
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return (string)$_SERVER['HTTP_USER_AGENT'];
        }

        return 'unknown';
    }
}
