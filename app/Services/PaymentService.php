<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Invoice;
use App\Models\Payment;
use DateTime;
use Exception;
use PDO;
use Throwable;

class PaymentService
{
    private PDO $db;

    private Payment $paymentModel;

    private Invoice $invoiceModel;

    private AuditLogService $auditLogService;

    private InvoiceDueService $invoiceDueService;

    public function __construct()
    {
        $this->db = Database::getConnection();

        $this->paymentModel = new Payment();
        $this->invoiceModel = new Invoice();

        $this->auditLogService =
            new AuditLogService();

        $this->invoiceDueService =
            new InvoiceDueService();
    }

    public function methods(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Card',
            'cash_on_delivery' =>
            'Cash on Delivery',
            'other' => 'Other',
        ];
    }

    public function summaryForInvoice(
        int $invoiceId,
        int $companyId
    ): ?array {
        $invoice =
            $this->invoiceModel
            ->findByIdAndCompany(
                $invoiceId,
                $companyId
            );

        if ($invoice === null) {
            return null;
        }

        return $this->calculateSummary(
            $invoice,
            $companyId
        );
    }

    public function recordPayment(
        int $invoiceId,
        int $companyId,
        int $userId,
        string $amountInput,
        string $paymentDate,
        string $paymentMethod,
        string $externalReference,
        string $note
    ): array {
        $amount = $this->parseAmount(
            $amountInput
        );

        if ($amount === null) {
            return [
                'success' => false,
                'payment_id' => null,
                'amount' => null,
                'remaining_balance' => null,
                'error' =>
                'Payment amount must be a positive number with maximum 2 decimal places.',
            ];
        }

        $externalReference =
            trim($externalReference);

        $note = trim($note);

        if (!$this->validDate($paymentDate)) {
            return [
                'success' => false,
                'payment_id' => null,
                'amount' => null,
                'remaining_balance' => null,
                'error' =>
                'Payment date is invalid.',
            ];
        }

        if ($paymentDate > date('Y-m-d')) {
            return [
                'success' => false,
                'payment_id' => null,
                'amount' => null,
                'remaining_balance' => null,
                'error' =>
                'Payment date cannot be in the future.',
            ];
        }

        $methods = $this->methods();

        if (
            !array_key_exists(
                $paymentMethod,
                $methods
            )
        ) {
            return [
                'success' => false,
                'payment_id' => null,
                'amount' => null,
                'remaining_balance' => null,
                'error' =>
                'Invalid payment method.',
            ];
        }

        if (
            mb_strlen($externalReference) > 100
        ) {
            return [
                'success' => false,
                'payment_id' => null,
                'amount' => null,
                'remaining_balance' => null,
                'error' =>
                'External reference must be maximum 100 characters.',
            ];
        }

        if (mb_strlen($note) > 2000) {
            return [
                'success' => false,
                'payment_id' => null,
                'amount' => null,
                'remaining_balance' => null,
                'error' =>
                'Payment note must be maximum 2000 characters.',
            ];
        }

        try {
            $this->db->beginTransaction();

            /*
             * Заключваме фактурата.
             *
             * Ако две плащания бъдат изпратени
             * едновременно, второто ще изчака
             * първото да приключи и след това
             * ще използва новия оставащ баланс.
             */
            $invoice =
                $this->invoiceModel
                ->findForUpdate(
                    $invoiceId,
                    $companyId
                );

            if ($invoice === null) {
                throw new Exception(
                    'Invoice was not found.'
                );
            }

            if (
                (string) $invoice['document_type'] !== 'invoice'
            ) {
                throw new Exception(
                    'Payments can only be recorded for invoices.'
                );
            }

            if (
                (string) $invoice['status'] !==
                'issued'
            ) {
                throw new Exception(
                    'Payments can only be recorded for issued invoices.'
                );
            }

            $summary =
                $this->calculateSummary(
                    $invoice,
                    $companyId
                );

            $balanceDue = round(
                (float) $summary['balance_due'],
                2
            );

            if ($balanceDue <= 0) {
                throw new Exception(
                    'This invoice does not have an outstanding balance.'
                );
            }

            if ($amount > $balanceDue) {
                throw new Exception(
                    'Payment amount cannot exceed the outstanding balance of ' .
                        number_format(
                            $balanceDue,
                            2,
                            '.',
                            ''
                        ) .
                        ' ' .
                        (string) $invoice['currency'] .
                        '.'
                );
            }

            $paymentId =
                $this->paymentModel->create([
                    'company_id' => $companyId,

                    'invoice_id' => $invoiceId,

                    'received_by_user_id' =>
                    $userId,

                    'payment_date' =>
                    $paymentDate,

                    'amount' => $amount,

                    'currency' =>
                    (string) $invoice['currency'],

                    'payment_method' =>
                    $paymentMethod,

                    'external_reference' =>
                    $this->nullableString(
                        $externalReference
                    ),

                    'note' =>
                    $this->nullableString(
                        $note
                    ),
                ]);

            $remainingBalance = round(
                $balanceDue - $amount,
                2
            );

            if (
                abs($remainingBalance) < 0.01
            ) {
                $remainingBalance = 0.00;
            }

            $invoiceNumber =
                (string) $invoice['invoice_number'];

            $this->auditLogService->log(
                $companyId,
                $userId,
                'create',
                'payment',
                $paymentId,
                'Recorded payment of ' .
                    number_format(
                        $amount,
                        2,
                        '.',
                        ''
                    ) .
                    ' ' .
                    (string) $invoice['currency'] .
                    ' for invoice ' .
                    $invoiceNumber .
                    '. Remaining balance: ' .
                    number_format(
                        $remainingBalance,
                        2,
                        '.',
                        ''
                    ) .
                    ' ' .
                    (string) $invoice['currency']
            );

            $this->db->commit();

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'amount' => $amount,

                'remaining_balance' =>
                $remainingBalance,

                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'payment_id' => null,
                'amount' => null,
                'remaining_balance' => null,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function cancelPayment(
        int $paymentId,
        int $companyId,
        int $userId,
        string $reason
    ): array {
        $reason = trim($reason);

        if ($reason === '') {
            return [
                'success' => false,
                'cancelled' => false,
                'error' =>
                'Cancellation reason is required.',
            ];
        }

        if (mb_strlen($reason) > 500) {
            return [
                'success' => false,
                'cancelled' => false,
                'error' =>
                'Cancellation reason must be maximum 500 characters.',
            ];
        }

        $preview =
            $this->paymentModel
            ->findByIdAndCompany(
                $paymentId,
                $companyId
            );

        if ($preview === null) {
            return [
                'success' => false,
                'cancelled' => false,
                'error' =>
                'Payment was not found.',
            ];
        }

        try {
            $this->db->beginTransaction();

            $invoice =
                $this->invoiceModel
                ->findForUpdate(
                    (int) $preview['invoice_id'],
                    $companyId
                );

            if ($invoice === null) {
                throw new Exception(
                    'Invoice was not found.'
                );
            }

            $payment =
                $this->paymentModel
                ->findForUpdate(
                    $paymentId,
                    $companyId
                );

            if ($payment === null) {
                throw new Exception(
                    'Payment was not found.'
                );
            }

            if (
                (string) $payment['status'] ===
                'cancelled'
            ) {
                $this->db->commit();

                return [
                    'success' => true,
                    'cancelled' => false,
                    'error' => null,
                ];
            }

            if (
                (string) $payment['status'] !==
                'completed'
            ) {
                throw new Exception(
                    'This payment cannot be cancelled.'
                );
            }

            $updated =
                $this->paymentModel
                ->markAsCancelled(
                    $paymentId,
                    $companyId,
                    $userId,
                    $reason
                );

            if (!$updated) {
                throw new Exception(
                    'The payment could not be cancelled.'
                );
            }

            $reference =
                $this->paymentReference(
                    $paymentId
                );

            $this->auditLogService->log(
                $companyId,
                $userId,
                'cancel',
                'payment',
                $paymentId,
                'Cancelled payment ' .
                    $reference .
                    '. Reason: ' .
                    $reason
            );

            $this->db->commit();

            return [
                'success' => true,
                'cancelled' => true,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'cancelled' => false,
                'error' =>
                $exception->getMessage(),
            ];
        }
    }

    public function paymentReference(
        int $paymentId
    ): string {
        return 'PAY-' . str_pad(
            (string) $paymentId,
            8,
            '0',
            STR_PAD_LEFT
        );
    }

    private function calculateSummary(
        array $invoice,
        int $companyId
    ): array {
        $invoiceId =
            (int) $invoice['id'];

        $invoiceTotal = round(
            (float) $invoice['total_amount'],
            2
        );

        $creditTotal =
            $this->invoiceModel
            ->issuedCreditTotalForInvoice(
                $invoiceId,
                $companyId
            );

        $adjustedTotal = round(
            $invoiceTotal - $creditTotal,
            2
        );

        if ($adjustedTotal < 0) {
            $adjustedTotal = 0.00;
        }

        $paidAmount =
            $this->paymentModel
            ->totalCompletedForInvoice(
                $invoiceId,
                $companyId
            );

        $rawBalance = round(
            $adjustedTotal - $paidAmount,
            2
        );

        if (abs($rawBalance) < 0.01) {
            $rawBalance = 0.00;
        }

        $balanceDue = max(
            0,
            $rawBalance
        );

        $overpaidAmount = max(
            0,
            -$rawBalance
        );

        $paymentStatus = 'unpaid';

        if ($overpaidAmount > 0) {
            $paymentStatus = 'overpaid';
        } elseif ($balanceDue <= 0) {
            $paymentStatus = 'paid';
        } elseif ($paidAmount > 0) {
            $paymentStatus =
                'partially_paid';
        }

        $dueInformation =
            $this->invoiceDueService
            ->information(
                $invoice,
                $balanceDue
            );

        return [
            'invoice_total' =>
            $invoiceTotal,

            'credit_total' =>
            $creditTotal,

            'adjusted_total' =>
            $adjustedTotal,

            'paid_amount' =>
            $paidAmount,

            'balance_due' =>
            round($balanceDue, 2),

            'overpaid_amount' =>
            round($overpaidAmount, 2),

            'payment_status' =>
            $paymentStatus,

            'currency' =>
            (string) $invoice['currency'],

            'due_status' =>
            $dueInformation['status'],

            'due_label' =>
            $dueInformation['label'],

            'due_badge_class' =>
            $dueInformation['badge_class'],

            'is_overdue' =>
            $dueInformation['is_overdue'],

            'days_overdue' =>
            $dueInformation['days_overdue'],

            'days_until_due' =>
            $dueInformation['days_until_due'],

            'due_date' =>
            $invoice['due_date'] ?? null,
        ];
    }

    private function validDate(
        string $value
    ): bool {
        $date = DateTime::createFromFormat(
            'Y-m-d',
            $value
        );

        if ($date === false) {
            return false;
        }

        return $date->format('Y-m-d') ===
            $value;
    }

    private function nullableString(
        string $value
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return $value;
    }

    private function parseAmount(
        string $value
    ): ?float {
        $value = trim($value);

        $value = str_replace(
            [
                ' ',
                ',',
            ],
            [
                '',
                '.',
            ],
            $value
        );

        /*
         * DECIMAL(12,2):
         * максимум 10 цифри преди десетичната
         * точка и максимум 2 след нея.
         */
        $validAmount = preg_match(
            '/^\d{1,10}(?:\.\d{1,2})?$/',
            $value
        );

        if ($validAmount !== 1) {
            return null;
        }

        $amount = round(
            (float) $value,
            2
        );

        if ($amount <= 0) {
            return null;
        }

        return $amount;
    }
}