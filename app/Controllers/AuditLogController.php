<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\AuditLogService;

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

        $filters = [
            'action' => '',
            'entity_type' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
        ];

        if (isset($_GET['action'])) {
            $filters['action'] = trim((string)$_GET['action']);
        }

        if (isset($_GET['entity_type'])) {
            $filters['entity_type'] = trim((string)$_GET['entity_type']);
        }

        if (isset($_GET['date_from'])) {
            $filters['date_from'] = trim((string)$_GET['date_from']);
        }

        if (isset($_GET['date_to'])) {
            $filters['date_to'] = trim((string)$_GET['date_to']);
        }

        if (isset($_GET['search'])) {
            $filters['search'] = trim((string)$_GET['search']);
        }

        $logs = $this->auditLogService->allByCompany(
            (int)$currentUser['company_id'],
            $filters
        );

        $this->view('audit_logs/index', [
            'title' => 'Audit Logs',
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $this->auditLogService->actions(),
            'entityTypes' => $this->auditLogService->entityTypes(),
        ]);
    }
}