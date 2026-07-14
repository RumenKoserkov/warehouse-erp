<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Client;
use App\Services\AuthService;

class ClientController extends Controller
{
    private Client $clientModel;
    private AuthService $authService;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->authService = new AuthService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        $search = trim((string) ($_GET['search'] ?? ''));

        $clients = $this->clientModel->allByCompany(
            (int) $currentUser['company_id'],
            $search
        );

        $this->view('clients/index', [
            'title' => 'Clients',
            'clients' => $clients,
            'search' => $search,
        ]);
    }

    public function create(): void
    {
        $this->view('clients/create', [
            'title' => 'Create Client',
            'errors' => [],
            'old' => $this->emptyOldData(),
        ]);
    }

    public function store(): void
    {
        $currentUser = $this->authService->user();

        $data = $this->getFormData();

        $companyId = (int) $currentUser['company_id'];

        $errors = $this->validateClientData(
            $data,
            $companyId
        );

        if (!empty($errors)) {
            $this->view('clients/create', [
                'title' => 'Create Client',
                'errors' => $errors,
                'old' => $data,
            ]);

            return;
        }

        $data['company_id'] = $companyId;

        $this->clientModel->create($data);

        Flash::success('Client created successfully.');

        $this->redirect('/clients');
    }

    public function edit(): void
    {
        $currentUser = $this->authService->user();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404);
        }

        $client = $this->clientModel->findByIdAndCompany(
            $id,
            (int) $currentUser['company_id']
        );

        if ($client === null) {
            $this->abort(404);
        }

        $this->view('clients/edit', [
            'title' => 'Edit Client',
            'client' => $client,
            'errors' => [],
            'old' => $client,
        ]);
    }

    public function update(): void
    {
        $currentUser = $this->authService->user();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404);
        }

        $companyId = (int) $currentUser['company_id'];

        $client = $this->clientModel->findByIdAndCompany(
            $id,
            $companyId
        );

        if ($client === null) {
            $this->abort(404);
        }

        $data = $this->getFormData();

        $errors = $this->validateClientData(
            $data,
            $companyId,
            $id
        );

        if (!empty($errors)) {
            $this->view('clients/edit', [
                'title' => 'Edit Client',
                'client' => $client,
                'errors' => $errors,
                'old' => $data,
            ]);

            return;
        }

        $data['company_id'] = $companyId;

        $this->clientModel->update($id, $data);

        Flash::success('Client updated successfully.');

        $this->redirect('/clients');
    }

    public function deactivate(): void
    {
        $currentUser = $this->authService->user();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404);
        }

        $companyId = (int) $currentUser['company_id'];

        $client = $this->clientModel->findByIdAndCompany(
            $id,
            $companyId
        );

        if ($client === null) {
            $this->abort(404);
        }

        $this->clientModel->deactivate(
            $id,
            $companyId
        );

        Flash::success('Client deactivated successfully.');

        $this->redirect('/clients');
    }

    private function getFormData(): array
    {
        $clientType = $this->input('client_type');

        if (
            $clientType !== 'company' &&
            $clientType !== 'individual'
        ) {
            $clientType = 'company';
        }

        $eik = strtoupper(
            str_replace(
                ' ',
                '',
                $this->input('eik')
            )
        );

        $vatNumber = strtoupper(
            str_replace(
                ' ',
                '',
                $this->input('vat_number')
            )
        );

        $billingCountry = $this->input(
            'billing_country'
        );

        if ($billingCountry === '') {
            $billingCountry = 'Bulgaria';
        }

        return [
            'name' => $this->input('name'),
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),

            'client_type' => $clientType,
            'company_name' =>
                $this->input('company_name'),
            'eik' => $eik,
            'vat_number' => $vatNumber,
            'contact_person' =>
                $this->input('contact_person'),

            'billing_address' =>
                $this->input('billing_address'),
            'billing_city' =>
                $this->input('billing_city'),
            'billing_postal_code' =>
                $this->input('billing_postal_code'),
            'billing_country' => $billingCountry,
            'billing_email' =>
                $this->input('billing_email'),

            'is_active' =>
                isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function validateClientData(
        array $data,
        int $companyId,
        int $clientId = 0
    ): array {
        $validator = new Validator($data);

        $validator
            ->required(
                'name',
                'Client name is required.'
            )
            ->max(
                'name',
                255,
                'Client name must be maximum 255 characters.'
            )
            ->max(
                'phone',
                50,
                'Phone must be maximum 50 characters.'
            )
            ->email(
                'email',
                'Email must be a valid email address.'
            )
            ->max(
                'email',
                255,
                'Email must be maximum 255 characters.'
            )
            ->in(
                'client_type',
                [
                    'company',
                    'individual',
                ],
                'Invalid client type.'
            )
            ->max(
                'company_name',
                255,
                'Company name must be maximum 255 characters.'
            )
            ->max(
                'eik',
                50,
                'EIK must be maximum 50 characters.'
            )
            ->max(
                'vat_number',
                30,
                'VAT number must be maximum 30 characters.'
            )
            ->max(
                'contact_person',
                255,
                'Contact person must be maximum 255 characters.'
            )
            ->max(
                'billing_address',
                255,
                'Billing address must be maximum 255 characters.'
            )
            ->max(
                'billing_city',
                100,
                'Billing city must be maximum 100 characters.'
            )
            ->max(
                'billing_postal_code',
                20,
                'Billing postal code must be maximum 20 characters.'
            )
            ->max(
                'billing_country',
                100,
                'Billing country must be maximum 100 characters.'
            )
            ->email(
                'billing_email',
                'Billing email must be a valid email address.'
            )
            ->max(
                'billing_email',
                255,
                'Billing email must be maximum 255 characters.'
            );

        if ($data['client_type'] === 'company') {
            if ($data['company_name'] === '') {
                $validator->add(
                    'company_name',
                    'Company name is required for a company client.'
                );
            }

            if ($data['eik'] === '') {
                $validator->add(
                    'eik',
                    'EIK is required for a company client.'
                );
            }
        }

        if ($data['vat_number'] !== '') {
            $validVatNumber = preg_match(
                '/^[A-Z]{2}[A-Z0-9]{2,20}$/',
                $data['vat_number']
            );

            if ($validVatNumber !== 1) {
                $validator->add(
                    'vat_number',
                    'VAT number must start with a country code, for example BG123456789.'
                );
            }
        }

        if (
            $data['eik'] !== '' &&
            $this->clientModel->eikExistsInCompany(
                $data['eik'],
                $companyId,
                $clientId
            )
        ) {
            $validator->add(
                'eik',
                'A client with this EIK already exists.'
            );
        }

        if (
            $data['vat_number'] !== '' &&
            $this->clientModel->vatNumberExistsInCompany(
                $data['vat_number'],
                $companyId,
                $clientId
            )
        ) {
            $validator->add(
                'vat_number',
                'A client with this VAT number already exists.'
            );
        }

        return $validator->all();
    }

    private function emptyOldData(): array
    {
        return [
            'name' => '',
            'phone' => '',
            'email' => '',
            'address' => '',

            'client_type' => 'company',
            'company_name' => '',
            'eik' => '',
            'vat_number' => '',
            'contact_person' => '',

            'billing_address' => '',
            'billing_city' => '',
            'billing_postal_code' => '',
            'billing_country' => 'Bulgaria',
            'billing_email' => '',

            'is_active' => '1',
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
}