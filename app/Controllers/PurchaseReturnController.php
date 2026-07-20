<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Warehouse;
use App\Services\AuthService;
use App\Services\PurchaseReturnService;

class PurchaseReturnController extends Controller
{
    private Purchase $purchaseModel;

    private PurchaseReturn $purchaseReturnModel;

    private PurchaseReturnItem $itemModel;

    private Warehouse $warehouseModel;

    private AuthService $authService;

    private PurchaseReturnService $returnService;

    public function __construct()
    {
        $this->purchaseModel =
            new Purchase();

        $this->purchaseReturnModel =
            new PurchaseReturn();

        $this->itemModel =
            new PurchaseReturnItem();

        $this->warehouseModel =
            new Warehouse();

        $this->authService =
            new AuthService();

        $this->returnService =
            new PurchaseReturnService();
    }

    public function index(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
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

        $companyId =
            (int) $user['company_id'];

        $filters = [
            'search' =>
                $this->queryString('search'),

            'status' => $status,

            'warehouse_id' =>
                $this->queryId(
                    'warehouse_id'
                ),
        ];

        $this->view(
            'purchase_returns/index',
            [
                'title' =>
                    'Purchase Returns',

                'purchaseReturns' =>
                    $this->purchaseReturnModel
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
            ]
        );
    }

    public function create(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $purchaseId =
            $this->queryId('purchase_id');

        $companyId =
            (int) $user['company_id'];

        $purchase =
            $this->purchaseModel
                ->findByIdAndCompany(
                    $purchaseId,
                    $companyId
                );

        if (
            $purchase === null ||
            (string) $purchase['status'] !==
                'completed'
        ) {
            $this->abort(404);
            return;
        }

        $summary =
            $this->returnService
                ->summaryForPurchase(
                    $purchaseId,
                    $companyId
                );

        if (
            !$summary[
                'has_returnable_items'
            ]
        ) {
            Flash::danger(
                'This purchase has no remaining quantities to return.'
            );

            $this->redirect(
                '/purchases/show?id=' .
                $purchaseId
            );

            return;
        }

        if ($summary['has_draft']) {
            Flash::danger(
                'This purchase already has an open return draft.'
            );

            $this->redirect(
                '/purchases/show?id=' .
                $purchaseId
            );

            return;
        }

        $this->renderCreate(
            $purchase,
            $companyId,
            [],
            [
                'return_date' =>
                    date('Y-m-d'),

                'reason_type' =>
                    'damaged_goods',

                'reason_description' => '',
                'notes' => '',
                'return_quantity' => [],
                'item_note' => [],
            ]
        );
    }

    public function store(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $purchaseId =
            $this->postId('purchase_id');

        $companyId =
            (int) $user['company_id'];

        $result =
            $this->returnService
                ->createDraft(
                    $purchaseId,
                    $companyId,
                    (int) $user['id'],

                    $this->postString(
                        'return_date'
                    ),

                    $this->postString(
                        'reason_type'
                    ),

                    $this->postString(
                        'reason_description'
                    ),

                    $this->postString('notes'),

                    $this->postArray(
                        'return_quantity'
                    ),

                    $this->postArray(
                        'item_note'
                    )
                );

        if (!$result['success']) {
            $purchase =
                $this->purchaseModel
                    ->findByIdAndCompany(
                        $purchaseId,
                        $companyId
                    );

            if ($purchase === null) {
                $this->abort(404);
                return;
            }

            $this->renderCreate(
                $purchase,
                $companyId,
                [(string) $result['error']],
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

                    'item_note' =>
                        $this->postArray(
                            'item_note'
                        ),
                ]
            );

            return;
        }

        Flash::success(
            'Purchase return draft created successfully.'
        );

        $this->redirect(
            '/purchase-returns/show?id=' .
            (int) $result[
                'purchase_return_id'
            ]
        );
    }

    public function show(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $id = $this->queryId('id');

        $companyId =
            (int) $user['company_id'];

        $purchaseReturn =
            $this->purchaseReturnModel
                ->findByIdAndCompany(
                    $id,
                    $companyId
                );

        if ($purchaseReturn === null) {
            $this->abort(404);
            return;
        }

        $returnableItems =
            $this->itemModel
                ->returnableByPurchase(
                    (int) $purchaseReturn[
                        'purchase_id'
                    ],
                    $companyId
                );

        $storedItems =
            $this->itemModel
                ->allByReturn(
                    $id,
                    $companyId
                );

        $storedItemMap = [];

        foreach ($storedItems as $item) {
            $storedItemMap[
                (int) $item[
                    'purchase_item_id'
                ]
            ] = $item;
        }

        $this->view(
            'purchase_returns/show',
            [
                'title' =>
                    'Purchase Return ' .
                    (string) $purchaseReturn[
                        'return_number'
                    ],

                'purchaseReturn' =>
                    $purchaseReturn,

                'items' =>
                    $storedItems,

                'returnableItems' =>
                    $returnableItems,

                'storedItemMap' =>
                    $storedItemMap,

                'reasonTypes' =>
                    $this->returnService
                        ->reasonTypes(),

                'canManage' =>
                    $this->canManage($user),
            ]
        );
    }

    public function update(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $id = $this->postId(
            'purchase_return_id'
        );

        $result =
            $this->returnService
                ->updateDraft(
                    $id,

                    (int) $user[
                        'company_id'
                    ],

                    (int) $user['id'],

                    $this->postArray(
                        'return_quantity'
                    ),

                    $this->postArray(
                        'item_note'
                    )
                );

        if ($result['success']) {
            Flash::success(
                'Purchase return quantities updated.'
            );
        } else {
            Flash::danger(
                (string) $result['error']
            );
        }

        $this->redirect(
            '/purchase-returns/show?id=' .
            $id
        );
    }

    public function complete(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $id = $this->postId(
            'purchase_return_id'
        );

        $result =
            $this->returnService
                ->complete(
                    $id,

                    (int) $user[
                        'company_id'
                    ],

                    (int) $user['id']
                );

        if ($result['success']) {
            Flash::success(
                'Purchase return completed. Returned items: ' .
                (int) $result['item_count'] .
                '.'
            );
        } else {
            Flash::danger(
                (string) $result['error']
            );
        }

        $this->redirect(
            '/purchase-returns/show?id=' .
            $id
        );
    }

    public function cancel(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $id = $this->postId(
            'purchase_return_id'
        );

        $result =
            $this->returnService
                ->cancel(
                    $id,

                    (int) $user[
                        'company_id'
                    ],

                    (int) $user['id'],

                    $this->postString(
                        'cancellation_reason'
                    )
                );

        if ($result['success']) {
            Flash::success(
                'Purchase return cancelled successfully.'
            );
        } else {
            Flash::danger(
                (string) $result['error']
            );
        }

        $this->redirect(
            '/purchase-returns/show?id=' .
            $id
        );
    }

    private function renderCreate(
        array $purchase,
        int $companyId,
        array $errors,
        array $old
    ): void {
        $this->view(
            'purchase_returns/create',
            [
                'title' =>
                    'Create Purchase Return',

                'purchase' =>
                    $purchase,

                'returnableItems' =>
                    $this->itemModel
                        ->returnableByPurchase(
                            (int) $purchase['id'],
                            $companyId
                        ),

                'reasonTypes' =>
                    $this->returnService
                        ->reasonTypes(),

                'errors' => $errors,
                'old' => $old,
            ]
        );
    }

    private function canManage(
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

    private function queryId(
        string $field
    ): int {
        $value = $_GET[$field] ?? null;

        $validated = filter_var(
            $value,
            FILTER_VALIDATE_INT
        );

        return $validated !== false &&
            $validated > 0
                ? $validated
                : 0;
    }

    private function postId(
        string $field
    ): int {
        $value = $_POST[$field] ?? null;

        $validated = filter_var(
            $value,
            FILTER_VALIDATE_INT
        );

        return $validated !== false &&
            $validated > 0
                ? $validated
                : 0;
    }

    private function queryString(
        string $field
    ): string {
        return isset($_GET[$field]) &&
            is_scalar($_GET[$field])
                ? trim(
                    (string) $_GET[$field]
                )
                : '';
    }

    private function postString(
        string $field
    ): string {
        return isset($_POST[$field]) &&
            is_scalar($_POST[$field])
                ? trim(
                    (string) $_POST[$field]
                )
                : '';
    }

    private function postArray(
        string $field
    ): array {
        return isset($_POST[$field]) &&
            is_array($_POST[$field])
                ? $_POST[$field]
                : [];
    }
}