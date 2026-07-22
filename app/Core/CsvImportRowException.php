<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class CsvImportRowException extends RuntimeException
{
    public function __construct(
        private readonly ?string $columnName,
        string $message
    ) {
        parent::__construct($message);
    }

    public function columnName(): ?string
    {
        return $this->columnName;
    }
}