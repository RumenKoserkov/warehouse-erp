<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Session;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuthService;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    private Payment $paymentModel;

    private Invoice $invoiceModel;

    private AuthService $authService;

    private PaymentService $paymentService;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->invoiceModel = new Invoice();

        $this->authService = new AuthService();

        $this->paymentService =
            new PaymentService();
    }

    public function index(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $search = '';

        if (isset($_GET['search'])) {
            $search = trim(
                (string) $_GET['search']
            );
        }

        $payments =
            $this->paymentModel
                ->allByCompany(
                    (int) $currentUser[
                        'company_id'
                    ],
                    $search
                );

        $this->view('payments/index', [
            'title' => 'Payments',
            'payments' => $payments,
            'search' => $search,

            'methods' =>
                $this->paymentService
                    ->methods(),
        ]);
    }

    public function create(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $invoiceId =
            $this->queryId('invoice_id');

        if ($invoiceId <= 0) {
            $this->abort(404);

            return;
        }

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $invoice =
            $this->invoiceModel
                ->findByIdAndCompany(
                    $invoiceId,
                    $companyId
                );

        if (
            $invoice === null ||
            (string) $invoice[
                'document_type'
            ] !== 'invoice' ||
            (string) $invoice['status'] !==
                'issued'
        ) {
            $this->abort(404);

            return;
        }

        $summary =
            $this->paymentService
                ->summaryForInvoice(
                    $invoiceId,
                    $companyId
                );

        if (
            $summary === null ||
            (float) $summary[
                'balance_due'
            ] <= 0
        ) {
            Flash::danger(
                'This invoice does not have an outstanding balance.'
            );

            $this->redirect(
                '/invoices/show?id=' .
                $invoiceId
            );

            return;
        }

        $old = [
            'invoice_id' =>
                (string) $invoiceId,

            'amount' =>
                number_format(
                    (float) $summary[
                        'balance_due'
                    ],
                    2,
                    '.',
                    ''
                ),

            'payment_date' =>
                date('Y-m-d'),

            'payment_method' =>
                'bank_transfer',

            'external_reference' =>
                '',

            'note' => '',
        ];

        $sessionOld = Session::get(
            'payment_old'
        );

        Session::remove(
            'payment_old'
        );

        if (
            is_array($sessionOld) &&
            isset($sessionOld['invoice_id']) &&
            (int) $sessionOld['invoice_id'] ===
                $invoiceId
        ) {
            $old = array_merge(
                $old,
                $sessionOld
            );
        }

        $this->view('payments/create', [
            'title' => 'Record Payment',

            'invoice' => $invoice,
            'summary' => $summary,

            'methods' =>
                $this->paymentService
                    ->methods(),

            'old' => $old,
        ]);
    }

    public function store(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $invoiceId =
            $this->postId('invoice_id');

        if ($invoiceId <= 0) {
            Flash::danger(
                'Invalid invoice.'
            );

            $this->redirect('/invoices');

            return;
        }

        $amount = $this->input(
            'amount'
        );

        $paymentDate = $this->input(
            'payment_date'
        );

        $paymentMethod = $this->input(
            'payment_method'
        );

        $externalReference =
            $this->input(
                'external_reference'
            );

        $note = $this->input('note');

        $result =
            $this->paymentService
                ->recordPayment(
                    $invoiceId,

                    (int) $currentUser[
                        'company_id'
                    ],

                    (int) $currentUser['id'],

                    $amount,

                    $paymentDate,

                    $paymentMethod,

                    $externalReference,

                    $note
                );

        if (!$result['success']) {
            Session::set(
                'payment_old',
                [
                    'invoice_id' =>
                        (string) $invoiceId,

                    'amount' => $amount,

                    'payment_date' =>
                        $paymentDate,

                    'payment_method' =>
                        $paymentMethod,

                    'external_reference' =>
                        $externalReference,

                    'note' => $note,
                ]
            );

            Flash::danger(
                (string) $result['error']
            );

            $this->redirect(
                '/payments/create?invoice_id=' .
                $invoiceId
            );

            return;
        }

        $amountText = number_format(
            (float) $result['amount'],
            2,
            '.',
            ''
        );

        $remainingBalance = (float) $result[
            'remaining_balance'
        ];

        if ($remainingBalance > 0) {
            Flash::success(
                'Payment of ' .
                $amountText .
                ' recorded successfully. ' .
                'Remaining balance: ' .
                number_format(
                    $remainingBalance,
                    2,
                    '.',
                    ''
                ) .
                '.'
            );
        } else {
            Flash::success(
                'Payment of ' .
                $amountText .
                ' recorded successfully. ' .
                'The invoice is now fully paid.'
            );
        }

        $this->redirect(
            '/payments/show?id=' .
            (int) $result['payment_id']
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

        $paymentId = $this->queryId('id');

        if ($paymentId <= 0) {
            $this->abort(404);

            return;
        }

        $companyId =
            (int) $currentUser[
                'company_id'
            ];

        $payment =
            $this->paymentModel
                ->findByIdAndCompany(
                    $paymentId,
                    $companyId
                );

        if ($payment === null) {
            $this->abort(404);

            return;
        }

        $summary =
            $this->paymentService
                ->summaryForInvoice(
                    (int) $payment[
                        'invoice_id'
                    ],
                    $companyId
                );

        $this->view('payments/show', [
            'title' => 'Payment Details',
            'payment' => $payment,
            'summary' => $summary,

            'methods' =>
                $this->paymentService
                    ->methods(),

            'paymentReference' =>
                $this->paymentService
                    ->paymentReference(
                        $paymentId
                    ),
        ]);
    }

    public function cancel(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $paymentId =
            $this->postId('payment_id');

        if ($paymentId <= 0) {
            Flash::danger(
                'Invalid payment.'
            );

            $this->redirect('/payments');

            return;
        }

        $result =
            $this->paymentService
                ->cancelPayment(
                    $paymentId,

                    (int) $currentUser[
                        'company_id'
                    ],

                    (int) $currentUser['id'],

                    $this->input(
                        'cancellation_reason'
                    )
                );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );

            $this->redirect(
                '/payments/show?id=' .
                $paymentId
            );

            return;
        }

        if ($result['cancelled']) {
            Flash::success(
                'Payment cancelled successfully.'
            );
        } else {
            Flash::success(
                'This payment is already cancelled.'
            );
        }

        $this->redirect(
            '/payments/show?id=' .
            $paymentId
        );
    }

    private function queryId(
        string $field
    ): int {
        if (!isset($_GET[$field])) {
            return 0;
        }

        $validatedId = filter_var(
            $_GET[$field],
            FILTER_VALIDATE_INT
        );

        if (
            $validatedId === false ||
            $validatedId <= 0
        ) {
            return 0;
        }

        return $validatedId;
    }

    private function postId(
        string $field
    ): int {
        if (!isset($_POST[$field])) {
            return 0;
        }

        $validatedId = filter_var(
            $_POST[$field],
            FILTER_VALIDATE_INT
        );

        if (
            $validatedId === false ||
            $validatedId <= 0
        ) {
            return 0;
        }

        return $validatedId;
    }

    private function input(
        string $field
    ): string {
        if (!isset($_POST[$field])) {
            return '';
        }

        if (!is_scalar($_POST[$field])) {
            return '';
        }

        return trim(
            (string) $_POST[$field]
        );
    }
}