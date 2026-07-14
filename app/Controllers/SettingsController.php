<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Company;
use App\Models\Setting;
use App\Services\AuthService;
use App\Services\AuditLogService;

class SettingsController extends Controller
{
    private Setting $settingModel;
    private Company $companyModel;
    private AuthService $authService;
    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->companyModel = new Company();
        $this->authService = new AuthService();
        $this->auditLogService = new AuditLogService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $companyId = (int) $currentUser['company_id'];

        $settings = $this->settingModel->allByCompany($companyId);

        $company = $this->companyModel->findById($companyId);

        if ($company === null) {
            $this->abort(404);

            return;
        }

        $oldCompanyBilling = Session::get(
            'company_billing_old'
        );

        Session::remove('company_billing_old');

        if (is_array($oldCompanyBilling)) {
            $company = array_merge(
                $company,
                $oldCompanyBilling
            );
        }

        $this->view('settings/index', [
            'title' => 'Settings',
            'settings' => $this->withDefaults($settings),
            'currencies' => $this->currencies(),
            'dateFormats' => $this->dateFormats(),
            'errors' => [],
            'company' => $company,
        ]);
    }

    public function update(): void
    {
        $currentUser = $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $companyId = (int) $currentUser['company_id'];

        $company = $this->companyModel->findById($companyId);

        if ($company === null) {
            $this->abort(404);

            return;
        }

        $companyName = $this->input('company_name');
        $currency = $this->input('currency');
        $vatRate = $this->input('vat_rate');
        $invoicePrefix = $this->input('invoice_prefix');
        $dateFormat = $this->input('date_format');

        $errors = [];

        if ($companyName === '') {
            $errors[] = 'Company name is required.';
        }

        if ($currency === '') {
            $errors[] = 'Currency is required.';
        }

        if (!in_array($currency, $this->currencies(), true)) {
            $errors[] = 'Invalid currency.';
        }

        if ($vatRate === '') {
            $errors[] = 'VAT rate is required.';
        }

        if (!is_numeric($vatRate)) {
            $errors[] = 'VAT rate must be a number.';
        }

        if (is_numeric($vatRate) && (float) $vatRate < 0) {
            $errors[] = 'VAT rate cannot be negative.';
        }

        if ($invoicePrefix === '') {
            $errors[] = 'Invoice prefix is required.';
        }

        if ($dateFormat === '') {
            $errors[] = 'Date format is required.';
        }

        if (!array_key_exists($dateFormat, $this->dateFormats())) {
            $errors[] = 'Invalid date format.';
        }

        $settings = [
            'company_name' => $companyName,
            'currency' => $currency,
            'vat_rate' => $vatRate,
            'invoice_prefix' => $invoicePrefix,
            'date_format' => $dateFormat,
        ];

        if (!empty($errors)) {
            $this->view('settings/index', [
                'title' => 'Settings',
                'settings' => $settings,
                'currencies' => $this->currencies(),
                'dateFormats' => $this->dateFormats(),
                'errors' => $errors,
                'company' => $company,
            ]);

            return;
        }

        $this->settingModel->updateMany(
            $companyId,
            $settings
        );

        $this->auditLogService->log(
            $companyId,
            (int) $currentUser['id'],
            'update',
            'settings',
            null,
            'Updated company settings.'
        );

        Flash::success(
            'Settings updated successfully.'
        );

        $this->redirect('/settings');
    }

    public function updateCompanyBilling(): void
    {
        $currentUser = $this->authService->user();

        if ($currentUser === null) {
            $this->redirect('/login');

            return;
        }

        $companyId = (int) $currentUser['company_id'];

        $data = $this->getCompanyBillingData();

        $validator = new Validator($data);

        $validator
            ->required(
                'legal_name',
                'Legal company name is required.'
            )
            ->max(
                'legal_name',
                255,
                'Legal company name must be maximum 255 characters.'
            )
            ->required(
                'eik',
                'Company identification number is required.'
            )
            ->max(
                'eik',
                20,
                'Company identification number must be maximum 20 characters.'
            )
            ->max(
                'vat_number',
                30,
                'VAT number must be maximum 30 characters.'
            )
            ->max(
                'manager_name',
                255,
                'Manager name must be maximum 255 characters.'
            )
            ->required(
                'billing_address',
                'Billing address is required.'
            )
            ->max(
                'billing_address',
                255,
                'Billing address must be maximum 255 characters.'
            )
            ->required(
                'billing_city',
                'Billing city is required.'
            )
            ->max(
                'billing_city',
                100,
                'Billing city must be maximum 100 characters.'
            )
            ->max(
                'billing_postal_code',
                20,
                'Postal code must be maximum 20 characters.'
            )
            ->required(
                'billing_country',
                'Billing country is required.'
            )
            ->max(
                'billing_country',
                100,
                'Billing country must be maximum 100 characters.'
            )
            ->max(
                'billing_phone',
                50,
                'Billing phone must be maximum 50 characters.'
            )
            ->required(
                'billing_email',
                'Billing email is required.'
            )
            ->email(
                'billing_email',
                'Billing email must be valid.'
            )
            ->max(
                'billing_email',
                255,
                'Billing email must be maximum 255 characters.'
            )
            ->max(
                'billing_website',
                255,
                'Website must be maximum 255 characters.'
            )
            ->max(
                'bank_name',
                255,
                'Bank name must be maximum 255 characters.'
            )
            ->max(
                'iban',
                50,
                'IBAN must be maximum 50 characters.'
            )
            ->max(
                'bic',
                20,
                'BIC must be maximum 20 characters.'
            );

        if ($data['billing_website'] !== '') {
            $validWebsite = filter_var(
                $data['billing_website'],
                FILTER_VALIDATE_URL
            );

            if ($validWebsite === false) {
                $validator->add(
                    'billing_website',
                    'Website must be a valid URL including http:// or https://.'
                );
            }
        }

        if ($validator->fails()) {
            Session::set(
                'company_billing_old',
                $data
            );

            foreach ($validator->all() as $error) {
                Flash::danger($error);
            }

            $this->redirect(
                '/settings#company-billing'
            );

            return;
        }

        $updated = $this->companyModel
            ->updateBillingInformation(
                $companyId,
                $data
            );

        if (!$updated) {
            Flash::danger(
                'Unable to update company billing information.'
            );

            $this->redirect(
                '/settings#company-billing'
            );

            return;
        }

        $this->auditLogService->log(
            $companyId,
            (int) $currentUser['id'],
            'update',
            'company',
            $companyId,
            'Updated company billing information.'
        );

        Flash::success(
            'Company billing information updated successfully.'
        );

        $this->redirect(
            '/settings#company-billing'
        );
    }

    private function getCompanyBillingData(): array
    {
        $data = [
            'legal_name' => $this->input('legal_name'),
            'eik' => $this->input('eik'),
            'vat_number' => $this->input('vat_number'),
            'manager_name' => $this->input('manager_name'),

            'billing_address' =>
            $this->input('billing_address'),

            'billing_city' =>
            $this->input('billing_city'),

            'billing_postal_code' =>
            $this->input('billing_postal_code'),

            'billing_country' =>
            $this->input('billing_country'),

            'billing_phone' =>
            $this->input('billing_phone'),

            'billing_email' =>
            $this->input('billing_email'),

            'billing_website' =>
            $this->input('billing_website'),

            'bank_name' =>
            $this->input('bank_name'),

            'iban' =>
            $this->input('iban'),

            'bic' =>
            $this->input('bic'),
        ];

        $data['vat_number'] = strtoupper(
            str_replace(
                ' ',
                '',
                $data['vat_number']
            )
        );

        $data['iban'] = strtoupper(
            str_replace(
                ' ',
                '',
                $data['iban']
            )
        );

        $data['bic'] = strtoupper(
            str_replace(
                ' ',
                '',
                $data['bic']
            )
        );

        return $data;
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

    private function withDefaults(array $settings): array
    {
        if (!isset($settings['company_name'])) {
            $settings['company_name'] = '';
        }

        if (!isset($settings['currency'])) {
            $settings['currency'] = 'BGN';
        }

        if (!isset($settings['vat_rate'])) {
            $settings['vat_rate'] = '20';
        }

        if (!isset($settings['invoice_prefix'])) {
            $settings['invoice_prefix'] = 'INV';
        }

        if (!isset($settings['date_format'])) {
            $settings['date_format'] = 'Y-m-d';
        }

        return $settings;
    }

    private function currencies(): array
    {
        return [
            'BGN',
            'EUR',
            'USD',
        ];
    }

    private function dateFormats(): array
    {
        return [
            'Y-m-d' => '2026-06-28',
            'd.m.Y' => '28.06.2026',
            'm/d/Y' => '06/28/2026',
        ];
    }
}
