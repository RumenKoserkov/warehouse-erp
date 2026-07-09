<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    private AuthService $authService;
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->dashboardService = new DashboardService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        $companyId = (int)$currentUser['company_id'];

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $this->dashboardService->getStats($companyId),
            'lowStockProducts' => $this->dashboardService->getLowStockProducts($companyId, 5),
            'recentTransactions' => $this->dashboardService->getRecentTransactions($companyId, 10),
        ]);
    }
}