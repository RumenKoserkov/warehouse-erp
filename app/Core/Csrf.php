<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        Session::start();

        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            self::regenerate();

            $token = Session::get(self::SESSION_KEY);
        }

        if (!is_string($token)) {
            return '';
        }

        return $token;
    }

    public static function field(): string
    {
        $token = self::token();

        $escapedToken = htmlspecialchars(
            $token,
            ENT_QUOTES,
            'UTF-8'
        );

        return '<input type="hidden" name="_csrf_token" value="' .
            $escapedToken .
            '">';
    }

    public static function validate(?string $submittedToken): bool
    {
        Session::start();

        if ($submittedToken === null || $submittedToken === '') {
            return false;
        }

        $sessionToken = Session::get(self::SESSION_KEY);

        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }

    public static function regenerate(): void
    {
        Session::start();

        $token = bin2hex(random_bytes(32));

        Session::set(self::SESSION_KEY, $token);
    }
}