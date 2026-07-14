<?php

declare(strict_types=1);

namespace App\Core;

final class Sorter
{
    private array $allowedColumns;

    private string $sortKey;

    private string $direction;

    private string $basePath;

    private array $queryParameters;

    public function __construct(
        array $allowedColumns,
        string $requestedSort,
        string $requestedDirection,
        string $defaultSort,
        string $defaultDirection,
        string $basePath,
        array $queryParameters = []
    ) {
        $this->allowedColumns = $allowedColumns;
        $this->basePath = $basePath;
        $this->queryParameters = $queryParameters;

        if (isset($allowedColumns[$requestedSort])) {
            $this->sortKey = $requestedSort;
        } else {
            $this->sortKey = $defaultSort;
        }

        $requestedDirection = strtolower($requestedDirection);
        $defaultDirection = strtolower($defaultDirection);

        if (
            $defaultDirection !== 'asc' &&
            $defaultDirection !== 'desc'
        ) {
            $defaultDirection = 'asc';
        }

        if (
            $requestedDirection === 'asc' ||
            $requestedDirection === 'desc'
        ) {
            $this->direction = $requestedDirection;
        } else {
            $this->direction = $defaultDirection;
        }
    }

    public function key(): string
    {
        return $this->sortKey;
    }

    public function column(): string
    {
        return $this->allowedColumns[$this->sortKey];
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function sqlDirection(): string
    {
        return strtoupper($this->direction);
    }

    public function isActive(string $sortKey): bool
    {
        return $this->sortKey === $sortKey;
    }

    public function nextDirection(string $sortKey): string
    {
        if (!$this->isActive($sortKey)) {
            return 'asc';
        }

        if ($this->direction === 'asc') {
            return 'desc';
        }

        return 'asc';
    }

    public function url(string $sortKey): string
    {
        if (!isset($this->allowedColumns[$sortKey])) {
            $sortKey = $this->sortKey;
        }

        $parameters = $this->queryParameters;

        foreach ($parameters as $key => $value) {
            if ($value === null || $value === '') {
                unset($parameters[$key]);
            }
        }

        $parameters['sort'] = $sortKey;
        $parameters['direction'] = $this->nextDirection($sortKey);

        // При ново сортиране започваме от първата страница.
        $parameters['page'] = 1;

        $queryString = http_build_query($parameters);

        if ($queryString === '') {
            return $this->basePath;
        }

        return $this->basePath . '?' . $queryString;
    }
}
