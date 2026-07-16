<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Receivable;
use App\Services\AuthService;
use App\Services\ReceivablesReportService;
use DateTimeImmutable;

class ReceivableController extends Controller
{
    private Receivable $receivableModel;

    private AuthService $authService;

    private ReceivablesReportService
        $reportService;

    public function __construct()
    {
        $this->receivableModel =
            new Receivable();

        $this->authService =
            new AuthService();

        $this->reportService =
            new ReceivablesReportService();
    }

    public function index(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $companyId = (int) $currentUser[
            'company_id'
        ];

        $errors = [];

        $asOfDate = date('Y-m-d');

        if (
            isset($_GET['as_of_date']) &&
            is_scalar($_GET['as_of_date'])
        ) {
            $requestedDate = trim(
                (string) $_GET[
                    'as_of_date'
                ]
            );

            if (
                $this->validDate(
                    $requestedDate
                )
            ) {
                if (
                    $requestedDate <=
                    date('Y-m-d')
                ) {
                    $asOfDate =
                        $requestedDate;
                } else {
                    $errors[] =
                        'The report date cannot be in the future.';
                }
            } elseif ($requestedDate !== '') {
                $errors[] =
                    'The report date is invalid.';
            }
        }

        $search = '';

        if (
            isset($_GET['search']) &&
            is_scalar($_GET['search'])
        ) {
            $search = trim(
                (string) $_GET['search']
            );
        }

        if (mb_strlen($search) > 255) {
            $search = mb_substr(
                $search,
                0,
                255
            );
        }

        $clientId = 0;

        if (isset($_GET['client_id'])) {
            $validatedClientId =
                filter_var(
                    $_GET['client_id'],
                    FILTER_VALIDATE_INT
                );

            if (
                $validatedClientId !==
                    false &&
                $validatedClientId > 0
            ) {
                $clientId =
                    $validatedClientId;
            }
        }

        $agingFilters =
            $this->reportService
                ->agingFilters();

        $agingFilter = 'all';

        if (
            isset($_GET['aging_filter']) &&
            is_scalar(
                $_GET['aging_filter']
            )
        ) {
            $requestedAgingFilter =
                trim(
                    (string) $_GET[
                        'aging_filter'
                    ]
                );

            if (
                array_key_exists(
                    $requestedAgingFilter,
                    $agingFilters
                )
            ) {
                $agingFilter =
                    $requestedAgingFilter;
            }
        }

        $receivables =
            $this->receivableModel
                ->reportByCompany(
                    $companyId,
                    $asOfDate,
                    $search,
                    $clientId,
                    $agingFilter
                );

        $summary =
            $this->reportService
                ->summarize(
                    $receivables
                );

        $this->view(
            'receivables/index',
            [
                'title' =>
                    'Receivables Report',

                'receivables' =>
                    $receivables,

                'summary' =>
                    $summary,

                'clients' =>
                    $this->receivableModel
                        ->clientsByCompany(
                            $companyId
                        ),

                'agingFilters' =>
                    $agingFilters,

                'agingBucketLabels' =>
                    $this->reportService
                        ->agingBucketLabels(),

                'asOfDate' =>
                    $asOfDate,

                'search' =>
                    $search,

                'clientId' =>
                    $clientId,

                'agingFilter' =>
                    $agingFilter,

                'errors' =>
                    $errors,
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

        if ($date === false) {
            return false;
        }

        return $date->format('Y-m-d') ===
            $value;
    }
}