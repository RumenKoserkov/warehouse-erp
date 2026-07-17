<?php

declare(strict_types=1);

use App\Core\Environment;
use App\Core\ErrorHandler;
use Dotenv\Dotenv;

$basePath = dirname(__DIR__);

require_once $basePath .
    '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(
    $basePath
);

$dotenv->safeLoad();

error_reporting(E_ALL);

$debug = Environment::isDebug();

ini_set(
    'display_errors',
    $debug ? '1' : '0'
);

ini_set(
    'display_startup_errors',
    $debug ? '1' : '0'
);

ini_set(
    'log_errors',
    '1'
);

ini_set(
    'html_errors',
    '0'
);

ini_set(
    'expose_php',
    '0'
);

$logDirectory =
    $basePath .
    '/storage/logs';

if (!is_dir($logDirectory)) {
    $directoryCreated = mkdir(
        $logDirectory,
        0775,
        true
    );

    if (!$directoryCreated) {
        throw new RuntimeException(
            'The application log directory could not be created.'
        );
    }
}

if (!is_writable($logDirectory)) {
    throw new RuntimeException(
        'The application log directory is not writable.'
    );
}

ini_set(
    'error_log',
    $logDirectory .
        '/php-error.log'
);

ErrorHandler::register();

$dotenv->required([
    'APP_NAME',
    'APP_ENV',
    'APP_DEBUG',
    'APP_URL',
    'APP_TIMEZONE',

    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_CHARSET',
]);

$dotenv->required([
    'APP_NAME',
    'APP_ENV',
    'APP_DEBUG',
    'APP_URL',
    'APP_TIMEZONE',

    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_CHARSET',
])->notEmpty();

$allowedEnvironments = [
    'local',
    'testing',
    'staging',
    'production',
];

if (
    !in_array(
        Environment::environment(),
        $allowedEnvironments,
        true
    )
) {
    throw new RuntimeException(
        'APP_ENV must be local, testing, staging or production.'
    );
}

$debugValue = strtolower(
    Environment::string(
        'APP_DEBUG'
    )
);

$allowedBooleanValues = [
    '1',
    '0',
    'true',
    'false',
    'yes',
    'no',
    'on',
    'off',
];

if (
    !in_array(
        $debugValue,
        $allowedBooleanValues,
        true
    )
) {
    throw new RuntimeException(
        'APP_DEBUG must contain a boolean value.'
    );
}

$timezone =
    Environment::string(
        'APP_TIMEZONE',
        'Europe/Sofia'
    );

if (
    !in_array(
        $timezone,
        DateTimeZone::listIdentifiers(),
        true
    )
) {
    throw new RuntimeException(
        'APP_TIMEZONE contains an invalid timezone.'
    );
}

date_default_timezone_set(
    $timezone
);

$dbPort = Environment::integer(
    'DB_PORT',
    0
);

if (
    $dbPort <= 0 ||
    $dbPort > 65535
) {
    throw new RuntimeException(
        'DB_PORT must be between 1 and 65535.'
    );
}

$positiveSecuritySettings = [
    'LOGIN_MAX_ATTEMPTS',
    'LOGIN_LOCK_MINUTES',

    'SESSION_IDLE_TIMEOUT',
    'SESSION_ABSOLUTE_TIMEOUT',
    'SESSION_RENEWAL_INTERVAL',
    'SESSION_USER_RECHECK_INTERVAL',
];

foreach (
    $positiveSecuritySettings as $setting
) {
    $value = Environment::integer(
        $setting,
        0
    );

    if ($value <= 0) {
        throw new RuntimeException(
            $setting .
            ' must be a positive integer.'
        );
    }
}

if (
    Environment::integer(
        'SESSION_ABSOLUTE_TIMEOUT',
        43200
    ) <
    Environment::integer(
        'SESSION_IDLE_TIMEOUT',
        1800
    )
) {
    throw new RuntimeException(
        'SESSION_ABSOLUTE_TIMEOUT cannot be shorter than SESSION_IDLE_TIMEOUT.'
    );
}

$appConfig = require
    $basePath .
    '/config/app.php';

$sameSite = ucfirst(
    strtolower(
        (string) $appConfig['session']['same_site']
    )
);

if (
    !in_array(
        $sameSite,
        [
            'Lax',
            'Strict',
            'None',
        ],
        true
    )
) {
    throw new RuntimeException(
        'SESSION_SAME_SITE must be Lax, Strict or None.'
    );
}

$secureCookie =
    (bool) $appConfig['session']['secure_cookie'];

if (
    $sameSite === 'None' &&
    !$secureCookie
) {
    throw new RuntimeException(
        'SESSION_SECURE_COOKIE must be true when SESSION_SAME_SITE is None.'
    );
}

ini_set(
    'session.name',
    (string) $appConfig['session']['name']
);

ini_set(
    'session.use_strict_mode',
    '1'
);

ini_set(
    'session.use_only_cookies',
    '1'
);

ini_set(
    'session.cookie_httponly',
    '1'
);

ini_set(
    'session.cookie_secure',
    $secureCookie ? '1' : '0'
);

ini_set(
    'session.cookie_samesite',
    $sameSite
);

ini_set(
    'session.cookie_lifetime',
    '0'
);

ini_set(
    'session.cache_limiter',
    'nocache'
);

$sessionGarbageLifetime = max(
    Environment::integer(
        'SESSION_IDLE_TIMEOUT',
        1800
    ),
    Environment::integer(
        'SESSION_ABSOLUTE_TIMEOUT',
        43200
    )
);

ini_set(
    'session.gc_maxlifetime',
    (string) $sessionGarbageLifetime
);

return [
    'base_path' => $basePath,

    'app' => $appConfig,

    'database' => require
        $basePath .
        '/config/database.php',
];