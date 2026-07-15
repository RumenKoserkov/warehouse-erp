<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\AuthService;
use App\Services\InvoiceService;
use App\Services\TaxService;
use App\Services\InvoiceNumberService;
use App\Services\PdfService;
use RuntimeException;

class InvoiceController extends Controller
{
    private Invoice $invoiceModel;
    private InvoiceItem $invoiceItemModel;
    private Client $clientModel;
    private Product $productModel;
    private InvoiceNumberService $invoiceNumberService;
    private PdfService $pdfService;

    private AuthService $authService;
    private InvoiceService $invoiceService;
    private TaxService $taxService;

    public function __construct()
    {
        $this->invoiceModel = new Invoice();
        $this->invoiceItemModel =
            new InvoiceItem();

        $this->clientModel = new Client();
        $this->productModel = new Product();

        $this->authService = new AuthService();
        $this->invoiceService =
            new InvoiceService();

        $this->invoiceNumberService =
            new InvoiceNumberService();

        $this->pdfService = new PdfService();

        $this->taxService = new TaxService();
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

        $companyId =
            (int) $currentUser['company_id'];

        $invoices =
            $this->invoiceModel
            ->allByCompany(
                $companyId,
                $search
            );

        $sequence =
            $this->invoiceNumberService
            ->information($companyId);

        $canConfigureSequence = false;

        if (
            isset($currentUser['role_slug']) &&
            $currentUser['role_slug'] ===
            'administrator'
        ) {
            $canConfigureSequence = true;
        }

        $this->view('invoices/index', [
            'title' => 'Invoices',
            'invoices' => $invoices,
            'search' => $search,
            'sequence' => $sequence,

            'canConfigureSequence' =>
            $canConfigureSequence,
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

        $companyId =
            (int) $currentUser['company_id'];

        $this->renderCreate(
            $companyId,
            [],
            $this->emptyOldData()
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

        $companyId =
            (int) $currentUser['company_id'];

        $clientId = 0;

        if (isset($_POST['client_id'])) {
            $clientId =
                (int) $_POST['client_id'];
        }

        $invoiceDate =
            $this->input('invoice_date');

        $supplyDate =
            $this->input('supply_date');

        $dueDate =
            $this->input('due_date');

        $note = $this->input('note');

        $validator = new Validator([
            'client_id' => $clientId,
            'invoice_date' => $invoiceDate,
            'supply_date' => $supplyDate,
            'due_date' => $dueDate,
        ]);

        $validator
            ->required(
                'client_id',
                'Client is required.'
            )
            ->integer(
                'client_id',
                'Client must be valid.'
            )
            ->positive(
                'client_id',
                'Please select a client.'
            )
            ->required(
                'invoice_date',
                'Invoice date is required.'
            )
            ->date(
                'invoice_date',
                'Y-m-d',
                'Invoice date is invalid.'
            )
            ->required(
                'supply_date',
                'Supply date is required.'
            )
            ->date(
                'supply_date',
                'Y-m-d',
                'Supply date is invalid.'
            )
            ->date(
                'due_date',
                'Y-m-d',
                'Due date is invalid.'
            );

        $errors = $validator->all();

        if ($clientId > 0) {
            $client =
                $this->clientModel
                ->findByIdAndCompany(
                    $clientId,
                    $companyId
                );

            if ($client === null) {
                $errors[] =
                    'Selected client was not found.';
            }
        }

        if (
            $dueDate !== '' &&
            $invoiceDate !== '' &&
            $dueDate < $invoiceDate
        ) {
            $errors[] =
                'Due date cannot be before invoice date.';
        }

        $items = $this->getItemsFromRequest();

        if (empty($items)) {
            $errors[] =
                'Invoice must have at least one item.';
        }

        $old = [
            'client_id' => (string) $clientId,
            'invoice_date' => $invoiceDate,
            'supply_date' => $supplyDate,
            'due_date' => $dueDate,
            'note' => $note,
        ];

        if (!empty($errors)) {
            $this->renderCreate(
                $companyId,
                $errors,
                $old
            );

            return;
        }

        $preparedDueDate = null;

        if ($dueDate !== '') {
            $preparedDueDate = $dueDate;
        }

        $result =
            $this->invoiceService
            ->createDraft([
                'company_id' => $companyId,
                'client_id' => $clientId,

                'user_id' =>
                (int) $currentUser['id'],

                'invoice_date' =>
                $invoiceDate,

                'supply_date' =>
                $supplyDate,

                'due_date' =>
                $preparedDueDate,

                'note' => $note,
                'items' => $items,
            ]);

        if (!$result['success']) {
            $this->renderCreate(
                $companyId,
                [
                    (string) $result['error'],
                ],
                $old
            );

            return;
        }

        Flash::success(
            'Invoice draft created successfully.'
        );

        $this->redirect(
            '/invoices/show?id=' .
                (int) $result['invoice_id']
        );
    }

    public function generateFromSale(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $saleId = 0;

        if (isset($_POST['sale_id'])) {
            $validatedSaleId = filter_var(
                $_POST['sale_id'],
                FILTER_VALIDATE_INT
            );

            if (
                $validatedSaleId !== false &&
                $validatedSaleId > 0
            ) {
                $saleId = $validatedSaleId;
            }
        }

        if ($saleId <= 0) {
            Flash::danger(
                'Invalid sale.'
            );

            $this->redirect('/sales');

            return;
        }

        $result =
            $this->invoiceService
            ->createDraftFromSale(
                $saleId,
                (int) $currentUser['company_id'],
                (int) $currentUser['id']
            );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );

            $this->redirect(
                '/sales/show?id=' . $saleId
            );

            return;
        }

        if ($result['created']) {
            Flash::success(
                'Invoice draft generated from sale successfully.'
            );
        } else {
            Flash::success(
                'This sale already has an invoice.'
            );
        }

        $this->redirect(
            '/invoices/show?id=' .
                (int) $result['invoice_id']
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

        $id = 0;

        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }

        if ($id <= 0) {
            $this->abort(404);

            return;
        }

        $companyId =
            (int) $currentUser['company_id'];

        $invoice =
            $this->invoiceModel
            ->findByIdAndCompany(
                $id,
                $companyId
            );

        if ($invoice === null) {
            $this->abort(404);

            return;
        }

        $items =
            $this->invoiceItemModel
            ->allByInvoice(
                $id,
                $companyId
            );

        $this->view('invoices/show', [
            'title' => 'Invoice Draft',
            'invoice' => $invoice,
            'items' => $items,
        ]);
    }

    private function renderCreate(
        int $companyId,
        array $errors,
        array $old
    ): void {
        $this->view('invoices/create', [
            'title' => 'Create Invoice Draft',

            'clients' =>
            $this->clientModel
                ->activeByCompany(
                    $companyId
                ),

            'products' =>
            $this->productModel
                ->activeByCompany(
                    $companyId
                ),

            'taxConfiguration' =>
            $this->taxService
                ->salesConfiguration(
                    $companyId
                ),

            'errors' => $errors,
            'old' => $old,
        ]);
    }

    private function getItemsFromRequest(): array
    {
        $productIds = [];
        $quantities = [];
        $unitPrices = [];
        $discountAmounts = [];

        if (
            isset($_POST['product_id']) &&
            is_array($_POST['product_id'])
        ) {
            $productIds =
                $_POST['product_id'];
        }

        if (
            isset($_POST['quantity']) &&
            is_array($_POST['quantity'])
        ) {
            $quantities =
                $_POST['quantity'];
        }

        if (
            isset($_POST['unit_price']) &&
            is_array($_POST['unit_price'])
        ) {
            $unitPrices =
                $_POST['unit_price'];
        }

        if (
            isset($_POST['discount_amount']) &&
            is_array(
                $_POST['discount_amount']
            )
        ) {
            $discountAmounts =
                $_POST['discount_amount'];
        }

        $items = [];

        foreach ($productIds as $index => $productId) {
            $quantity = 0;
            $unitPrice = 0;
            $discountAmount = 0;

            if (isset($quantities[$index])) {
                $quantity =
                    (float) $quantities[$index];
            }

            if (isset($unitPrices[$index])) {
                $unitPrice =
                    (float) $unitPrices[$index];
            }

            if (
                isset($discountAmounts[$index])
            ) {
                $discountAmount =
                    (float) $discountAmounts[$index];
            }

            if ((int) $productId <= 0) {
                continue;
            }

            $items[] = [
                'product_id' =>
                (int) $productId,

                'quantity' => $quantity,

                'unit_price' =>
                $unitPrice,

                'discount_amount' =>
                $discountAmount,
            ];
        }

        return $items;
    }

    private function emptyOldData(): array
    {
        return [
            'client_id' => '',
            'invoice_date' => date('Y-m-d'),
            'supply_date' => date('Y-m-d'),
            'due_date' => '',
            'note' => '',
        ];
    }

    private function input(string $field): string
    {
        if (!isset($_POST[$field])) {
            return '';
        }

        if (!is_scalar($_POST[$field])) {
            return '';
        }

        return trim((string) $_POST[$field]);
    }

    public function issue(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $invoiceId = 0;

        if (isset($_POST['invoice_id'])) {
            $validatedInvoiceId = filter_var(
                $_POST['invoice_id'],
                FILTER_VALIDATE_INT
            );

            if (
                $validatedInvoiceId !== false &&
                $validatedInvoiceId > 0
            ) {
                $invoiceId =
                    $validatedInvoiceId;
            }
        }

        if ($invoiceId <= 0) {
            Flash::danger(
                'Invalid invoice.'
            );

            $this->redirect('/invoices');

            return;
        }

        $result =
            $this->invoiceNumberService
            ->issue(
                $invoiceId,
                (int) $currentUser['company_id'],
                (int) $currentUser['id']
            );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );

            $this->redirect(
                '/invoices/show?id=' .
                    $invoiceId
            );

            return;
        }

        if ($result['issued']) {
            Flash::success(
                'Invoice ' .
                    (string) $result['invoice_number'] .
                    ' issued successfully.'
            );
        } else {
            Flash::success(
                'This invoice is already issued as ' .
                    (string) $result['invoice_number'] .
                    '.'
            );
        }

        $this->redirect(
            '/invoices/show?id=' .
                $invoiceId
        );
    }

    public function updateSequence(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $rawNumber = '';

        if (
            isset($_POST['next_number']) &&
            is_scalar($_POST['next_number'])
        ) {
            $rawNumber = trim(
                (string) $_POST['next_number']
            );
        }

        if (
            preg_match(
                '/^\d{1,10}$/',
                $rawNumber
            ) !== 1
        ) {
            Flash::danger(
                'Starting invoice number must contain between 1 and 10 digits.'
            );

            $this->redirect('/invoices');

            return;
        }

        $nextNumber = (int) $rawNumber;

        if (
            $nextNumber < 1 ||
            $nextNumber > 9999999999
        ) {
            Flash::danger(
                'Starting invoice number must be between 1 and 9999999999.'
            );

            $this->redirect('/invoices');

            return;
        }

        $result =
            $this->invoiceNumberService
            ->configureStartingNumber(
                (int) $currentUser['company_id'],
                $nextNumber,
                (int) $currentUser['id']
            );

        if (!$result['success']) {
            Flash::danger(
                (string) $result['error']
            );

            $this->redirect('/invoices');

            return;
        }

        Flash::success(
            'Starting invoice number updated to ' .
                (string) $result['next_invoice_number'] .
                '.'
        );

        $this->redirect('/invoices');
    }

    public function printView(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $invoiceId =
            $this->invoiceIdFromQuery();

        if ($invoiceId <= 0) {
            $this->abort(404);

            return;
        }

        $document =
            $this->loadInvoiceDocument(
                $invoiceId,
                (int) $currentUser['company_id']
            );

        if ($document === null) {
            $this->abort(404);

            return;
        }

        try {
            $html =
                $this->renderDocumentHtml(
                    $document['invoice'],
                    $document['items'],
                    false
                );
        } catch (RuntimeException $exception) {
            http_response_code(500);

            echo htmlspecialchars(
                $exception->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            );

            return;
        }

        header(
            'Content-Type: text/html; charset=UTF-8'
        );

        echo $html;

        exit;
    }

    public function pdf(): void
    {
        $currentUser =
            $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $invoiceId =
            $this->invoiceIdFromQuery();

        if ($invoiceId <= 0) {
            $this->abort(404);

            return;
        }

        $document =
            $this->loadInvoiceDocument(
                $invoiceId,
                (int) $currentUser['company_id']
            );

        if ($document === null) {
            $this->abort(404);

            return;
        }

        $invoice = $document['invoice'];
        $items = $document['items'];

        try {
            $html =
                $this->renderDocumentHtml(
                    $invoice,
                    $items,
                    true
                );

            $pdf =
                $this->pdfService->generate(
                    $html,
                    'A4',
                    'portrait'
                );
        } catch (RuntimeException $exception) {
            Flash::danger(
                $exception->getMessage()
            );

            $this->redirect(
                '/invoices/show?id=' .
                    $invoiceId
            );

            return;
        }

        $filename =
            $this->invoicePdfFilename(
                $invoice
            );

        header('Content-Type: application/pdf');

        header(
            'Content-Disposition: attachment; filename="' .
                $filename .
                '"'
        );

        header(
            'Content-Length: ' .
                strlen($pdf)
        );

        header(
            'Cache-Control: private, max-age=0, must-revalidate'
        );

        header('Pragma: public');

        echo $pdf;

        exit;
    }

    private function invoiceIdFromQuery(): int
    {
        if (!isset($_GET['id'])) {
            return 0;
        }

        $validatedId = filter_var(
            $_GET['id'],
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

    private function loadInvoiceDocument(
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

        $items =
            $this->invoiceItemModel
            ->allByInvoice(
                $invoiceId,
                $companyId
            );

        return [
            'invoice' => $invoice,
            'items' => $items,
        ];
    }

    private function renderDocumentHtml(
        array $invoice,
        array $items,
        bool $forPdf
    ): string {
        $viewPath =
            dirname(__DIR__, 2) .
            '/resources/views/invoices/document.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException(
                'Invoice document template was not found.'
            );
        }

        ob_start();

        require $viewPath;

        $html = ob_get_clean();

        if ($html === false) {
            throw new RuntimeException(
                'Invoice document could not be rendered.'
            );
        }

        return $html;
    }

    private function invoicePdfFilename(
        array $invoice
    ): string {
        $reference =
            'draft-' . (int) $invoice['id'];

        if (
            isset($invoice['invoice_number']) &&
            trim(
                (string) $invoice['invoice_number']
            ) !== ''
        ) {
            $reference = trim(
                (string) $invoice['invoice_number']
            );
        }

        $reference = preg_replace(
            '/[^0-9A-Za-z_-]/',
            '-',
            $reference
        );

        if (
            !is_string($reference) ||
            $reference === ''
        ) {
            $reference =
                (string) $invoice['id'];
        }

        return 'invoice-' .
            $reference .
            '.pdf';
    }
}
