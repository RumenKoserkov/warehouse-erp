<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityHeaders
{
    public static function enforceTrustedHost(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $hostHeader = '';

        if (
            isset($_SERVER['HTTP_HOST']) &&
            is_string($_SERVER['HTTP_HOST'])
        ) {
            $hostHeader = trim(
                $_SERVER['HTTP_HOST']
            );
        }

        if ($hostHeader === '') {
            self::badRequest();

            return;
        }

        $requestedHost = parse_url(
            'http://' . $hostHeader,
            PHP_URL_HOST
        );

        if (
            !is_string($requestedHost) ||
            $requestedHost === ''
        ) {
            self::badRequest();

            return;
        }

        $requestedHost = strtolower(
            $requestedHost
        );

        $trustedHosts = [];

        $configuredHosts = explode(
            ',',
            Environment::string(
                'TRUSTED_HOSTS'
            )
        );

        foreach (
            $configuredHosts as $configuredHost
        ) {
            $configuredHost = strtolower(
                trim($configuredHost)
            );

            if ($configuredHost !== '') {
                $trustedHosts[] =
                    $configuredHost;
            }
        }

        $appUrl = Environment::string(
            'APP_URL'
        );

        if ($appUrl !== '') {
            $appHost = parse_url(
                $appUrl,
                PHP_URL_HOST
            );

            if (
                is_string($appHost) &&
                $appHost !== ''
            ) {
                $trustedHosts[] = strtolower(
                    $appHost
                );
            }
        }

        $trustedHosts = array_values(
            array_unique($trustedHosts)
        );

        if (
            !in_array(
                $requestedHost,
                $trustedHosts,
                true
            )
        ) {
            self::badRequest();
        }
    }

    public static function send(): void
    {
        if (
            PHP_SAPI === 'cli' ||
            headers_sent()
        ) {
            return;
        }

        header(
            'X-Content-Type-Options: nosniff'
        );

        header(
            'X-Frame-Options: DENY'
        );

        /*
         * Старият browser XSS filter може сам
         * да създава проблеми. Използваме CSP.
         */
        header(
            'X-XSS-Protection: 0'
        );

        header(
            'Referrer-Policy: strict-origin-when-cross-origin'
        );

        header(
            'Permissions-Policy: ' .
            'camera=(), ' .
            'microphone=(), ' .
            'geolocation=(), ' .
            'payment=(), ' .
            'usb=()'
        );

        header(
            'Cross-Origin-Opener-Policy: same-origin'
        );

        header(
            'Cross-Origin-Resource-Policy: same-origin'
        );

        header(
            'X-Permitted-Cross-Domain-Policies: none'
        );

        header(
            'Cache-Control: no-store, no-cache, must-revalidate, private'
        );

        header(
            'Pragma: no-cache'
        );

        $contentSecurityPolicy = implode(
            '; ',
            [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "img-src 'self' data: blob:",
                "connect-src 'self'",
                "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
                "font-src 'self' data: https://cdn.jsdelivr.net",
            ]
        );

        if (
            Environment::isProduction() &&
            self::isHttps()
        ) {
            $contentSecurityPolicy .=
                '; upgrade-insecure-requests';
        }

        header(
            'Content-Security-Policy: ' .
            $contentSecurityPolicy
        );

        if (
            Environment::isProduction() &&
            self::isHttps()
        ) {
            header(
                'Strict-Transport-Security: ' .
                'max-age=31536000; ' .
                'includeSubDomains'
            );
        }
    }

    private static function isHttps(): bool
    {
        if (
            isset($_SERVER['HTTPS']) &&
            is_string($_SERVER['HTTPS']) &&
            strtolower($_SERVER['HTTPS']) !==
                'off' &&
            $_SERVER['HTTPS'] !== ''
        ) {
            return true;
        }

        return isset(
            $_SERVER['SERVER_PORT']
        ) &&
            (int) $_SERVER[
                'SERVER_PORT'
            ] === 443;
    }

    private static function badRequest(): never
    {
        if (!headers_sent()) {
            http_response_code(400);

            header(
                'Content-Type: text/plain; charset=UTF-8'
            );
        }

        echo '400 Bad Request';

        exit;
    }
}