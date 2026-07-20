<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Promotion;
use App\Services\AuthService;
use App\Services\PromotionService;

class PromotionController extends Controller
{
    private Promotion $promotionModel;

    private PromotionService $promotionService;

    private AuthService $authService;

    public function __construct()
    {
        $this->promotionModel =
            new Promotion();

        $this->promotionService =
            new PromotionService();

        $this->authService =
            new AuthService();
    }

    public function index(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $activeFilter = null;

        $activeInput =
            $this->queryString('active');

        if ($activeInput === '1') {
            $activeFilter = 1;
        }

        if ($activeInput === '0') {
            $activeFilter = 0;
        }

        $filters = [
            'search' =>
            $this->queryString('search'),

            'active' =>
            $activeFilter,
        ];

        $this->view(
            'promotions/index',
            [
                'title' => 'Promotions',

                'promotions' =>
                $this->promotionModel
                    ->allByCompany(
                        (int) $user['company_id'],
                        $filters
                    ),

                'discountTypes' =>
                $this->promotionService
                    ->discountTypes(),

                'filters' => [
                    'search' =>
                    $filters['search'],

                    'active' =>
                    $activeInput,
                ],
            ]
        );
    }

    public function create(): void
    {
        $this->renderForm(
            'promotions/create',
            'New Promotion',
            null,
            [],
            [
                'name' => '',
                'code' => '',

                'discount_type' =>
                'percentage',

                'discount_value' => '',

                'maximum_discount_amount' =>
                '',

                'minimum_order_amount' =>
                '0.00',

                'starts_on' =>
                date('Y-m-d'),

                'ends_on' => '',
                'max_uses' => '',
                'is_active' => 1,
                'notes' => '',
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

        $input = $this->formInput();

        $result =
            $this->promotionService
            ->create(
                (int) $user['company_id'],

                (int) $user['id'],

                $input
            );

        if (!$result['success']) {
            $this->renderForm(
                'promotions/create',
                'New Promotion',
                null,
                [(string) $result['error']],
                $input
            );

            return;
        }

        Flash::success(
            'Promotion created successfully.'
        );

        $this->redirect('/promotions');
    }

    public function edit(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $promotion =
            $this->promotionModel
            ->findByIdAndCompany(
                $this->queryId('id'),

                (int) $user['company_id']
            );

        if ($promotion === null) {
            $this->abort(404);
            return;
        }

        $this->renderForm(
            'promotions/edit',
            'Edit Promotion',
            $promotion,
            [],
            $promotion
        );
    }

    public function update(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $promotionId =
            $this->postId(
                'promotion_id'
            );

        $input = $this->formInput();

        $result =
            $this->promotionService
            ->update(
                $promotionId,

                (int) $user['company_id'],

                (int) $user['id'],

                $input
            );

        if (!$result['success']) {
            $promotion =
                $this->promotionModel
                ->findByIdAndCompany(
                    $promotionId,

                    (int) $user['company_id']
                );

            if ($promotion === null) {
                $this->abort(404);
                return;
            }

            $this->renderForm(
                'promotions/edit',
                'Edit Promotion',
                $promotion,
                [(string) $result['error']],
                $input
            );

            return;
        }

        Flash::success(
            'Promotion updated successfully.'
        );

        $this->redirect('/promotions');
    }

    public function toggle(): void
    {
        $user = $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            return;
        }

        $active =
            $this->postString('active') ===
            '1';

        $result =
            $this->promotionService
            ->setActive(
                $this->postId(
                    'promotion_id'
                ),

                (int) $user['company_id'],

                (int) $user['id'],

                $active
            );

        if ($result['success']) {
            Flash::success(
                $active
                    ? 'Promotion activated.'
                    : 'Promotion deactivated.'
            );
        } else {
            Flash::danger(
                (string) $result['error']
            );
        }

        $this->redirect('/promotions');
    }

    private function renderForm(
        string $view,
        string $title,
        ?array $promotion,
        array $errors,
        array $old
    ): void {
        $this->view(
            $view,
            [
                'title' => $title,

                'promotion' =>
                $promotion,

                'discountTypes' =>
                $this->promotionService
                    ->discountTypes(),

                'errors' =>
                $errors,

                'old' =>
                $old,
            ]
        );
    }

    private function formInput(): array
    {
        return [
            'name' =>
            $this->postString('name'),

            'code' =>
            $this->postString('code'),

            'discount_type' =>
            $this->postString(
                'discount_type'
            ),

            'discount_value' =>
            $this->postString(
                'discount_value'
            ),

            'maximum_discount_amount' =>
            $this->postString(
                'maximum_discount_amount'
            ),

            'minimum_order_amount' =>
            $this->postString(
                'minimum_order_amount'
            ),

            'starts_on' =>
            $this->postString(
                'starts_on'
            ),

            'ends_on' =>
            $this->postString(
                'ends_on'
            ),

            'max_uses' =>
            $this->postString(
                'max_uses'
            ),

            'is_active' =>
            isset($_POST['is_active'])
                ? 1
                : 0,

            'notes' =>
            $this->postString('notes'),
        ];
    }

    private function queryId(
        string $field
    ): int {
        $value = filter_var(
            $_GET[$field] ?? null,
            FILTER_VALIDATE_INT
        );

        return $value !== false &&
            $value > 0
            ? $value
            : 0;
    }

    private function postId(
        string $field
    ): int {
        $value = filter_var(
            $_POST[$field] ?? null,
            FILTER_VALIDATE_INT
        );

        return $value !== false &&
            $value > 0
            ? $value
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
}
