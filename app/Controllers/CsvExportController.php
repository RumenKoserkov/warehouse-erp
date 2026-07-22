<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\CsvResponse;
use App\Models\CsvExport;
use App\Models\ProfitReport;
use App\Services\AuthService;
use App\Services\CsvExportService;
use App\Services\MarginReportService;
use DateTimeImmutable;

class CsvExportController extends Controller
{
    private CsvExport $csvExportModel;

    private ProfitReport $profitReportModel;

    private CsvExportService $csvExportService;

    private MarginReportService $marginReportService;

    private AuthService $authService;

    public function __construct()
    {
        $this->csvExportModel =
            new CsvExport();

        $this->profitReportModel =
            new ProfitReport();

        $this->csvExportService =
            new CsvExportService();

        $this->marginReportService =
            new MarginReportService();

        $this->authService =
            new AuthService();
    }

    public function products(): void
    {
        $user = $this->currentUser();

        $records =
            $this->csvExportModel
            ->products(
                (int) $user['company_id'],
                [
                    'search' =>
                    $this->queryString(
                        'search'
                    ),

                    'category_id' =>
                    $this->queryId(
                        'category_id'
                    ),

                    'supplier_id' =>
                    $this->queryId(
                        'supplier_id'
                    ),

                    'unit' =>
                    $this->queryString(
                        'unit'
                    ),

                    'status' =>
                    $this->queryString(
                        'status'
                    ),

                    'min_price' =>
                    $this->queryNonNegativeNumber(
                        'min_price'
                    ),

                    'max_price' =>
                    $this->queryNonNegativeNumber(
                        'max_price'
                    ),
                ]
            );

        $dataset =
            $this->csvExportService
            ->products(
                $records,
                $this->canViewCosts(
                    $user
                )
            );

        $this->send(
            'products',
            $dataset
        );
    }

    public function stock(): void
    {
        $user = $this->currentUser();

        $records =
            $this->csvExportModel
            ->stock(
                (int) $user['company_id'],
                [
                    'search' =>
                    $this->queryString(
                        'search'
                    ),

                    'warehouse_id' =>
                    $this->queryId(
                        'warehouse_id'
                    ),

                    'category_id' =>
                    $this->queryId(
                        'category_id'
                    ),

                    'stock_status' =>
                    $this->queryString(
                        'stock_status'
                    ),
                ]
            );

        $dataset =
            $this->csvExportService
            ->stock(
                $records,
                $this->canViewCosts(
                    $user
                )
            );

        $this->send(
            'stock_levels',
            $dataset
        );
    }

    public function transactions(): void
    {
        $user = $this->currentUser();

        $records =
            $this->csvExportModel
            ->transactions(
                (int) $user['company_id'],
                [
                    'search' =>
                    $this->queryString(
                        'search'
                    ),

                    'product_id' =>
                    $this->queryId(
                        'product_id'
                    ),

                    'warehouse_id' =>
                    $this->queryId(
                        'warehouse_id'
                    ),

                    'type' =>
                    $this->queryString(
                        'type'
                    ),

                    'date_from' =>
                    $this->queryDate(
                        'date_from'
                    ),

                    'date_to' =>
                    $this->queryDate(
                        'date_to'
                    ),
                ]
            );

        $dataset =
            $this->csvExportService
            ->transactions(
                $records,
                $this->canViewCosts(
                    $user
                )
            );

        $this->send(
            'warehouse_transactions',
            $dataset
        );
    }

    public function sales(): void
    {
        $user = $this->currentUser();

        $records =
            $this->csvExportModel
            ->sales(
                (int) $user['company_id'],
                [
                    'search' =>
                    $this->queryString(
                        'search'
                    ),

                    'status' =>
                    $this->queryString(
                        'status'
                    ),

                    'warehouse_id' =>
                    $this->queryId(
                        'warehouse_id'
                    ),

                    'client_id' =>
                    $this->queryId(
                        'client_id'
                    ),

                    'date_from' =>
                    $this->queryDate(
                        'date_from'
                    ),

                    'date_to' =>
                    $this->queryDate(
                        'date_to'
                    ),
                ]
            );

        $dataset =
            $this->csvExportService
            ->sales(
                $records,
                $this->canViewCosts(
                    $user
                )
            );

        $this->send(
            'sales',
            $dataset
        );
    }

    public function purchases(): void
    {
        $user = $this->currentUser();

        $records =
            $this->csvExportModel
            ->purchases(
                (int) $user['company_id'],
                [
                    'search' =>
                    $this->queryString(
                        'search'
                    ),

                    'status' =>
                    $this->queryString(
                        'status'
                    ),

                    'warehouse_id' =>
                    $this->queryId(
                        'warehouse_id'
                    ),

                    'supplier_id' =>
                    $this->queryId(
                        'supplier_id'
                    ),

                    'date_from' =>
                    $this->queryDate(
                        'date_from'
                    ),

                    'date_to' =>
                    $this->queryDate(
                        'date_to'
                    ),
                ]
            );

        $dataset =
            $this->csvExportService
            ->purchases(
                $records
            );

        $this->send(
            'purchases',
            $dataset
        );
    }

    public function invoices(): void
    {
        $user = $this->currentUser();

        $dueFilter =
            $this->queryString(
                'due_filter'
            );

        if (
            !in_array(
                $dueFilter,
                [
                    'all',
                    'overdue',
                    'due_today',
                    'due_soon',
                    'unpaid',
                    'partially_paid',
                    'paid',
                    'no_due_date',
                ],
                true
            )
        ) {
            $dueFilter = 'all';
        }

        $records =
            $this->csvExportModel
            ->invoices(
                (int) $user['company_id'],
                [
                    'search' =>
                    $this->queryString(
                        'search'
                    ),

                    'due_filter' =>
                    $dueFilter,
                ]
            );

        $dataset =
            $this->csvExportService
            ->invoices(
                $records
            );

        $this->send(
            'invoices',
            $dataset
        );
    }

    public function profit(): void
    {
        $user = $this->currentUser();

        [$dateFrom, $dateTo] =
            $this->reportDateRange();

        $filters =
            $this->reportFilters();

        $events =
            $this->profitReportModel
            ->eventsByCompany(
                (int) $user['company_id'],
                $dateFrom,
                $dateTo,
                $filters
            );

        $dataset =
            $this->csvExportService
            ->profitEvents(
                $events
            );

        $this->send(
            'profit_report',
            $dataset
        );
    }

    public function margins(): void
    {
        $user = $this->currentUser();

        [$dateFrom, $dateTo] =
            $this->reportDateRange();

        $filters =
            $this->reportFilters();

        $grouping =
            $this->queryString(
                'grouping'
            );

        if (
            !in_array(
                $grouping,
                [
                    'daily',
                    'monthly',
                ],
                true
            )
        ) {
            $grouping = 'daily';
        }

        $minimumMargin =
            $this->queryPercentage(
                'minimum_margin',
                20.0
            );

        $events =
            $this->profitReportModel
            ->eventsByCompany(
                (int) $user['company_id'],
                $dateFrom,
                $dateTo,
                $filters
            );

        $report =
            $this->marginReportService
            ->build(
                $events,
                $grouping,
                $minimumMargin
            );

        $dataset =
            $this->csvExportService
            ->marginEvents(
                $report['events']
            );

        $this->send(
            'margin_report',
            $dataset
        );
    }

    private function reportFilters(): array
    {
        $costStatus =
            $this->queryString(
                'cost_status'
            );

        if (
            !in_array(
                $costStatus,
                [
                    'all',
                    'costed',
                    'uncosted',
                ],
                true
            )
        ) {
            $costStatus = 'all';
        }

        return [
            'search' =>
            $this->queryString(
                'search'
            ),

            'warehouse_id' =>
            $this->queryId(
                'warehouse_id'
            ),

            'client_id' =>
            $this->queryId(
                'client_id'
            ),

            'product_id' =>
            $this->queryId(
                'product_id'
            ),

            'category_id' =>
            $this->queryId(
                'category_id'
            ),

            'cost_status' =>
            $costStatus,
        ];
    }

    private function reportDateRange(): array
    {
        $dateFrom =
            $this->queryDate(
                'date_from'
            );

        $dateTo =
            $this->queryDate(
                'date_to'
            );

        if ($dateFrom === '') {
            $dateFrom =
                date('Y-m-01');
        }

        if ($dateTo === '') {
            $dateTo =
                date('Y-m-d');
        }

        if ($dateFrom > $dateTo) {
            return [
                date('Y-m-01'),
                date('Y-m-d'),
            ];
        }

        return [
            $dateFrom,
            $dateTo,
        ];
    }

    private function currentUser(): array
    {
        $user =
            $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');

            exit;
        }

        return $user;
    }

    private function canViewCosts(
        array $user
    ): bool {
        return in_array(
            (string) $user['role_slug'],
            [
                'administrator',
                'manager',
            ],
            true
        );
    }

    private function send(
        string $prefix,
        array $dataset
    ): never {
        CsvResponse::download(
            $prefix .
                '_' .
                date('Y-m-d_His') .
                '.csv',

            $dataset['headers'],
            $dataset['rows']
        );
    }

    private function queryNullableBoolean(
        string $field
    ): ?int {
        $value =
            $this->queryString(
                $field
            );

        if ($value === '1') {
            return 1;
        }

        if ($value === '0') {
            return 0;
        }

        return null;
    }

    private function queryPercentage(
        string $field,
        float $default
    ): float {
        $value =
            $this->queryString(
                $field
            );

        if ($value === '') {
            return $default;
        }

        $value = str_replace(
            ',',
            '.',
            $value
        );

        if (
            preg_match(
                '/^\d{1,3}(?:\.\d{1,2})?$/',
                $value
            ) !== 1
        ) {
            return $default;
        }

        $percentage = round(
            (float) $value,
            2
        );

        if (
            $percentage < 0 ||
            $percentage > 100
        ) {
            return $default;
        }

        return $percentage;
    }

    private function queryNonNegativeNumber(
        string $field
    ): ?float {
        $value =
            $this->queryString(
                $field
            );

        if ($value === '') {
            return null;
        }

        $value = str_replace(
            ',',
            '.',
            $value
        );

        if (
            !is_numeric($value) ||
            (float) $value < 0
        ) {
            return null;
        }

        return (float) $value;
    }

    private function queryDate(
        string $field
    ): string {
        $value =
            $this->queryString(
                $field
            );

        if ($value === '') {
            return '';
        }

        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        if (
            $date === false ||
            $date->format('Y-m-d') !==
            $value
        ) {
            return '';
        }

        return $value;
    }

    private function queryId(
        string $field
    ): int {
        $value = filter_var(
            $_GET[$field] ?? null,
            FILTER_VALIDATE_INT
        );

        return $value !== false &&
            $value > 0
            ? $value
            : 0;
    }

    private function queryString(
        string $field
    ): string {
        if (
            !isset($_GET[$field]) ||
            !is_scalar($_GET[$field])
        ) {
            return '';
        }

        return trim(
            (string) $_GET[$field]
        );
    }
}
