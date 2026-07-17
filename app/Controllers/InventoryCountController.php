<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\Warehouse;
use App\Services\AuthService;
use App\Services\InventoryCountService;

class InventoryCountController extends Controller
{
    private InventoryCount
        $inventoryCountModel;

    private InventoryCountItem
        $inventoryCountItemModel;

    private Warehouse $warehouseModel;

    private AuthService $authService;

    private InventoryCountService
        $inventoryCountService;

    public function __construct()
    {
        $this->inventoryCountModel =
            new InventoryCount();

        $this->inventoryCountItemModel =
            new InventoryCountItem();

        $this->warehouseModel =
            new Warehouse();

        $this->authService =
            new AuthService();

        $this->inventoryCountService =
            new InventoryCountService();
    }

    public function index(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $search =
            $this->queryString('search');

        $status =
            $this->queryString('status');

        $allowedStatuses = [
            '',
            'draft',
            'completed',
            'cancelled',
        ];

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            $status = '';
        }

        $warehouseId =
            $this->queryId(
                'warehouse_id'
            );

        $filters = [
            'search' => $search,
            'status' => $status,
            'warehouse_id' =>
                $warehouseId,
        ];

        $this->view(
            'inventory_counts/index',
            [
                'title' =>
                    'Inventory Counts',

                'inventoryCounts' =>
                    $this->inventoryCountModel
                        ->allByCompany(
                            $companyId,
                            $filters
                        ),

                'warehouses' =>
                    $this->warehouseModel
                        ->activeByCompany(
                            $companyId
                        ),

                'filters' => $filters,

                'canManage' =>
                    $this->canManage(
                        $currentUser
                    ),
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

        $this->view(
            'inventory_counts/create',
            [
                'title' =>
                    'New Inventory Count',

                'warehouses' =>
                    $this->warehouseModel
                        ->activeByCompany(
                            (int) $currentUser[
                                'company_id'
                            ]
                        ),

                'errors' => [],

                'old' => [
                    'warehouse_id' => '',
                    'count_date' =>
                        date('Y-m-d'),
                    'notes' => '',
                ],
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

        $warehouseId =
            $this->postId(
                'warehouse_id'
            );

        $countDate =
            $this->postString(
                'count_date'
            );

        $notes =
            $this->postString('notes');

        $result =
            $this->inventoryCountService
                ->createDraft(
                    (int) $currentUser[
                        'company_id'
                    ],

                    $warehouseId,

                    (int) $currentUser['id'],

                    $countDate,

                    $notes
                );

        if (!$result['success']) {
            $this->view(
                'inventory_counts/create',
                [
                    'title' =>
                        'New Inventory Count',

                    'warehouses' =>
                        $this->warehouseModel
                            ->activeByCompany(
                                (int) $currentUser[
                                    'company_id'
                                ]
                            ),

                    'errors' => [
                        (string) $result[
                            'error'
                        ],
                    ],

                    'old' => [
                        'warehouse_id' =>
                            (string) $warehouseId,

                        'count_date' =>
                            $countDate,

                        'notes' =>
                            $notes,
                    ],
                ]
            );

            return;
        }

        Flash::success(
            'Inventory count created successfully.'
        );

        $this->redirect(
            '/inventory-counts/show?id=' .
            (int) $result[
                'inventory_count_id'
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

        $inventoryCountId =
            $this->queryId('id');

        if ($inventoryCountId <= 0) {
            $this->abort(404);

            return;
        }

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $inventoryCount =
            $this->inventoryCountModel
                ->findByIdAndCompany(
                    $inventoryCountId,
                    $companyId
                );

        if ($inventoryCount === null) {
            $this->abort(404);

            return;
        }

        $this->view(
            'inventory_counts/show',
            [
                'title' =>
                    'Inventory Count ' .
                    (string) $inventoryCount[
                        'count_number'
                    ],

                'inventoryCount' =>
                    $inventoryCount,

                'items' =>
                    $this->inventoryCountItemModel
                        ->allByCount(
                            $inventoryCountId,
                            $companyId
                        ),

                'canManage' =>
                    $this->canManage(
                        $currentUser
                    ),

                'stockChanged' =>
                    $this->inventoryCountService
                        ->stockChangedSinceSnapshot(
                            $inventoryCount
                        ),
            ]
        );
    }

    public function save(): void
    {
        $this->saveOrComplete(false);
    }

    public function complete(): void
    {
        $this->saveOrComplete(true);
    }

    public function cancel(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $inventoryCountId =
            $this->postId(
                'inventory_count_id'
            );

        if ($inventoryCountId <= 0) {
            Flash::danger(
                'Invalid inventory count.'
            );

            $this->redirect(
                '/inventory-counts'
            );

            return;
        }

        $result =
            $this->inventoryCountService
                ->cancel(
                    $inventoryCountId,

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

            $this->redirect(
                '/inventory-counts/show?id=' .
                $inventoryCountId
            );

            return;
        }

        Flash::success(
            'Inventory count cancelled successfully.'
        );

        $this->redirect(
            '/inventory-counts/show?id=' .
            $inventoryCountId
        );
    }

    private function saveOrComplete(
        bool $complete
    ): void {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $inventoryCountId =
            $this->postId(
                'inventory_count_id'
            );

        if ($inventoryCountId <= 0) {
            Flash::danger(
                'Invalid inventory count.'
            );

            $this->redirect(
                '/inventory-counts'
            );

            return;
        }

        $quantities = [];

        if (
            isset(
                $_POST[
                    'counted_quantity'
                ]
            ) &&
            is_array(
                $_POST[
                    'counted_quantity'
                ]
            )
        ) {
            $quantities =
                $_POST[
                    'counted_quantity'
                ];
        }

        if ($complete) {
            $result =
                $this->inventoryCountService
                    ->complete(
                        $inventoryCountId,

                        (int) $currentUser[
                            'company_id'
                        ],

                        (int) $currentUser[
                            'id'
                        ],

                        $quantities
                    );
        } else {
            $result =
                $this->inventoryCountService
                    ->saveCounts(
                        $inventoryCountId,

                        (int) $currentUser[
                            'company_id'
                        ],

                        (int) $currentUser[
                            'id'
                        ],

                        $quantities
                    );
        }

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );

            $this->redirect(
                '/inventory-counts/show?id=' .
                $inventoryCountId
            );

            return;
        }

        if ($complete) {
            Flash::success(
                'Inventory count completed. Adjusted products: ' .
                (int) $result[
                    'difference_item_count'
                ] .
                '.'
            );
        } else {
            Flash::success(
                'Counted quantities saved.'
            );
        }

        $this->redirect(
            '/inventory-counts/show?id=' .
            $inventoryCountId
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
}