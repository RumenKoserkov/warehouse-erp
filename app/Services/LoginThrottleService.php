<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Environment;
use DateTimeImmutable;
use PDO;
use Throwable;

class LoginThrottleService
{
    private PDO $db;

    private int $maximumAttempts;

    private int $lockMinutes;

    public function __construct()
    {
        $this->db =
            Database::getConnection();

        $this->maximumAttempts = max(
            3,
            min(
                20,
                Environment::integer(
                    'LOGIN_MAX_ATTEMPTS',
                    5
                )
            )
        );

        $this->lockMinutes = max(
            1,
            min(
                1440,
                Environment::integer(
                    'LOGIN_LOCK_MINUTES',
                    15
                )
            )
        );
    }

    public function status(
        string $email,
        string $ipAddress
    ): array {
        $this->cleanup();

        $attempt =
            $this->find(
                $email,
                $ipAddress
            );

        if ($attempt === null) {
            return $this->allowedResult(
                $this->maximumAttempts
            );
        }

        $now = new DateTimeImmutable();

        $lockedUntil =
            $this->dateValue(
                $attempt['locked_until']
            );

        if (
            $lockedUntil !== null &&
            $lockedUntil > $now
        ) {
            return [
                'allowed' => false,

                'remaining_attempts' =>
                    0,

                'retry_after_seconds' =>
                    max(
                        1,
                        $lockedUntil
                            ->getTimestamp() -
                        $now->getTimestamp()
                    ),
            ];
        }

        $lastAttempt =
            $this->dateValue(
                $attempt['last_attempt_at']
            );

        $windowStart = $now->modify(
            '-' .
            $this->lockMinutes .
            ' minutes'
        );

        if (
            $lastAttempt === null ||
            $lastAttempt < $windowStart
        ) {
            $this->clear(
                $email,
                $ipAddress
            );

            return $this->allowedResult(
                $this->maximumAttempts
            );
        }

        $remaining = max(
            0,
            $this->maximumAttempts -
            (int) $attempt[
                'attempt_count'
            ]
        );

        return $this->allowedResult(
            $remaining
        );
    }

    public function recordFailure(
        string $email,
        string $ipAddress
    ): array {
        try {
            $this->db->beginTransaction();

            $attempt =
                $this->findForUpdate(
                    $email,
                    $ipAddress
                );

            $now = new DateTimeImmutable();

            $windowStart = $now->modify(
                '-' .
                $this->lockMinutes .
                ' minutes'
            );

            $attemptCount = 1;
            $firstAttemptAt = $now;

            if ($attempt !== null) {
                $lockedUntil =
                    $this->dateValue(
                        $attempt[
                            'locked_until'
                        ]
                    );

                if (
                    $lockedUntil !== null &&
                    $lockedUntil > $now
                ) {
                    $this->db->commit();

                    return [
                        'allowed' => false,

                        'remaining_attempts' =>
                            0,

                        'retry_after_seconds' =>
                            max(
                                1,
                                $lockedUntil
                                    ->getTimestamp() -
                                $now->getTimestamp()
                            ),
                    ];
                }

                $lastAttempt =
                    $this->dateValue(
                        $attempt[
                            'last_attempt_at'
                        ]
                    );

                if (
                    $lastAttempt !== null &&
                    $lastAttempt >=
                        $windowStart
                ) {
                    $attemptCount =
                        (int) $attempt[
                            'attempt_count'
                        ] + 1;

                    $storedFirst =
                        $this->dateValue(
                            $attempt[
                                'first_attempt_at'
                            ]
                        );

                    if (
                        $storedFirst !== null
                    ) {
                        $firstAttemptAt =
                            $storedFirst;
                    }
                }
            }

            $lockedUntil = null;

            if (
                $attemptCount >=
                $this->maximumAttempts
            ) {
                $lockedUntil = $now->modify(
                    '+' .
                    $this->lockMinutes .
                    ' minutes'
                );
            }

            $this->saveAttempt(
                $email,
                $ipAddress,
                $attemptCount,
                $firstAttemptAt,
                $now,
                $lockedUntil
            );

            $this->db->commit();

            if ($lockedUntil !== null) {
                return [
                    'allowed' => false,

                    'remaining_attempts' =>
                        0,

                    'retry_after_seconds' =>
                        $this->lockMinutes * 60,
                ];
            }

            return $this->allowedResult(
                max(
                    0,
                    $this->maximumAttempts -
                    $attemptCount
                )
            );
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function clear(
        string $email,
        string $ipAddress
    ): void {
        $sql = "
            DELETE FROM login_attempts
            WHERE email_hash = :email_hash
            AND ip_address = :ip_address
        ";

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute([
            'email_hash' =>
                $this->emailHash($email),

            'ip_address' =>
                $ipAddress,
        ]);
    }

    public function clientIp(): string
    {
        if (
            !isset(
                $_SERVER[
                    'REMOTE_ADDR'
                ]
            ) ||
            !is_string(
                $_SERVER[
                    'REMOTE_ADDR'
                ]
            )
        ) {
            return 'unknown';
        }

        $ipAddress = trim(
            $_SERVER['REMOTE_ADDR']
        );

        if (
            filter_var(
                $ipAddress,
                FILTER_VALIDATE_IP
            ) === false
        ) {
            return 'unknown';
        }

        return $ipAddress;
    }

    private function find(
        string $email,
        string $ipAddress
    ): ?array {
        $sql = "
            SELECT *
            FROM login_attempts
            WHERE email_hash = :email_hash
            AND ip_address = :ip_address
            LIMIT 1
        ";

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute([
            'email_hash' =>
                $this->emailHash($email),

            'ip_address' =>
                $ipAddress,
        ]);

        $attempt = $statement->fetch();

        if ($attempt === false) {
            return null;
        }

        return $attempt;
    }

    private function findForUpdate(
        string $email,
        string $ipAddress
    ): ?array {
        $sql = "
            SELECT *
            FROM login_attempts
            WHERE email_hash = :email_hash
            AND ip_address = :ip_address
            LIMIT 1
            FOR UPDATE
        ";

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute([
            'email_hash' =>
                $this->emailHash($email),

            'ip_address' =>
                $ipAddress,
        ]);

        $attempt = $statement->fetch();

        if ($attempt === false) {
            return null;
        }

        return $attempt;
    }

    private function saveAttempt(
        string $email,
        string $ipAddress,
        int $attemptCount,
        DateTimeImmutable $firstAttemptAt,
        DateTimeImmutable $lastAttemptAt,
        ?DateTimeImmutable $lockedUntil
    ): void {
        $sql = "
            INSERT INTO login_attempts
            (
                email_hash,
                ip_address,
                attempt_count,
                first_attempt_at,
                last_attempt_at,
                locked_until
            )
            VALUES
            (
                :email_hash,
                :ip_address,
                :attempt_count,
                :first_attempt_at,
                :last_attempt_at,
                :locked_until
            )
            ON DUPLICATE KEY UPDATE
                attempt_count =
                    VALUES(attempt_count),

                first_attempt_at =
                    VALUES(first_attempt_at),

                last_attempt_at =
                    VALUES(last_attempt_at),

                locked_until =
                    VALUES(locked_until),

                updated_at = NOW()
        ";

        $statement = $this->db->prepare(
            $sql
        );

        $lockedUntilValue = null;

        if ($lockedUntil !== null) {
            $lockedUntilValue =
                $lockedUntil->format(
                    'Y-m-d H:i:s'
                );
        }

        $statement->execute([
            'email_hash' =>
                $this->emailHash($email),

            'ip_address' =>
                $ipAddress,

            'attempt_count' =>
                $attemptCount,

            'first_attempt_at' =>
                $firstAttemptAt->format(
                    'Y-m-d H:i:s'
                ),

            'last_attempt_at' =>
                $lastAttemptAt->format(
                    'Y-m-d H:i:s'
                ),

            'locked_until' =>
                $lockedUntilValue,
        ]);
    }

    private function emailHash(
        string $email
    ): string {
        return hash(
            'sha256',
            mb_strtolower(
                trim($email)
            )
        );
    }

    private function dateValue(
        mixed $value
    ): ?DateTimeImmutable {
        if (
            $value === null ||
            !is_scalar($value)
        ) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable(
                $value
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function allowedResult(
        int $remainingAttempts
    ): array {
        return [
            'allowed' => true,

            'remaining_attempts' =>
                max(
                    0,
                    $remainingAttempts
                ),

            'retry_after_seconds' =>
                0,
        ];
    }

    private function cleanup(): void
    {
        $sql = "
            DELETE FROM login_attempts
            WHERE last_attempt_at <
                DATE_SUB(
                    NOW(),
                    INTERVAL 1 DAY
                )

            AND (
                locked_until IS NULL
                OR locked_until < NOW()
            )
        ";

        $this->db->exec($sql);
    }
}