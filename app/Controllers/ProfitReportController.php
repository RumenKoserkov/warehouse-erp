<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfitReport;
use App\Services\AuthService;
use App\Services\ProfitReportService;
use DateTimeImmutable;

class ProfitReportController extends Controller
{
    private ProfitReport $profitReportModel;

    private ProfitReportService $profitReportService;

    private AuthService $authService;

    public function __construct()
    {
        $this->profitReportModel =
            new ProfitReport();

        $this->profitReportService =
            new ProfitReportService();

        $this->authService =
            new AuthService();
    }

    public function index(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $today = date('Y-m-d');

        $defaultDateFrom =
            date('Y-m-01');

        $dateFrom =
            $this->queryString(
                'date_from'
            );

        $dateTo =
            $this->queryString(
                'date_to'
            );

        if ($dateFrom === '') {
            $dateFrom =
                $defaultDateFrom;
        }

        if ($dateTo === '') {
            $dateTo = $today;
        }

        $errors = [];

        if (!$this->validDate($dateFrom)) {
            $errors[] =
                'Start date is invalid.';

            $dateFrom =
                $defaultDateFrom;
        }

        if (!$this->validDate($dateTo)) {
            $errors[] =
                'End date is invalid.';

            $dateTo = $today;
        }

        if ($dateFrom > $dateTo) {
            $errors[] =
                'Start date cannot be after end date.';

            $dateFrom =
                $defaultDateFrom;

            $dateTo = $today;
        }

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

        $search =
            $this->queryString(
                'search'
            );

        if (mb_strlen($search) > 255) {
            $search = mb_substr(
                $search,
                0,
                255
            );
        }

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,

            'grouping' => $grouping,

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

            'search' =>
                $search,
        ];

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $events =
            $this->profitReportModel
                ->eventsByCompany(
                    $companyId,
                    $dateFrom,
                    $dateTo,
                    $filters
                );

        $report =
            $this->profitReportService
                ->build(
                    $events,
                    $grouping
                );

        $this->view(
            'reports/profit',
            [
                'title' =>
                    'Profit Reports',

                'report' =>
                    $report,

                'filters' =>
                    $filters,

                'errors' =>
                    $errors,

                'warehouses' =>
                    $this->profitReportModel
                        ->warehousesByCompany(
                            $companyId
                        ),

                'clients' =>
                    $this->profitReportModel
                        ->clientsByCompany(
                            $companyId
                        ),

                'products' =>
                    $this->profitReportModel
                        ->productsByCompany(
                            $companyId
                        ),

                'categories' =>
                    $this->profitReportModel
                        ->categoriesByCompany(
                            $companyId
                        ),
            ]
        );
    }

    private function validDate(
        string $value
    ): bool {
        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        return $date !== false &&
            $date->format('Y-m-d') ===
                $value;
    }

    private function queryId(
        string $field
    ): int {
        $value = filter_var(
            $_GET[$field] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $value === false ||
            $value <= 0
        ) {
            return 0;
        }

        return $value;
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