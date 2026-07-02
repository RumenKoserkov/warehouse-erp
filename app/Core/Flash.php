<?php

declare(strict_types=1);

namespace App\Core;

class Flash
{
    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function danger(string $message): void
    {
        self::add('danger', $message);
    }

    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    public static function add(string $type, string $message): void
    {
        Session::start();

        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }

        if (!isset($_SESSION['flash'][$type])) {
            $_SESSION['flash'][$type] = [];
        }

        $_SESSION['flash'][$type][] = $message;
    }

    public static function all(): array
    {
        Session::start();

        $messages = $_SESSION['flash'] ?? [];

        unset($_SESSION['flash']);

        return $messages;
    }
}