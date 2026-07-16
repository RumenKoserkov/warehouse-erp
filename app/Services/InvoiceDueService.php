<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use DateTimeImmutable;
use InvalidArgumentException;

class InvoiceDueService
{
    private const DEFAULT_PAYMENT_TERMS_DAYS = 14;

    private const MAX_PAYMENT_TERMS_DAYS = 365;

    private const DUE_SOON_DAYS = 7;

    private Setting $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    public function paymentTermsDays(
        int $companyId
    ): int {
        $value = $this->settingModel->get(
            $companyId,
            'payment_terms_days',
            (string) self::DEFAULT_PAYMENT_TERMS_DAYS
        );

        if (
            preg_match(
                '/^\d{1,3}$/',
                trim((string) $value)
            ) !== 1
        ) {
            return self::DEFAULT_PAYMENT_TERMS_DAYS;
        }

        $days = (int) $value;

        if (
            $days < 0 ||
            $days > self::MAX_PAYMENT_TERMS_DAYS
        ) {
            return self::DEFAULT_PAYMENT_TERMS_DAYS;
        }

        return $days;
    }

    public function calculateDueDate(
        int $companyId,
        string $baseDate
    ): string {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $baseDate
        );

        if (
            $date === false ||
            $date->format('Y-m-d') !== $baseDate
        ) {
            throw new InvalidArgumentException(
                'The invoice date is invalid.'
            );
        }

        $paymentTermsDays =
            $this->paymentTermsDays($companyId);

        if ($paymentTermsDays === 0) {
            return $date->format('Y-m-d');
        }

        return $date
            ->modify(
                '+' . $paymentTermsDays . ' days'
            )
            ->format('Y-m-d');
    }

    public function information(
        array $invoice,
        float $balanceDue
    ): array {
        $documentType = (string) (
            $invoice['document_type'] ?? ''
        );

        $documentStatus = (string) (
            $invoice['status'] ?? ''
        );

        if ($documentType !== 'invoice') {
            return $this->result(
                'not_applicable',
                'Not Applicable',
                'text-bg-secondary'
            );
        }

        if ($documentStatus === 'cancelled') {
            return $this->result(
                'cancelled',
                'Cancelled',
                'text-bg-danger'
            );
        }

        if ($documentStatus === 'draft') {
            return $this->result(
                'draft',
                'Draft',
                'text-bg-warning'
            );
        }

        if ($documentStatus !== 'issued') {
            return $this->result(
                'not_applicable',
                'Not Applicable',
                'text-bg-secondary'
            );
        }

        if ($balanceDue <= 0.009) {
            return $this->result(
                'paid',
                'Paid',
                'text-bg-success'
            );
        }

        $dueDateValue = trim(
            (string) (
                $invoice['due_date'] ?? ''
            )
        );

        if ($dueDateValue === '') {
            return $this->result(
                'no_due_date',
                'No Due Date',
                'text-bg-secondary'
            );
        }

        $dueDate = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dueDateValue
        );

        if (
            $dueDate === false ||
            $dueDate->format('Y-m-d') !==
                $dueDateValue
        ) {
            return $this->result(
                'invalid_due_date',
                'Invalid Due Date',
                'text-bg-danger'
            );
        }

        $today = new DateTimeImmutable('today');

        $difference = (int) $today
            ->diff($dueDate)
            ->format('%r%a');

        if ($difference < 0) {
            return $this->result(
                'overdue',
                'Overdue',
                'text-bg-danger',
                abs($difference),
                0
            );
        }

        if ($difference === 0) {
            return $this->result(
                'due_today',
                'Due Today',
                'text-bg-warning',
                0,
                0
            );
        }

        if (
            $difference <= self::DUE_SOON_DAYS
        ) {
            return $this->result(
                'due_soon',
                'Due Soon',
                'text-bg-warning',
                0,
                $difference
            );
        }

        return $this->result(
            'open',
            'Open',
            'text-bg-info',
            0,
            $difference
        );
    }

    private function result(
        string $status,
        string $label,
        string $badgeClass,
        int $daysOverdue = 0,
        int $daysUntilDue = 0
    ): array {
        return [
            'status' => $status,
            'label' => $label,
            'badge_class' => $badgeClass,
            'is_overdue' =>
                $status === 'overdue',
            'days_overdue' => $daysOverdue,
            'days_until_due' =>
                $daysUntilDue,
        ];
    }
}