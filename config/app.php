<?php

declare(strict_types=1);

use App\Core\Environment;

return [
    'name' => Environment::string(
        'APP_NAME',
        'Warehouse ERP'
    ),

    'environment' =>
        Environment::environment(),

    'debug' =>
        Environment::isDebug(),

    'url' => rtrim(
        Environment::string(
            'APP_URL',
            'http://127.0.0.1:8000'
        ),
        '/'
    ),

    'timezone' =>
        Environment::string(
            'APP_TIMEZONE',
            'Europe/Sofia'
        ),

    'session' => [
        'name' =>
            Environment::string(
                'SESSION_NAME',
                'warehouse_erp_session'
            ),

        'secure_cookie' =>
            Environment::boolean(
                'SESSION_SECURE_COOKIE',
                Environment::isProduction()
            ),

        'same_site' =>
            Environment::string(
                'SESSION_SAME_SITE',
                'Lax'
            ),
    ],
];