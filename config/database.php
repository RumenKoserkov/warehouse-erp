<?php

declare(strict_types=1);

use App\Core\Environment;

return [
    'host' => Environment::string(
        'DB_HOST',
        '127.0.0.1'
    ),

    'port' => Environment::integer(
        'DB_PORT',
        3306
    ),

    'database' => Environment::string(
        'DB_DATABASE'
    ),

    'username' => Environment::string(
        'DB_USERNAME'
    ),

    'password' => Environment::string(
        'DB_PASSWORD'
    ),

    'charset' => Environment::string(
        'DB_CHARSET',
        'utf8mb4'
    ),
];