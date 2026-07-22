<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOStatement;
use Throwable;

class AuditLogService
{
    private PDO $db;

    private static ?string $requestId = null;

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
        string $description,
        array $context = [],
        ?string $severity = null
    ): bool {
        $action = $this->limitText(
            trim($action),
            100
        );

        $entityType = $this->limitText(
            trim($entityType),
            100
        );

        $description = $this->limitText(
            trim($description),
            10000
        );

        $normalizedSeverity =
            $this->normalizeSeverity(
                $severity,
                $action
            );

        $contextJson =
            $this->encodeContext($context);

        $sql = "
            INSERT INTO logs
            (
                company_id,
                user_id,
                action,
                severity,
                entity_type,
                entity_id,
                description,
                request_id,
                request_method,
                request_uri,
                context,
                ip_address,
                user_agent
            )
            VALUES
            (
                :company_id,
                :user_id,
                :action,
                :severity,
                :entity_type,
                :entity_id,
                :description,
                :request_id,
                :request_method,
                :request_uri,
                :context,
                :ip_address,
                :user_agent
            )
        ";

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'company_id' => $companyId,
            'user_id' => $userId,
            'action' => $action,
            'severity' => $normalizedSeverity,

            'entity_type' =>
                $entityType === ''
                    ? null
                    : $entityType,

            'entity_id' => $entityId,

            'description' =>
                $description === ''
                    ? null
                    : $description,

            'request_id' =>
                $this->getRequestId(),

            'request_method' =>
                $this->getRequestMethod(),

            'request_uri' =>
                $this->getRequestUri(),

            'context' => $contextJson,

            'ip_address' =>
                $this->getIpAddress(),

            'user_agent' =>
                $this->getUserAgent(),
        ]);
    }

    public function countByCompany(
        int $companyId,
        array $filters = []
    ): int {
        $parameters = [
            'company_id' => $companyId,
        ];

        $sql = "
            SELECT COUNT(*)
            FROM logs
            LEFT JOIN users
                ON users.id = logs.user_id
            WHERE logs.company_id =
                :company_id
        ";

        $sql .= $this->buildFilters(
            $filters,
            $parameters
        );

        $statement = $this->db->prepare($sql);

        $this->bindValues(
            $statement,
            $parameters
        );

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function paginateByCompany(
        int $companyId,
        array $filters,
        int $limit,
        int $offset
    ): array {
        $parameters = [
            'company_id' => $companyId,
        ];

        $sql = "
            SELECT
                logs.*,
                users.name AS user_name,
                users.email AS user_email
            FROM logs
            LEFT JOIN users
                ON users.id = logs.user_id
            WHERE logs.company_id =
                :company_id
        ";

        $sql .= $this->buildFilters(
            $filters,
            $parameters
        );

        $sql .= "
            ORDER BY logs.id DESC
            LIMIT :limit
            OFFSET :offset
        ";

        $statement = $this->db->prepare($sql);

        $this->bindValues(
            $statement,
            $parameters
        );

        $statement->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll();
    }

    public function findByIdAndCompany(
        int $id,
        int $companyId
    ): ?array {
        $sql = "
            SELECT
                logs.*,
                users.name AS user_name,
                users.email AS user_email
            FROM logs
            LEFT JOIN users
                ON users.id = logs.user_id
            WHERE logs.id = :id
            AND logs.company_id = :company_id
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $log = $statement->fetch();

        if ($log === false) {
            return null;
        }

        $log['context_data'] =
            $this->decodeContext(
                $log['context'] ?? null
            );

        return $log;
    }

    public function summaryByCompany(
        int $companyId
    ): array {
        $sql = "
            SELECT
                COUNT(*) AS total_count,

                COALESCE(
                    SUM(
                        created_at >= CURDATE()
                    ),
                    0
                ) AS today_count,

                COALESCE(
                    SUM(
                        created_at >=
                        DATE_SUB(
                            NOW(),
                            INTERVAL 7 DAY
                        )
                    ),
                    0
                ) AS last_seven_days_count,

                COALESCE(
                    SUM(
                        severity = 'warning'
                        AND created_at >=
                        DATE_SUB(
                            NOW(),
                            INTERVAL 30 DAY
                        )
                    ),
                    0
                ) AS warning_count,

                COALESCE(
                    SUM(
                        severity IN (
                            'error',
                            'critical'
                        )
                        AND created_at >=
                        DATE_SUB(
                            NOW(),
                            INTERVAL 30 DAY
                        )
                    ),
                    0
                ) AS error_count

            FROM logs
            WHERE company_id = :company_id
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
        ]);

        $summary = $statement->fetch();

        if ($summary === false) {
            return [
                'total_count' => 0,
                'today_count' => 0,
                'last_seven_days_count' => 0,
                'warning_count' => 0,
                'error_count' => 0,
            ];
        }

        return [
            'total_count' =>
                (int) $summary['total_count'],

            'today_count' =>
                (int) $summary['today_count'],

            'last_seven_days_count' =>
                (int) $summary[
                    'last_seven_days_count'
                ],

            'warning_count' =>
                (int) $summary['warning_count'],

            'error_count' =>
                (int) $summary['error_count'],
        ];
    }

    public function usersByCompany(
        int $companyId
    ): array {
        $sql = "
            SELECT
                id,
                name,
                email,
                is_active
            FROM users
            WHERE company_id = :company_id
            ORDER BY name ASC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function actions(): array
    {
        return [
            'create',
            'update',
            'activate',
            'deactivate',
            'delete',
            'cancel',
            'issue',
            'complete',
            'stock_in',
            'stock_out',
            'stock_transfer',
            'login',
            'logout',
            'import',
            'export',
            'password_change',
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
            'invoice',
            'credit_note',
            'payment',
            'document_sequence',
            'inventory_count',
            'inventory_adjustment',
            'sales_return',
            'purchase_return',
            'promotion',
            'csv_import',
        ];
    }

    public function severities(): array
    {
        return [
            'info' => 'Info',
            'warning' => 'Warning',
            'error' => 'Error',
            'critical' => 'Critical',
        ];
    }

    private function buildFilters(
        array $filters,
        array &$parameters
    ): string {
        $sql = '';

        if (
            isset($filters['action']) &&
            $filters['action'] !== ''
        ) {
            $sql .= "
                AND logs.action = :filter_action
            ";

            $parameters['filter_action'] =
                $filters['action'];
        }

        if (
            isset($filters['severity']) &&
            $filters['severity'] !== ''
        ) {
            $sql .= "
                AND logs.severity =
                    :filter_severity
            ";

            $parameters['filter_severity'] =
                $filters['severity'];
        }

        if (
            isset($filters['entity_type']) &&
            $filters['entity_type'] !== ''
        ) {
            $sql .= "
                AND logs.entity_type =
                    :filter_entity_type
            ";

            $parameters['filter_entity_type'] =
                $filters['entity_type'];
        }

        if (
            isset($filters['entity_id']) &&
            is_int($filters['entity_id']) &&
            $filters['entity_id'] > 0
        ) {
            $sql .= "
                AND logs.entity_id =
                    :filter_entity_id
            ";

            $parameters['filter_entity_id'] =
                $filters['entity_id'];
        }

        if (
            isset($filters['user_id']) &&
            is_int($filters['user_id']) &&
            $filters['user_id'] > 0
        ) {
            $sql .= "
                AND logs.user_id =
                    :filter_user_id
            ";

            $parameters['filter_user_id'] =
                $filters['user_id'];
        }

        if (
            isset($filters['date_from']) &&
            $filters['date_from'] !== ''
        ) {
            $sql .= "
                AND logs.created_at >=
                    :filter_date_from
            ";

            $parameters['filter_date_from'] =
                $filters['date_from'] .
                ' 00:00:00';
        }

        if (
            isset($filters['date_to']) &&
            $filters['date_to'] !== ''
        ) {
            $sql .= "
                AND logs.created_at <
                    DATE_ADD(
                        :filter_date_to,
                        INTERVAL 1 DAY
                    )
            ";

            $parameters['filter_date_to'] =
                $filters['date_to'];
        }

        if (
            isset($filters['search']) &&
            $filters['search'] !== ''
        ) {
            $sql .= "
                AND (
                    logs.description
                        LIKE :search_description

                    OR users.name
                        LIKE :search_user_name

                    OR users.email
                        LIKE :search_user_email

                    OR logs.ip_address
                        LIKE :search_ip

                    OR logs.request_id
                        LIKE :search_request_id

                    OR logs.request_uri
                        LIKE :search_request_uri
                )
            ";

            $searchValue =
                '%' .
                $filters['search'] .
                '%';

            $parameters['search_description'] =
                $searchValue;

            $parameters['search_user_name'] =
                $searchValue;

            $parameters['search_user_email'] =
                $searchValue;

            $parameters['search_ip'] =
                $searchValue;

            $parameters['search_request_id'] =
                $searchValue;

            $parameters['search_request_uri'] =
                $searchValue;
        }

        return $sql;
    }

    private function bindValues(
        PDOStatement $statement,
        array $parameters
    ): void {
        foreach (
            $parameters as $key => $value
        ) {
            $type = PDO::PARAM_STR;

            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            }

            $statement->bindValue(
                ':' . $key,
                $value,
                $type
            );
        }
    }

    private function encodeContext(
        array $context
    ): ?string {
        if (empty($context)) {
            return null;
        }

        $sanitized =
            $this->sanitizeContext(
                $context
            );

        try {
            return json_encode(
                $sanitized,
                JSON_THROW_ON_ERROR |
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function decodeContext(
        mixed $context
    ): array {
        if (
            $context === null ||
            !is_string($context) ||
            trim($context) === ''
        ) {
            return [];
        }

        try {
            $decoded = json_decode(
                $context,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($decoded)) {
                return [];
            }

            return $decoded;
        } catch (Throwable) {
            return [];
        }
    }

    private function sanitizeContext(
        array $context,
        int $depth = 0
    ): array {
        if ($depth >= 5) {
            return [
                '_truncated' =>
                    'Maximum context depth reached.',
            ];
        }

        $result = [];
        $processed = 0;

        foreach ($context as $key => $value) {
            $processed++;

            if ($processed > 100) {
                $result['_truncated'] =
                    'Maximum context item count reached.';

                break;
            }

            $stringKey = (string) $key;

            if (
                $this->isSensitiveKey(
                    $stringKey
                )
            ) {
                $result[$stringKey] =
                    '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $result[$stringKey] =
                    $this->sanitizeContext(
                        $value,
                        $depth + 1
                    );

                continue;
            }

            if ($value === null) {
                $result[$stringKey] = null;

                continue;
            }

            if (
                is_bool($value) ||
                is_int($value) ||
                is_float($value)
            ) {
                $result[$stringKey] =
                    $value;

                continue;
            }

            if (is_string($value)) {
                $result[$stringKey] =
                    $this->limitText(
                        $value,
                        2000
                    );

                continue;
            }

            $result[$stringKey] =
                '[UNSUPPORTED VALUE]';
        }

        return $result;
    }

    private function isSensitiveKey(
        string $key
    ): bool {
        $key = strtolower($key);

        $sensitiveParts = [
            'password',
            'passwd',
            'token',
            'secret',
            'authorization',
            'cookie',
            'session_id',
            'api_key',
            'api-key',
            'csrf',
            'cvv',
        ];

        foreach (
            $sensitiveParts as
            $sensitivePart
        ) {
            if (
                str_contains(
                    $key,
                    $sensitivePart
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSeverity(
        ?string $severity,
        string $action
    ): string {
        if (
            $severity !== null &&
            array_key_exists(
                $severity,
                $this->severities()
            )
        ) {
            return $severity;
        }

        if (
            in_array(
                $action,
                [
                    'cancel',
                    'delete',
                    'deactivate',
                ],
                true
            )
        ) {
            return 'warning';
        }

        return 'info';
    }

    private function getRequestId(): string
    {
        if (self::$requestId !== null) {
            return self::$requestId;
        }

        try {
            self::$requestId = bin2hex(
                random_bytes(16)
            );
        } catch (Throwable) {
            self::$requestId = md5(
                uniqid('', true)
            );
        }

        return self::$requestId;
    }

    private function getRequestMethod(): string
    {
        if (
            !isset(
                $_SERVER['REQUEST_METHOD']
            )
        ) {
            return 'UNKNOWN';
        }

        return $this->limitText(
            strtoupper(
                (string) $_SERVER['REQUEST_METHOD']
            ),
            10
        );
    }

    private function getRequestUri(): string
    {
        if (
            !isset(
                $_SERVER['REQUEST_URI']
            )
        ) {
            return '';
        }

        return $this->limitText(
            (string) $_SERVER['REQUEST_URI'],
            2048
        );
    }

    private function getIpAddress(): string
    {
        if (
            isset($_SERVER['REMOTE_ADDR'])
        ) {
            return $this->limitText(
                (string) $_SERVER['REMOTE_ADDR'],
                45
            );
        }

        return 'unknown';
    }

    private function getUserAgent(): string
    {
        if (
            isset(
                $_SERVER['HTTP_USER_AGENT']
            )
        ) {
            return $this->limitText(
                (string) $_SERVER[
                    'HTTP_USER_AGENT'
                ],
                255
            );
        }

        return 'unknown';
    }

    private function limitText(
        string $value,
        int $maximumLength
    ): string {
        if (
            mb_strlen($value) <=
            $maximumLength
        ) {
            return $value;
        }

        return mb_substr(
            $value,
            0,
            $maximumLength
        );
    }
}