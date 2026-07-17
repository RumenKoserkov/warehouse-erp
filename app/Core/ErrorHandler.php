<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    private static bool $handling = false;

    public static function register(): void
    {
        set_exception_handler(
            [
                self::class,
                'handleException',
            ]
        );

        register_shutdown_function(
            [
                self::class,
                'handleShutdown',
            ]
        );
    }

    public static function handleException(
        Throwable $exception
    ): never {
        if (self::$handling) {
            self::renderEmergencyMessage();

            exit(1);
        }

        self::$handling = true;

        $reference =
            self::generateReference();

        self::writeLog(
            $exception,
            $reference
        );

        if (PHP_SAPI === 'cli') {
            self::renderCli(
                $exception,
                $reference
            );

            exit(1);
        }

        self::renderWeb(
            $exception,
            $reference
        );

        exit(1);
    }

    public static function handleShutdown(): void
    {
        if (self::$handling) {
            return;
        }

        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
        ];

        if (
            !in_array(
                $error['type'],
                $fatalTypes,
                true
            )
        ) {
            return;
        }

        $exception = new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );

        self::handleException(
            $exception
        );
    }

    private static function writeLog(
        Throwable $exception,
        string $reference
    ): void {
        $method = 'CLI';
        $uri = '';

        if (
            isset(
                $_SERVER[
                    'REQUEST_METHOD'
                ]
            )
        ) {
            $method = (string) $_SERVER[
                'REQUEST_METHOD'
            ];
        }

        if (
            isset(
                $_SERVER[
                    'REQUEST_URI'
                ]
            )
        ) {
            $uri = (string) $_SERVER[
                'REQUEST_URI'
            ];
        }

        $message =
            '[' .
            $reference .
            ']' .
            PHP_EOL .
            'Environment: ' .
            Environment::environment() .
            PHP_EOL .
            'Request: ' .
            $method .
            ' ' .
            $uri .
            PHP_EOL .
            (string) $exception;

        error_log($message);
    }

    private static function renderCli(
        Throwable $exception,
        string $reference
    ): void {
        if (Environment::isDebug()) {
            fwrite(
                STDERR,
                (string) $exception .
                PHP_EOL
            );

            return;
        }

        fwrite(
            STDERR,
            'Application error. Reference: ' .
            $reference .
            PHP_EOL
        );
    }

    private static function renderWeb(
        Throwable $exception,
        string $reference
    ): void {
        if (!headers_sent()) {
            http_response_code(500);

            header(
                'Content-Type: text/html; charset=UTF-8'
            );

            header(
                'Cache-Control: no-store, no-cache, must-revalidate'
            );
        }

        $applicationName =
            htmlspecialchars(
                Environment::string(
                    'APP_NAME',
                    'Warehouse ERP'
                ),
                ENT_QUOTES,
                'UTF-8'
            );

        $safeReference =
            htmlspecialchars(
                $reference,
                ENT_QUOTES,
                'UTF-8'
            );

        $debugContent = '';

        if (Environment::isDebug()) {
            $exceptionText =
                htmlspecialchars(
                    (string) $exception,
                    ENT_QUOTES,
                    'UTF-8'
                );

            $debugContent = '
                <section class="debug">
                    <h2>Debug information</h2>

                    <pre>' .
                        $exceptionText .
                    '</pre>
                </section>
            ';
        }

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>500 - Server Error</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            color: #212529;
            background: #f4f6f8;
            font-family: Arial, sans-serif;
        }

        .error-card {
            width: min(900px, 100%);
            padding: 40px;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            background: #ffffff;
            box-shadow:
                0 8px 30px
                rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin: 0 0 12px;
            font-size: 64px;
        }

        h2 {
            margin-top: 0;
        }

        .reference {
            margin-top: 24px;
            padding: 12px;
            border-radius: 6px;
            background: #f1f3f5;
            font-family: monospace;
        }

        .debug {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #dee2e6;
        }

        pre {
            max-height: 500px;
            overflow: auto;
            padding: 16px;
            border-radius: 6px;
            color: #f8f9fa;
            background: #212529;
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>

<body>
    <main class="error-card">
        <h1>500</h1>

        <h2>Server error</h2>

        <p>
            ' .
            $applicationName .
            ' could not complete the request.
            Please try again later.
        </p>

        <div class="reference">
            Error reference:
            <strong>' .
                $safeReference .
            '</strong>
        </div>

        ' .
        $debugContent .
        '
    </main>
</body>
</html>';
    }

    private static function renderEmergencyMessage(): void
    {
        if (PHP_SAPI === 'cli') {
            fwrite(
                STDERR,
                'A fatal application error occurred.' .
                PHP_EOL
            );

            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        echo 'A fatal application error occurred.';
    }

    private static function generateReference(): string
    {
        try {
            return strtoupper(
                bin2hex(
                    random_bytes(6)
                )
            );
        } catch (Throwable) {
            return strtoupper(
                substr(
                    md5(
                        uniqid(
                            '',
                            true
                        )
                    ),
                    0,
                    12
                )
            );
        }
    }
}