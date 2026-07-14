<?php

declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    private int $total;
    private int $perPage;
    private int $currentPage;
    private int $lastPage;
    private string $basePath;
    private array $queryParameters;

    public function __construct(
        int $total,
        int $currentPage,
        int $perPage,
        string $basePath,
        array $queryParameters = []
    ) {
        if ($total < 0) {
            $total = 0;
        }

        if ($perPage < 1) {
            $perPage = 10;
        }

        $this->total = $total;
        $this->perPage = $perPage;
        $this->basePath = $basePath;
        $this->queryParameters = $queryParameters;

        $lastPage = (int) ceil($total / $perPage);

        if ($lastPage < 1) {
            $lastPage = 1;
        }

        $this->lastPage = $lastPage;

        if ($currentPage < 1) {
            $currentPage = 1;
        }

        if ($currentPage > $lastPage) {
            $currentPage = $lastPage;
        }

        $this->currentPage = $currentPage;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function from(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return $this->offset() + 1;
    }

    public function to(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        $to = $this->offset() + $this->perPage;

        if ($to > $this->total) {
            $to = $this->total;
        }

        return $to;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function previousPage(): int
    {
        $page = $this->currentPage - 1;

        if ($page < 1) {
            return 1;
        }

        return $page;
    }

    public function nextPage(): int
    {
        $page = $this->currentPage + 1;

        if ($page > $this->lastPage) {
            return $this->lastPage;
        }

        return $page;
    }

    public function pages(): array
    {
        $start = $this->currentPage - 2;
        $end = $this->currentPage + 2;

        if ($start < 1) {
            $start = 1;
        }

        if ($end > $this->lastPage) {
            $end = $this->lastPage;
        }

        return range($start, $end);
    }

    public function url(int $page): string
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($page > $this->lastPage) {
            $page = $this->lastPage;
        }

        $parameters = $this->queryParameters;

        foreach ($parameters as $key => $value) {
            if ($value === null || $value === '') {
                unset($parameters[$key]);
            }
        }

        $parameters['page'] = $page;

        $queryString = http_build_query($parameters);

        if ($queryString === '') {
            return $this->basePath;
        }

        return $this->basePath . '?' . $queryString;
    }
}