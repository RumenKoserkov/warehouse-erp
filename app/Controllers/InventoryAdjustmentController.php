<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentItem;
use App\Models\Warehouse;
use App\Services\AuthService;
use App\Services\InventoryAdjustmentService;

class InventoryAdjustmentController extends Controller
{
    private InventoryAdjustment $adjustmentModel;

    private InventoryAdjustmentItem $itemModel;

    private Warehouse $warehouseModel;

    private AuthService $authService;

    private InventoryAdjustmentService $adjustmentService;

    public function __construct()
    {
        $this->adjustmentModel =
            new InventoryAdjustment();

        $this->itemModel =
            new InventoryAdjustmentItem();

        $this->warehouseModel =
            new Warehouse();

        $this->authService =
            new AuthService();

        $this->adjustmentService =
            new InventoryAdjustmentService();
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
            (int) $currentUser['company_id'];

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

        $reasonType =
            $this->queryString(
                'reason_type'
            );

        if (
            $reasonType !== '' &&
            !array_key_exists(
                $reasonType,
                $this->adjustmentService
                    ->reasonTypes()
            )
        ) {
            $reasonType = '';
        }

        $filters = [
            'search' =>
            $this->queryString(
                'search'
            ),

            'status' =>
            $status,

            'reason_type' =>
            $reasonType,

            'warehouse_id' =>
            $this->queryId(
                'warehouse_id'
            ),
        ];

        $this->view(
            'inventory_adjustments/index',
            [
                'title' =>
                'Inventory Adjustments',

                'adjustments' =>
                $this->adjustmentModel
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
                $this->adjustmentService
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

        $this->view(
            'inventory_adjustments/create',
            [
                'title' =>
                'New Inventory Adjustment',

                'warehouses' =>
                $this->warehouseModel
                    ->activeByCompany(
                        (int) $currentUser['company_id']
                    ),

                'reasonTypes' =>
                $this->adjustmentService
                    ->reasonTypes(),

                'errors' => [],

                'old' => [
                    'warehouse_id' => '',

                    'adjustment_date' =>
                    date('Y-m-d'),

                    'reason_type' => '',

                    'reason_description' => '',

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

        $adjustmentDate =
            $this->postString(
                'adjustment_date'
            );

        $reasonType =
            $this->postString(
                'reason_type'
            );

        $reasonDescription =
            $this->postString(
                'reason_description'
            );

        $notes =
            $this->postString('notes');

        $result =
            $this->adjustmentService
            ->createDraft(
                (int) $currentUser['company_id'],

                $warehouseId,

                (int) $currentUser['id'],

                $adjustmentDate,

                $reasonType,

                $reasonDescription,

                $notes
            );

        if (!$result['success']) {
            $this->view(
                'inventory_adjustments/create',
                [
                    'title' =>
                    'New Inventory Adjustment',

                    'warehouses' =>
                    $this->warehouseModel
                        ->activeByCompany(
                            (int) $currentUser['company_id']
                        ),

                    'reasonTypes' =>
                    $this->adjustmentService
                        ->reasonTypes(),

                    'errors' => [
                        (string) $result['error'],
                    ],

                    'old' => [
                        'warehouse_id' =>
                        (string) $warehouseId,

                        'adjustment_date' =>
                        $adjustmentDate,

                        'reason_type' =>
                        $reasonType,

                        'reason_description' =>
                        $reasonDescription,

                        'notes' =>
                        $notes,
                    ],
                ]
            );

            return;
        }

        Flash::success(
            'Inventory adjustment draft created successfully.'
        );

        $this->redirect(
            '/inventory-adjustments/show?id=' .
                (int) $result['adjustment_id']
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

        $adjustmentId =
            $this->queryId('id');

        if ($adjustmentId <= 0) {
            $this->abort(404);

            return;
        }

        $companyId =
            (int) $currentUser['company_id'];

        $adjustment =
            $this->adjustmentModel
            ->findByIdAndCompany(
                $adjustmentId,
                $companyId
            );

        if ($adjustment === null) {
            $this->abort(404);

            return;
        }

        $productSearch =
            $this->queryString(
                'product_search'
            );

        $products = [];

        if (
            (string) $adjustment['status'] === 'draft'
        ) {
            $products =
                $this->itemModel
                ->productsForAdjustment(
                    $companyId,

                    (int) $adjustment['warehouse_id'],

                    $productSearch
                );
        }

        $this->view(
            'inventory_adjustments/show',
            [
                'title' =>
                'Inventory Adjustment ' .
                    (string) $adjustment['adjustment_number'],

                'adjustment' =>
                $adjustment,

                'items' =>
                $this->itemModel
                    ->allByAdjustment(
                        $adjustmentId,
                        $companyId
                    ),

                'products' =>
                $products,

                'productSearch' =>
                $productSearch,

                'reasonTypes' =>
                $this->adjustmentService
                    ->reasonTypes(),

                'directions' =>
                $this->adjustmentService
                    ->directions(),
            ]
        );
    }

    public function addItem(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $adjustmentId =
            $this->postId(
                'inventory_adjustment_id'
            );

        $result =
            $this->adjustmentService
            ->addItem(
                $adjustmentId,

                (int) $currentUser['company_id'],

                (int) $currentUser['id'],

                $this->postId(
                    'product_id'
                ),

                $this->postString(
                    'direction'
                ),

                $this->postString(
                    'quantity'
                ),

                $this->postString(
                    'unit_cost'
                ),

                $this->postString(
                    'item_note'
                )
            );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );
        } else {
            Flash::success(
                'Product added to the adjustment.'
            );
        }

        $this->redirect(
            '/inventory-adjustments/show?id=' .
                $adjustmentId
        );
    }

    public function deleteItem(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $adjustmentId =
            $this->postId(
                'inventory_adjustment_id'
            );

        $result =
            $this->adjustmentService
            ->deleteItem(
                $adjustmentId,

                $this->postId(
                    'item_id'
                ),

                (int) $currentUser['company_id'],

                (int) $currentUser['id']
            );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );
        } else {
            Flash::success(
                'Adjustment item removed.'
            );
        }

        $this->redirect(
            '/inventory-adjustments/show?id=' .
                $adjustmentId
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

        $adjustmentId =
            $this->postId(
                'inventory_adjustment_id'
            );

        $result =
            $this->adjustmentService
            ->complete(
                $adjustmentId,

                (int) $currentUser['company_id'],

                (int) $currentUser['id']
            );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );
        } else {
            Flash::success(
                'Inventory adjustment completed. Products adjusted: ' .
                    (int) $result['item_count'] .
                    '.'
            );
        }

        $this->redirect(
            '/inventory-adjustments/show?id=' .
                $adjustmentId
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

        $adjustmentId =
            $this->postId(
                'inventory_adjustment_id'
            );

        $result =
            $this->adjustmentService
            ->cancel(
                $adjustmentId,

                (int) $currentUser['company_id'],

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
                'Inventory adjustment cancelled successfully.'
            );
        }

        $this->redirect(
            '/inventory-adjustments/show?id=' .
                $adjustmentId
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
