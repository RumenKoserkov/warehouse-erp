<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Paginator;
use App\Services\AuditLogService;
use App\Services\AuthService;
use DateTimeImmutable;

class AuditLogController extends Controller
{
    private AuthService $authService;

    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->auditLogService = new AuditLogService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $companyId = (int) $currentUser['company_id'];

        $filters = $this->readFilters();

        $page = 1;

        if (isset($_GET['page'])) {
            $requestedPage = filter_var(
                $_GET['page'],
                FILTER_VALIDATE_INT
            );

            if (
                $requestedPage !== false &&
                $requestedPage > 0
            ) {
                $page = $requestedPage;
            }
        }

        $perPage = 25;

        $totalLogs =
            $this->auditLogService->countByCompany(
                $companyId,
                $filters
            );

        $paginator = new Paginator(
            $totalLogs,
            $page,
            $perPage,
            '/audit-logs',
            $filters
        );

        $logs =
            $this->auditLogService->paginateByCompany(
                $companyId,
                $filters,
                $paginator->perPage(),
                $paginator->offset()
            );

        $this->view(
            'audit_logs/index',
            [
                'title' => 'Activity Log',
                'logs' => $logs,
                'filters' => $filters,

                'actions' =>
                    $this->auditLogService->actions(),

                'entityTypes' =>
                    $this->auditLogService->entityTypes(),

                'severities' =>
                    $this->auditLogService->severities(),

                'users' =>
                    $this->auditLogService->usersByCompany(
                        $companyId
                    ),

                'summary' =>
                    $this->auditLogService->summaryByCompany(
                        $companyId
                    ),

                'paginator' => $paginator,
            ]
        );
    }

    public function show(): void
    {
        $currentUser = $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $id = 0;

        if (isset($_GET['id'])) {
            $validatedId = filter_var(
                $_GET['id'],
                FILTER_VALIDATE_INT
            );

            if (
                $validatedId !== false &&
                $validatedId > 0
            ) {
                $id = $validatedId;
            }
        }

        if ($id <= 0) {
            $this->abort(404);

            return;
        }

        $log =
            $this->auditLogService->findByIdAndCompany(
                $id,
                (int) $currentUser['company_id']
            );

        if ($log === null) {
            $this->abort(404);

            return;
        }

        $this->view(
            'audit_logs/show',
            [
                'title' => 'Activity Log Details',
                'log' => $log,
            ]
        );
    }

    private function readFilters(): array
    {
        $filters = [
            'action' => '',
            'severity' => '',
            'entity_type' => '',
            'entity_id' => '',
            'user_id' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
        ];

        $requestedAction =
            $this->queryString('action');

        if (
            in_array(
                $requestedAction,
                $this->auditLogService->actions(),
                true
            )
        ) {
            $filters['action'] = $requestedAction;
        }

        $requestedSeverity =
            $this->queryString('severity');

        if (
            array_key_exists(
                $requestedSeverity,
                $this->auditLogService->severities()
            )
        ) {
            $filters['severity'] =
                $requestedSeverity;
        }

        $requestedEntityType =
            $this->queryString('entity_type');

        if (
            in_array(
                $requestedEntityType,
                $this->auditLogService->entityTypes(),
                true
            )
        ) {
            $filters['entity_type'] =
                $requestedEntityType;
        }

        $entityId =
            $this->positiveQueryInteger(
                'entity_id'
            );

        if ($entityId > 0) {
            $filters['entity_id'] = $entityId;
        }

        $userId =
            $this->positiveQueryInteger(
                'user_id'
            );

        if ($userId > 0) {
            $filters['user_id'] = $userId;
        }

        $dateFrom =
            $this->queryString('date_from');

        if ($this->validDate($dateFrom)) {
            $filters['date_from'] = $dateFrom;
        }

        $dateTo =
            $this->queryString('date_to');

        if ($this->validDate($dateTo)) {
            $filters['date_to'] = $dateTo;
        }

        if (
            $filters['date_from'] !== '' &&
            $filters['date_to'] !== '' &&
            $filters['date_from'] >
                $filters['date_to']
        ) {
            $temporaryDate =
                $filters['date_from'];

            $filters['date_from'] =
                $filters['date_to'];

            $filters['date_to'] =
                $temporaryDate;
        }

        $search =
            $this->queryString('search');

        if (mb_strlen($search) > 255) {
            $search = mb_substr(
                $search,
                0,
                255
            );
        }

        $filters['search'] = $search;

        return $filters;
    }

    private function queryString(
        string $field
    ): string {
        if (!isset($_GET[$field])) {
            return '';
        }

        if (!is_scalar($_GET[$field])) {
            return '';
        }

        return trim(
            (string) $_GET[$field]
        );
    }

    private function positiveQueryInteger(
        string $field
    ): int {
        if (!isset($_GET[$field])) {
            return 0;
        }

        $value = filter_var(
            $_GET[$field],
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

    private function validDate(
        string $value
    ): bool {
        if ($value === '') {
            return false;
        }

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