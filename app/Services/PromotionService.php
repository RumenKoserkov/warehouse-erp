<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Promotion;
use DateTimeImmutable;
use Exception;
use PDO;
use Throwable;

class PromotionService
{
    private PDO $db;

    private Promotion $promotionModel;

    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->db = Database::getConnection();

        $this->promotionModel =
            new Promotion();

        $this->auditLogService =
            new AuditLogService();
    }

    public function discountTypes(): array
    {
        return [
            'percentage' =>
            'Percentage Discount',

            'fixed_amount' =>
            'Fixed Amount Discount',
        ];
    }

    public function create(
        int $companyId,
        int $userId,
        array $input
    ): array {
        try {
            $data = $this->validateInput(
                $companyId,
                $input
            );

            $this->db->beginTransaction();

            $promotionId =
                $this->promotionModel
                ->create(
                    array_merge(
                        $data,
                        [
                            'company_id' =>
                            $companyId,

                            'created_by_user_id' =>
                            $userId,
                        ]
                    )
                );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'promotion',
                $promotionId,
                'Created promotion ' .
                    $data['name'] .
                    (
                        $data['code'] !== null
                        ? ' (' .
                        $data['code'] .
                        ')'
                        : ''
                    ) .
                    '.'
            );

            $this->db->commit();

            return [
                'success' => true,
                'promotion_id' =>
                $promotionId,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'promotion_id' => null,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function update(
        int $promotionId,
        int $companyId,
        int $userId,
        array $input
    ): array {
        try {
            $promotion =
                $this->promotionModel
                ->findByIdAndCompany(
                    $promotionId,
                    $companyId
                );

            if ($promotion === null) {
                throw new Exception(
                    'Promotion was not found.'
                );
            }

            $data = $this->validateInput(
                $companyId,
                $input,
                $promotionId
            );

            $this->db->beginTransaction();

            $this->promotionModel->update(
                $promotionId,
                $companyId,
                $data
            );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'update',
                'promotion',
                $promotionId,
                'Updated promotion ' .
                    $data['name'] .
                    '.'
            );

            $this->db->commit();

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function setActive(
        int $promotionId,
        int $companyId,
        int $userId,
        bool $active
    ): array {
        try {
            $promotion =
                $this->promotionModel
                ->findByIdAndCompany(
                    $promotionId,
                    $companyId
                );

            if ($promotion === null) {
                throw new Exception(
                    'Promotion was not found.'
                );
            }

            $this->promotionModel->setActive(
                $promotionId,
                $companyId,
                $active
            );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'update',
                'promotion',
                $promotionId,
                (
                    $active
                    ? 'Activated promotion '
                    : 'Deactivated promotion '
                ) .
                    (string) $promotion['name'] .
                    '.'
            );

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function applyToItems(
        int $promotionId,
        string $enteredCode,
        int $companyId,
        array $items
    ): array {
        if ($promotionId <= 0) {
            return [
                'items' => $items,
                'promotion' => null,
                'discount_amount' => 0.0,
            ];
        }

        $promotion =
            $this->promotionModel
            ->findForUpdate(
                $promotionId,
                $companyId
            );

        if ($promotion === null) {
            throw new Exception(
                'Selected promotion was not found.'
            );
        }

        $this->validateAvailability(
            $promotion,
            $enteredCode
        );

        $eligibleAmount = 0.0;

        foreach ($items as $item) {
            $eligibleAmount += max(
                0,
                (float) $item['net_amount']
            );
        }

        $eligibleAmount = round(
            $eligibleAmount,
            2
        );

        if ($eligibleAmount <= 0) {
            throw new Exception(
                'The sale has no eligible amount for a promotion.'
            );
        }

        if (
            $eligibleAmount + 0.005 <
            (float) $promotion['minimum_order_amount']
        ) {
            throw new Exception(
                'This promotion requires a minimum order amount of ' .
                    number_format(
                        (float) $promotion['minimum_order_amount'],
                        2,
                        '.',
                        ''
                    ) .
                    '.'
            );
        }

        if (
            $promotion['discount_type'] ===
            'percentage'
        ) {
            $discountAmount = round(
                $eligibleAmount *
                    (
                        (float) $promotion['discount_value'] / 100
                    ),
                2
            );
        } else {
            $discountAmount = round(
                (float) $promotion['discount_value'],
                2
            );
        }

        if (
            $promotion['maximum_discount_amount'] !== null
        ) {
            $discountAmount = min(
                $discountAmount,
                round(
                    (float) $promotion['maximum_discount_amount'],
                    2
                )
            );
        }

        $discountAmount = min(
            $discountAmount,
            $eligibleAmount
        );

        if ($discountAmount <= 0) {
            throw new Exception(
                'The selected promotion does not produce a valid discount.'
            );
        }

        $remainingDiscount =
            $discountAmount;

        $remainingBase =
            $eligibleAmount;

        $lastEligibleIndex = null;

        foreach ($items as $index => $item) {
            if (
                (float) $item['net_amount'] > 0
            ) {
                $lastEligibleIndex = $index;
            }
        }

        foreach ($items as $index => &$item) {
            $lineNet = round(
                max(
                    0,
                    (float) $item['net_amount']
                ),
                2
            );

            $linePromotionDiscount = 0.0;

            if ($lineNet > 0) {
                if (
                    $index ===
                    $lastEligibleIndex
                ) {
                    $linePromotionDiscount =
                        $remainingDiscount;
                } else {
                    $linePromotionDiscount =
                        round(
                            $remainingDiscount *
                                (
                                    $lineNet /
                                    $remainingBase
                                ),
                            2
                        );
                }

                $linePromotionDiscount = min(
                    $linePromotionDiscount,
                    $lineNet,
                    $remainingDiscount
                );

                $remainingDiscount = round(
                    $remainingDiscount -
                        $linePromotionDiscount,
                    2
                );

                $remainingBase = round(
                    $remainingBase -
                        $lineNet,
                    2
                );
            }

            $newNet = round(
                $lineNet -
                    $linePromotionDiscount,
                2
            );

            $vatRate = round(
                (float) (
                    $item['vat_rate'] ?? 0
                ),
                2
            );

            $newTax = round(
                $newNet *
                    ($vatRate / 100),
                2
            );

            $newTotal = round(
                $newNet + $newTax,
                2
            );

            $item['promotion_discount_amount'] = $linePromotionDiscount;

            $item['discount_amount'] =
                round(
                    (float) $item['discount_amount'] +
                        $linePromotionDiscount,
                    2
                );

            $item['net_amount'] =
                $newNet;

            $item['tax_amount'] =
                $newTax;

            $item['total_price'] =
                $newTotal;

            if (
                array_key_exists(
                    'total_amount',
                    $item
                )
            ) {
                $item['total_amount'] =
                    $newTotal;
            }
        }

        unset($item);

        return [
            'items' => $items,
            'promotion' => $promotion,
            'discount_amount' =>
            $discountAmount,
        ];
    }

    public function recordUsage(
        int $saleId,
        int $companyId,
        array $appliedPromotion
    ): void {
        if (
            $appliedPromotion['promotion'] ===
            null
        ) {
            return;
        }

        $promotion =
            $appliedPromotion['promotion'];

        $discountAmount =
            (float) $appliedPromotion['discount_amount'];

        if (
            !$this->promotionModel
                ->incrementUsage(
                    (int) $promotion['id'],
                    $companyId
                )
        ) {
            throw new Exception(
                'The promotion usage limit has been reached.'
            );
        }

        $this->promotionModel
            ->attachToSale(
                $saleId,
                $companyId,
                $promotion,
                $discountAmount
            );

        $this->promotionModel
            ->createUsage(
                $companyId,
                (int) $promotion['id'],
                $saleId,
                (string) $promotion['name'],
                $promotion['code'] !== null
                    ? (string) $promotion['code']
                    : null,
                $discountAmount
            );
    }

    public function releaseForCancelledSale(
        int $saleId,
        int $companyId
    ): void {
        $usage =
            $this->promotionModel
            ->usageForSaleForUpdate(
                $saleId,
                $companyId
            );

        if ($usage === null) {
            return;
        }

        if (
            !$this->promotionModel
                ->cancelUsage(
                    (int) $usage['id'],
                    $companyId
                )
        ) {
            throw new Exception(
                'Promotion usage could not be cancelled.'
            );
        }

        $this->promotionModel
            ->decrementUsage(
                (int) $usage['promotion_id'],
                $companyId
            );
    }

    private function validateAvailability(
        array $promotion,
        string $enteredCode
    ): void {
        if (
            (int) $promotion['is_active'] !== 1
        ) {
            throw new Exception(
                'Selected promotion is inactive.'
            );
        }

        $today = date('Y-m-d');

        if (
            (string) $promotion['starts_on'] >
            $today
        ) {
            throw new Exception(
                'Selected promotion has not started yet.'
            );
        }

        if (
            $promotion['ends_on'] !== null &&
            (string) $promotion['ends_on'] <
            $today
        ) {
            throw new Exception(
                'Selected promotion has expired.'
            );
        }

        if (
            $promotion['max_uses'] !== null &&
            (int) $promotion['used_count'] >=
            (int) $promotion['max_uses']
        ) {
            throw new Exception(
                'Selected promotion has reached its usage limit.'
            );
        }

        $storedCode = $this->normalizeCode(
            (string) (
                $promotion['code'] ?? ''
            )
        );

        if ($storedCode !== '') {
            $submittedCode =
                $this->normalizeCode(
                    $enteredCode
                );

            if (
                $submittedCode === '' ||
                !hash_equals(
                    $storedCode,
                    $submittedCode
                )
            ) {
                throw new Exception(
                    'Promotion code is invalid.'
                );
            }
        }
    }

    private function validateInput(
        int $companyId,
        array $input,
        ?int $excludeId = null
    ): array {
        $name = trim(
            (string) (
                $input['name'] ?? ''
            )
        );

        if ($name === '') {
            throw new Exception(
                'Promotion name is required.'
            );
        }

        if (mb_strlen($name) > 255) {
            throw new Exception(
                'Promotion name must be maximum 255 characters.'
            );
        }

        $code = $this->normalizeCode(
            (string) (
                $input['code'] ?? ''
            )
        );

        if (mb_strlen($code) > 100) {
            throw new Exception(
                'Promotion code must be maximum 100 characters.'
            );
        }

        if (
            $code !== '' &&
            preg_match(
                '/^[A-Z0-9_-]+$/',
                $code
            ) !== 1
        ) {
            throw new Exception(
                'Promotion code may contain only letters, numbers, underscore and hyphen.'
            );
        }

        if (
            $code !== '' &&
            $this->promotionModel
            ->codeExists(
                $companyId,
                $code,
                $excludeId
            )
        ) {
            throw new Exception(
                'Promotion code already exists.'
            );
        }

        $discountType = trim(
            (string) (
                $input['discount_type'] ??
                ''
            )
        );

        if (
            !array_key_exists(
                $discountType,
                $this->discountTypes()
            )
        ) {
            throw new Exception(
                'Invalid discount type.'
            );
        }

        $discountValue =
            $this->decimal(
                $input['discount_value'] ??
                    ''
            );

        if ($discountValue <= 0) {
            throw new Exception(
                'Discount value must be greater than zero.'
            );
        }

        if (
            $discountType ===
            'percentage' &&
            $discountValue > 100
        ) {
            throw new Exception(
                'Percentage discount cannot exceed 100%.'
            );
        }

        $minimumOrderAmount =
            $this->decimal(
                $input['minimum_order_amount'] ?? 0,
                true
            );

        $maximumDiscountAmount =
            $this->nullableDecimal(
                $input['maximum_discount_amount'] ?? null
            );

        if (
            $maximumDiscountAmount !== null &&
            $maximumDiscountAmount <= 0
        ) {
            throw new Exception(
                'Maximum discount amount must be greater than zero.'
            );
        }

        $startsOn = trim(
            (string) (
                $input['starts_on'] ?? ''
            )
        );

        if (!$this->validDate($startsOn)) {
            throw new Exception(
                'Start date is invalid.'
            );
        }

        $endsOn = trim(
            (string) (
                $input['ends_on'] ?? ''
            )
        );

        if (
            $endsOn !== '' &&
            !$this->validDate($endsOn)
        ) {
            throw new Exception(
                'End date is invalid.'
            );
        }

        if (
            $endsOn !== '' &&
            $endsOn < $startsOn
        ) {
            throw new Exception(
                'End date cannot be before start date.'
            );
        }

        $maxUses =
            $this->nullableInteger(
                $input['max_uses'] ?? null
            );

        if (
            $maxUses !== null &&
            $maxUses <= 0
        ) {
            throw new Exception(
                'Maximum uses must be greater than zero.'
            );
        }

        $notes = trim(
            (string) (
                $input['notes'] ?? ''
            )
        );

        if (mb_strlen($notes) > 2000) {
            throw new Exception(
                'Notes must be maximum 2000 characters.'
            );
        }

        return [
            'name' => $name,

            'code' =>
            $code !== ''
                ? $code
                : null,

            'discount_type' =>
            $discountType,

            'discount_value' =>
            $discountValue,

            'maximum_discount_amount' =>
            $maximumDiscountAmount,

            'minimum_order_amount' =>
            $minimumOrderAmount,

            'starts_on' =>
            $startsOn,

            'ends_on' =>
            $endsOn !== ''
                ? $endsOn
                : null,

            'max_uses' =>
            $maxUses,

            'is_active' =>
            (int) ($input['is_active'] ?? 0) === 1
                ? 1
                : 0,

            'notes' =>
            $notes !== ''
                ? $notes
                : null,
        ];
    }

    private function normalizeCode(
        string $value
    ): string {
        return mb_strtoupper(
            trim($value)
        );
    }

    private function decimal(
        mixed $value,
        bool $allowZero = false
    ): float {
        if (!is_scalar($value)) {
            throw new Exception(
                'Invalid numeric value.'
            );
        }

        $value = str_replace(
            [' ', ','],
            ['', '.'],
            trim((string) $value)
        );

        if (
            preg_match(
                '/^\d+(?:\.\d{1,4})?$/',
                $value
            ) !== 1
        ) {
            throw new Exception(
                'Numeric values may use maximum 4 decimal places.'
            );
        }

        $number = round(
            (float) $value,
            4
        );

        if (
            !$allowZero &&
            $number <= 0
        ) {
            throw new Exception(
                'Numeric value must be greater than zero.'
            );
        }

        if (
            $allowZero &&
            $number < 0
        ) {
            throw new Exception(
                'Numeric value cannot be negative.'
            );
        }

        return $number;
    }

    private function nullableDecimal(
        mixed $value
    ): ?float {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        return $this->decimal($value);
    }

    private function nullableInteger(
        mixed $value
    ): ?int {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        $validated = filter_var(
            $value,
            FILTER_VALIDATE_INT
        );

        if ($validated === false) {
            throw new Exception(
                'Maximum uses must be a whole number.'
            );
        }

        return $validated;
    }

    private function validDate(
        string $value
    ): bool {
        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        return $date !== false &&
            $date->format('Y-m-d') ===
            $value;
    }
}