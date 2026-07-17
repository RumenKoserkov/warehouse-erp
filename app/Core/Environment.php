<?php

declare(strict_types=1);

namespace App\Core;

final class Environment
{
    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        if (
            array_key_exists(
                $key,
                $_ENV
            )
        ) {
            return $_ENV[$key];
        }

        if (
            array_key_exists(
                $key,
                $_SERVER
            )
        ) {
            return $_SERVER[$key];
        }

        return $default;
    }

    public static function string(
        string $key,
        string $default = ''
    ): string {
        $value = self::get(
            $key,
            $default
        );

        if (!is_scalar($value)) {
            return $default;
        }

        return trim((string) $value);
    }

    public static function integer(
        string $key,
        int $default = 0
    ): int {
        $value = self::string(
            $key,
            (string) $default
        );

        if (
            preg_match(
                '/^-?\d+$/',
                $value
            ) !== 1
        ) {
            return $default;
        }

        return (int) $value;
    }

    public static function boolean(
        string $key,
        bool $default = false
    ): bool {
        $value = strtolower(
            self::string($key)
        );

        if (
            in_array(
                $value,
                [
                    '1',
                    'true',
                    'yes',
                    'on',
                ],
                true
            )
        ) {
            return true;
        }

        if (
            in_array(
                $value,
                [
                    '0',
                    'false',
                    'no',
                    'off',
                ],
                true
            )
        ) {
            return false;
        }

        return $default;
    }

    public static function environment(): string
    {
        return strtolower(
            self::string(
                'APP_ENV',
                'production'
            )
        );
    }

    public static function isLocal(): bool
    {
        return self::environment() ===
            'local';
    }

    public static function isProduction(): bool
    {
        return self::environment() ===
            'production';
    }

    public static function isDebug(): bool
    {
        return self::boolean(
            'APP_DEBUG',
            false
        );
    }
}