<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Setting;
use App\Services\AuthService;
use App\Services\AuditLogService;

class SettingsController extends Controller
{
    private Setting $settingModel;
    private AuthService $authService;
    private AuditLogService $auditLogService;

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->authService = new AuthService();
        $this->auditLogService = new AuditLogService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        $companyId = (int)$currentUser['company_id'];

        $settings = $this->settingModel->allByCompany($companyId);

        $this->view('settings/index', [
            'title' => 'Settings',
            'settings' => $this->withDefaults($settings),
            'currencies' => $this->currencies(),
            'dateFormats' => $this->dateFormats(),
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $currentUser = $this->authService->user();

        $companyId = (int)$currentUser['company_id'];

        $companyName = '';
        $currency = '';
        $vatRate = '';
        $invoicePrefix = '';
        $dateFormat = '';

        if (isset($_POST['company_name'])) {
            $companyName = trim((string)$_POST['company_name']);
        }

        if (isset($_POST['currency'])) {
            $currency = trim((string)$_POST['currency']);
        }

        if (isset($_POST['vat_rate'])) {
            $vatRate = trim((string)$_POST['vat_rate']);
        }

        if (isset($_POST['invoice_prefix'])) {
            $invoicePrefix = trim((string)$_POST['invoice_prefix']);
        }

        if (isset($_POST['date_format'])) {
            $dateFormat = trim((string)$_POST['date_format']);
        }

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

        if (is_numeric($vatRate) && (float)$vatRate < 0) {
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
            ]);

            return;
        }

        $this->settingModel->updateMany($companyId, $settings);

        $this->auditLogService->log(
            $companyId,
            (int)$currentUser['id'],
            'update',
            'settings',
            null,
            'Updated company settings.'
        );

        Flash::success('Settings updated successfully.');

        $this->redirect('/settings');
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