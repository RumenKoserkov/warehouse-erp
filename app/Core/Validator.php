<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;

    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, ?string $message = null): self
    {
        $value = trim((string)($this->data[$field] ?? ''));

        if ($value === '') {
            $this->addError(
                $field,
                $message ?? ucfirst($field) . ' is required.'
            );
        }

        return $this;
    }

    public function email(string $field, ?string $message = null): self
    {
        $value = trim((string)($this->data[$field] ?? ''));

        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError(
                $field,
                $message ?? ucfirst($field) . ' must be a valid email address.'
            );
        }

        return $this;
    }

    public function min(string $field, int $min, ?string $message = null): self
    {
        $value = trim((string)($this->data[$field] ?? ''));

        if ($value !== '' && mb_strlen($value) < $min) {
            $this->addError(
                $field,
                $message ?? ucfirst($field) . " must be at least {$min} characters."
            );
        }

        return $this;
    }

    public function max(string $field, int $max, ?string $message = null): self
    {
        $value = trim((string)($this->data[$field] ?? ''));

        if ($value !== '' && mb_strlen($value) > $max) {
            $this->addError(
                $field,
                $message ?? ucfirst($field) . " must be maximum {$max} characters."
            );
        }

        return $this;
    }

    public function numeric(string $field, ?string $message = null): self
    {
        $value = trim((string)($this->data[$field] ?? ''));

        if ($value !== '' && !is_numeric($value)) {
            $this->addError(
                $field,
                $message ?? ucfirst($field) . ' must be numeric.'
            );
        }

        return $this;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function all(): array
    {
        $allErrors = [];

        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $allErrors[] = $error;
            }
        }

        return $allErrors;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }
}