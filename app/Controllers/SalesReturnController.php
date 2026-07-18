<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Warehouse;
use App\Services\AuthService;
use App\Services\SalesReturnService;

class SalesReturnController extends Controller
{
    private Sale $saleModel;

    private SalesReturn $salesReturnModel;

    private SalesReturnItem $salesReturnItemModel;

    private Warehouse $warehouseModel;

    private AuthService $authService;

    private SalesReturnService $salesReturnService;

    public function __construct()
    {
        $this->saleModel =
            new Sale();

        $this->salesReturnModel =
            new SalesReturn();

        $this->salesReturnItemModel =
            new SalesReturnItem();

        $this->warehouseModel =
            new Warehouse();

        $this->authService =
            new AuthService();

        $this->salesReturnService =
            new SalesReturnService();
    }

    public function index(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $status =
            $this->queryString('status');

        if (
            !in_array(
                $status,
                [
                    '',
                    'draft',
                    'completed',
                    'cancelled',
                ],
                true
            )
        ) {
            $status = '';
        }

        $filters = [
            'search' =>
                $this->queryString(
                    'search'
                ),

            'status' =>
                $status,

            'warehouse_id' =>
                $this->queryId(
                    'warehouse_id'
                ),
        ];

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $this->view(
            'sales_returns/index',
            [
                'title' =>
                    'Sales Returns',

                'salesReturns' =>
                    $this->salesReturnModel
                        ->allByCompany(
                            $companyId,
                            $filters
                        ),

                'warehouses' =>
                    $this->warehouseModel
                        ->activeByCompany(
                            $companyId
                        ),

                'reasonTypes' =>
                    $this->salesReturnService
                        ->reasonTypes(),

                'filters' =>
                    $filters,
            ]
        );
    }

    public function create(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $saleId =
            $this->queryId('sale_id');

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $sale =
            $this->saleModel
                ->findByIdAndCompany(
                    $saleId,
                    $companyId
                );

        if (
            $sale === null ||
            (string) $sale['status'] !==
                'completed'
        ) {
            $this->abort(404);

            return;
        }

        $summary =
            $this->salesReturnService
                ->summaryForSale(
                    $saleId,
                    $companyId
                );

        if (
            !$summary[
                'has_returnable_items'
            ]
        ) {
            Flash::danger(
                'This sale does not have any remaining quantities to return.'
            );

            $this->redirect(
                '/sales/show?id=' .
                $saleId
            );

            return;
        }

        if ($summary['has_draft']) {
            Flash::danger(
                'This sale already has an open sales return draft.'
            );

            $this->redirect(
                '/sales/show?id=' .
                $saleId
            );

            return;
        }

        $this->renderCreate(
            $sale,
            $companyId,
            [],
            [
                'return_date' =>
                    date('Y-m-d'),

                'reason_type' =>
                    'customer_return',

                'reason_description' =>
                    '',

                'notes' =>
                    '',

                'return_quantity' =>
                    [],

                'restock_quantity' =>
                    [],

                'item_note' =>
                    [],
            ]
        );
    }

    public function store(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $saleId =
            $this->postId('sale_id');

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $result =
            $this->salesReturnService
                ->createDraft(
                    $saleId,
                    $companyId,
                    (int) $currentUser['id'],

                    $this->postString(
                        'return_date'
                    ),

                    $this->postString(
                        'reason_type'
                    ),

                    $this->postString(
                        'reason_description'
                    ),

                    $this->postString(
                        'notes'
                    ),

                    $this->postArray(
                        'return_quantity'
                    ),

                    $this->postArray(
                        'restock_quantity'
                    ),

                    $this->postArray(
                        'item_note'
                    )
                );

        if (!$result['success']) {
            $sale =
                $this->saleModel
                    ->findByIdAndCompany(
                        $saleId,
                        $companyId
                    );

            if ($sale === null) {
                $this->abort(404);

                return;
            }

            $this->renderCreate(
                $sale,
                $companyId,
                [
                    (string) $result[
                        'error'
                    ],
                ],
                [
                    'return_date' =>
                        $this->postString(
                            'return_date'
                        ),

                    'reason_type' =>
                        $this->postString(
                            'reason_type'
                        ),

                    'reason_description' =>
                        $this->postString(
                            'reason_description'
                        ),

                    'notes' =>
                        $this->postString(
                            'notes'
                        ),

                    'return_quantity' =>
                        $this->postArray(
                            'return_quantity'
                        ),

                    'restock_quantity' =>
                        $this->postArray(
                            'restock_quantity'
                        ),

                    'item_note' =>
                        $this->postArray(
                            'item_note'
                        ),
                ]
            );

            return;
        }

        Flash::success(
            'Sales return draft created successfully.'
        );

        $this->redirect(
            '/sales-returns/show?id=' .
            (int) $result[
                'sales_return_id'
            ]
        );
    }

    public function show(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $salesReturnId =
            $this->queryId('id');

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $salesReturn =
            $this->salesReturnModel
                ->findByIdAndCompany(
                    $salesReturnId,
                    $companyId
                );

        if ($salesReturn === null) {
            $this->abort(404);

            return;
        }

        $returnableRows =
            $this->salesReturnItemModel
                ->returnableBySale(
                    (int) $salesReturn[
                        'sale_id'
                    ],
                    $companyId
                );

        $returnableMap = [];

        foreach (
            $returnableRows as $row
        ) {
            $returnableMap[
                (int) $row['id']
            ] = $row;
        }

        $this->view(
            'sales_returns/show',
            [
                'title' =>
                    'Sales Return ' .
                    (string) $salesReturn[
                        'return_number'
                    ],

                'salesReturn' =>
                    $salesReturn,

                'items' =>
                    $this->salesReturnItemModel
                        ->allByReturn(
                            $salesReturnId,
                            $companyId
                        ),

                'returnableMap' =>
                    $returnableMap,

                'reasonTypes' =>
                    $this->salesReturnService
                        ->reasonTypes(),

                'canManage' =>
                    $this->canManage(
                        $currentUser
                    ),
            ]
        );
    }

    public function update(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $salesReturnId =
            $this->postId(
                'sales_return_id'
            );

        $result =
            $this->salesReturnService
                ->updateDraftItems(
                    $salesReturnId,

                    (int) $currentUser[
                        'company_id'
                    ],

                    (int) $currentUser['id'],

                    $this->postArray(
                        'return_quantity'
                    ),

                    $this->postArray(
                        'restock_quantity'
                    ),

                    $this->postArray(
                        'item_note'
                    )
                );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );
        } else {
            Flash::success(
                'Sales return quantities updated.'
            );
        }

        $this->redirect(
            '/sales-returns/show?id=' .
            $salesReturnId
        );
    }

    public function complete(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $salesReturnId =
            $this->postId(
                'sales_return_id'
            );

        $result =
            $this->salesReturnService
                ->complete(
                    $salesReturnId,

                    (int) $currentUser[
                        'company_id'
                    ],

                    (int) $currentUser['id']
                );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );
        } else {
            Flash::success(
                'Sales return completed successfully. Returned items: ' .
                (int) $result[
                    'item_count'
                ] .
                '.'
            );
        }

        $this->redirect(
            '/sales-returns/show?id=' .
            $salesReturnId
        );
    }

    public function cancel(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $salesReturnId =
            $this->postId(
                'sales_return_id'
            );

        $result =
            $this->salesReturnService
                ->cancel(
                    $salesReturnId,

                    (int) $currentUser[
                        'company_id'
                    ],

                    (int) $currentUser['id'],

                    $this->postString(
                        'cancellation_reason'
                    )
                );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );
        } else {
            Flash::success(
                'Sales return cancelled successfully.'
            );
        }

        $this->redirect(
            '/sales-returns/show?id=' .
            $salesReturnId
        );
    }

    private function renderCreate(
        array $sale,
        int $companyId,
        array $errors,
        array $old
    ): void {
        $this->view(
            'sales_returns/create',
            [
                'title' =>
                    'Create Sales Return',

                'sale' =>
                    $sale,

                'returnableItems' =>
                    $this->salesReturnItemModel
                        ->returnableBySale(
                            (int) $sale['id'],
                            $companyId
                        ),

                'reasonTypes' =>
                    $this->salesReturnService
                        ->reasonTypes(),

                'errors' =>
                    $errors,

                'old' =>
                    $old,
            ]
        );
    }

    private function canManage(
        array $currentUser
    ): bool {
        return in_array(
            (string) $currentUser[
                'role_slug'
            ],
            [
                'administrator',
                'manager',
            ],
            true
        );
    }

    private function queryId(
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

    private function postId(
        string $field
    ): int {
        if (!isset($_POST[$field])) {
            return 0;
        }

        $value = filter_var(
            $_POST[$field],
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

    private function postString(
        string $field
    ): string {
        if (
            !isset($_POST[$field]) ||
            !is_scalar($_POST[$field])
        ) {
            return '';
        }

        return trim(
            (string) $_POST[$field]
        );
    }

    private function postArray(
        string $field
    ): array {
        if (
            !isset($_POST[$field]) ||
            !is_array($_POST[$field])
        ) {
            return [];
        }

        return $_POST[$field];
    }
}