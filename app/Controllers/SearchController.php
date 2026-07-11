<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\SearchService;

class SearchController extends Controller
{
    private AuthService $authService;
    private SearchService $searchService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->searchService = new SearchService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        $companyId = (int)$currentUser['company_id'];

        $query = '';

        if (isset($_GET['q'])) {
            $query = trim((string)$_GET['q']);
        }

        $results = [
            'products' => [],
            'clients' => [],
            'suppliers' => [],
            'sales' => [],
            'purchases' => [],
        ];

        $totalResults = 0;

        if ($query !== '' && strlen($query) >= 2) {
            $results = $this->searchService->search($companyId, $query);
            $totalResults = $this->searchService->countResults($results);
        }

        $this->view('search/index', [
            'title' => 'Global Search',
            'query' => $query,
            'results' => $results,
            'totalResults' => $totalResults,
        ]);
    }
}