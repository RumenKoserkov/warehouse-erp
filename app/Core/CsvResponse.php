<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeInterface;
use RuntimeException;
use Stringable;
use Throwable;

final class CsvResponse
{
    public static function download(
        string $filename,
        array $headers,
        iterable $rows,
        string $delimiter = ';'
    ): never {
        if (strlen($delimiter) !== 1) {
            throw new RuntimeException(
                'CSV delimiter must contain exactly one character.'
            );
        }

        if (headers_sent($file, $line)) {
            throw new RuntimeException(
                'CSV headers were already sent in ' .
                $file .
                ' on line ' .
                $line .
                '.'
            );
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = self::sanitizeFilename(
            $filename
        );

        header(
            'Content-Type: text/csv; charset=UTF-8'
        );

        header(
            'Content-Disposition: attachment; filename="' .
            $filename .
            '"'
        );

        header(
            'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
        );

        header('Pragma: no-cache');

        header(
            'Expires: Thu, 01 Jan 1970 00:00:00 GMT'
        );

        header(
            'X-Content-Type-Options: nosniff'
        );

        $output = fopen(
            'php://output',
            'wb'
        );

        if ($output === false) {
            throw new RuntimeException(
                'CSV output stream could not be opened.'
            );
        }

        fwrite(
            $output,
            "\xEF\xBB\xBF"
        );

        self::writeRow(
            $output,
            $headers,
            $delimiter
        );

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            self::writeRow(
                $output,
                array_values($row),
                $delimiter
            );
        }

        fclose($output);

        exit;
    }

    private static function writeRow(
        mixed $output,
        array $row,
        string $delimiter
    ): void {
        $safeRow = [];

        foreach ($row as $value) {
            $safeRow[] = self::sanitizeCell(
                $value
            );
        }

        $written = fputcsv(
            $output,
            $safeRow,
            $delimiter,
            '"',
            ''
        );

        if ($written === false) {
            throw new RuntimeException(
                'CSV row could not be written.'
            );
        }
    }

    private static function sanitizeCell(
        mixed $value
    ): string|int|float {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (
            is_int($value) ||
            is_float($value)
        ) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(
                'Y-m-d H:i:s'
            );
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (
            is_array($value) ||
            is_object($value)
        ) {
            try {
                $encoded = json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_THROW_ON_ERROR
                );

                $value =
                    $encoded !== false
                        ? $encoded
                        : '';
            } catch (Throwable) {
                $value = '';
            }
        }

        $value = str_replace(
            "\0",
            '',
            (string) $value
        );

        if (
            $value !== '' &&
            is_numeric($value)
        ) {
            return $value;
        }

        $trimmedLeft = ltrim(
            $value,
            " \t"
        );

        if (
            $trimmedLeft !== '' &&
            preg_match(
                '/^[=\-+@\t\r]/u',
                $trimmedLeft
            ) === 1
        ) {
            return "'" . $value;
        }

        return $value;
    }

    private static function sanitizeFilename(
        string $filename
    ): string {
        $filename = trim($filename);

        $filename = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '_',
            $filename
        ) ?? '';

        $filename = trim(
            $filename,
            '._-'
        );

        if ($filename === '') {
            $filename = 'export.csv';
        }

        if (
            !str_ends_with(
                strtolower($filename),
                '.csv'
            )
        ) {
            $filename .= '.csv';
        }

        return $filename;
    }
}