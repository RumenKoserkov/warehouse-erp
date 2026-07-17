<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $connection =
        null;

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = require
            __DIR__ .
            '/../../config/database.php';

        if (
            trim(
                (string) $config['database']
            ) === '' ||
            trim(
                (string) $config['username']
            ) === ''
        ) {
            throw new RuntimeException(
                'Database configuration is incomplete.'
            );
        }

        $dsn =
            'mysql:host=' .
            $config['host'] .

            ';port=' .
            $config['port'] .

            ';dbname=' .
            $config['database'] .

            ';charset=' .
            $config['charset'];

        try {
            self::$connection = new PDO(
                $dsn,
                (string) $config['username'],
                (string) $config['password'],
                [
                    PDO::ATTR_ERRMODE =>
                        PDO::ERRMODE_EXCEPTION,

                    PDO::ATTR_DEFAULT_FETCH_MODE =>
                        PDO::FETCH_ASSOC,

                    PDO::ATTR_EMULATE_PREPARES =>
                        false,

                    PDO::ATTR_STRINGIFY_FETCHES =>
                        false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Database connection could not be established.',
                0,
                $exception
            );
        }

        return self::$connection;
    }
}