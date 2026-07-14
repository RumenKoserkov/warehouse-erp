<?php

declare(strict_types=1);

namespace App\Core;

use DateTime;

class Validator
{
    private array $data;

    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(
        string $field,
        ?string $message = null
    ): self {
        $value = $this->getValue($field);

        if ($this->isEmpty($value)) {
            if ($message === null) {
                $message = $this->fieldLabel($field) . ' is required.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function email(
        string $field,
        ?string $message = null
    ): self {
        $value = $this->getStringValue($field);

        if ($value === '') {
            return $this;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must be a valid email address.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function min(
        string $field,
        int $minimumLength,
        ?string $message = null
    ): self {
        $value = $this->getStringValue($field);

        if ($value === '') {
            return $this;
        }

        if (mb_strlen($value) < $minimumLength) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must be at least ' .
                    $minimumLength .
                    ' characters.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function max(
        string $field,
        int $maximumLength,
        ?string $message = null
    ): self {
        $value = $this->getStringValue($field);

        if ($value === '') {
            return $this;
        }

        if (mb_strlen($value) > $maximumLength) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must be maximum ' .
                    $maximumLength .
                    ' characters.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function numeric(
        string $field,
        ?string $message = null
    ): self {
        $value = $this->getValue($field);

        if ($this->isEmpty($value)) {
            return $this;
        }

        if (!is_numeric($value)) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must be numeric.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function integer(
        string $field,
        ?string $message = null
    ): self {
        $value = $this->getValue($field);

        if ($this->isEmpty($value)) {
            return $this;
        }

        $validatedValue = filter_var(
            $value,
            FILTER_VALIDATE_INT
        );

        if ($validatedValue === false) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must be an integer.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function positive(
        string $field,
        ?string $message = null
    ): self {
        $value = $this->getValue($field);

        if ($this->isEmpty($value)) {
            return $this;
        }

        if (!is_numeric($value)) {
            return $this;
        }

        if ((float) $value <= 0) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must be greater than zero.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function nonNegative(
        string $field,
        ?string $message = null
    ): self {
        $value = $this->getValue($field);

        if ($this->isEmpty($value)) {
            return $this;
        }

        if (!is_numeric($value)) {
            return $this;
        }

        if ((float) $value < 0) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' cannot be negative.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function minValue(
        string $field,
        float $minimumValue,
        ?string $message = null
    ): self {
        $value = $this->getValue($field);

        if ($this->isEmpty($value)) {
            return $this;
        }

        if (!is_numeric($value)) {
            return $this;
        }

        if ((float) $value < $minimumValue) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must be at least ' .
                    $minimumValue .
                    '.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function maxValue(
        string $field,
        float $maximumValue,
        ?string $message = null
    ): self {
        $value = $this->getValue($field);

        if ($this->isEmpty($value)) {
            return $this;
        }

        if (!is_numeric($value)) {
            return $this;
        }

        if ((float) $value > $maximumValue) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must not be greater than ' .
                    $maximumValue .
                    '.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function decimal(
        string $field,
        int $decimalPlaces = 2,
        ?string $message = null
    ): self {
        $value = $this->getStringValue($field);

        if ($value === '') {
            return $this;
        }

        if ($decimalPlaces < 0) {
            $decimalPlaces = 0;
        }

        $pattern = '/^-?\d+$/';

        if ($decimalPlaces > 0) {
            $pattern = '/^-?\d+(?:\.\d{1,' .
                $decimalPlaces .
                '})?$/';
        }

        if (preg_match($pattern, $value) !== 1) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must contain maximum ' .
                    $decimalPlaces .
                    ' decimal places.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function date(
        string $field,
        string $format = 'Y-m-d',
        ?string $message = null
    ): self {
        $value = $this->getStringValue($field);

        if ($value === '') {
            return $this;
        }

        $date = DateTime::createFromFormat($format, $value);

        $isValid = false;

        if ($date !== false) {
            $isValid = $date->format($format) === $value;
        }

        if (!$isValid) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must be a valid date.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function in(
        string $field,
        array $allowedValues,
        ?string $message = null
    ): self {
        $value = $this->getStringValue($field);

        if ($value === '') {
            return $this;
        }

        $allowedStrings = [];

        foreach ($allowedValues as $allowedValue) {
            $allowedStrings[] = (string) $allowedValue;
        }

        if (!in_array($value, $allowedStrings, true)) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' contains an invalid value.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function same(
        string $field,
        string $otherField,
        ?string $message = null
    ): self {
        $value = $this->getStringValue($field);
        $otherValue = $this->getStringValue($otherField);

        if ($value === '') {
            return $this;
        }

        if ($value !== $otherValue) {
            if ($message === null) {
                $message = $this->fieldLabel($field) .
                    ' must match ' .
                    $this->fieldLabel($otherField) .
                    '.';
            }

            $this->addError($field, $message);
        }

        return $this;
    }

    public function add(
        string $field,
        string $message
    ): self {
        $this->addError($field, $message);

        return $this;
    }

    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function first(string $field): ?string
    {
        if (!isset($this->errors[$field][0])) {
            return null;
        }

        return $this->errors[$field][0];
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

    private function getValue(string $field): mixed
    {
        if (!array_key_exists($field, $this->data)) {
            return null;
        }

        return $this->data[$field];
    }

    private function getStringValue(string $field): string
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return '';
        }

        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_array($value)) {
            return empty($value);
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return false;
    }

    private function fieldLabel(string $field): string
    {
        $field = str_replace('_', ' ', $field);

        return ucwords($field);
    }

    private function addError(
        string $field,
        string $message
    ): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }
}