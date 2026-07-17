<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class Session
{
    public static function start(): void
    {
        if (
            session_status() ===
            PHP_SESSION_NONE
        ) {
            $started = session_start();

            if (!$started) {
                throw new RuntimeException(
                    'The session could not be started.'
                );
            }
        }
    }

    public static function regenerate(): void
    {
        self::start();

        $regenerated =
            session_regenerate_id(true);

        if (!$regenerated) {
            throw new RuntimeException(
                'The session ID could not be regenerated.'
            );
        }
    }

    public static function set(
        string $key,
        mixed $value
    ): void {
        self::start();

        $_SESSION[$key] = $value;
    }

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        self::start();

        if (
            !array_key_exists(
                $key,
                $_SESSION
            )
        ) {
            return $default;
        }

        return $_SESSION[$key];
    }

    public static function has(
        string $key
    ): bool {
        self::start();

        return array_key_exists(
            $key,
            $_SESSION
        );
    }

    public static function remove(
        string $key
    ): void {
        self::start();

        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parameters =
                session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' =>
                    time() - 42000,

                    'path' =>
                    $parameters['path'],

                    'domain' =>
                    $parameters['domain'],

                    'secure' =>
                    $parameters['secure'],

                    'httponly' =>
                    $parameters['httponly'],

                    'samesite' =>
                    $parameters['samesite'] ??
                        'Lax',
                ]
            );
        }

        session_destroy();
    }
}
