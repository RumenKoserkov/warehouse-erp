<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    public function allByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value
            FROM settings
            WHERE company_id = ?
            ORDER BY setting_key ASC
        ");

        $stmt->execute([$companyId]);

        $settings = [];

        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public function get(int $companyId, string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare("
            SELECT setting_value
            FROM settings
            WHERE company_id = ?
            AND setting_key = ?
            LIMIT 1
        ");

        $stmt->execute([
            $companyId,
            $key,
        ]);

        $setting = $stmt->fetch();

        if (!$setting) {
            return $default;
        }

        return (string)$setting['setting_value'];
    }

    public function set(int $companyId, string $key, string $value): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO settings
                (
                    company_id,
                    setting_key,
                    setting_value
                )
            VALUES
                (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value)
        ");

        return $stmt->execute([
            $companyId,
            $key,
            $value,
        ]);
    }

    public function updateMany(int $companyId, array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($companyId, $key, (string)$value);
        }
    }
}