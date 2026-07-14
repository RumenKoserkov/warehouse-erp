<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use InvalidArgumentException;

final class TaxService
{
    private Setting $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    public function salesConfiguration(
        int $companyId
    ): array {
        return $this->configuration(
            $companyId,
            'sales_prices_include_vat'
        );
    }

    public function purchaseConfiguration(
        int $companyId
    ): array {
        return $this->configuration(
            $companyId,
            'purchase_prices_include_vat'
        );
    }

    public function calculateLine(
        float $quantity,
        float $unitPrice,
        float $discountAmount,
        array $configuration
    ): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                'Quantity must be greater than zero.'
            );
        }

        if ($unitPrice < 0) {
            throw new InvalidArgumentException(
                'Unit price cannot be negative.'
            );
        }

        if ($discountAmount < 0) {
            throw new InvalidArgumentException(
                'Discount cannot be negative.'
            );
        }

        $lineAmount = $this->money(
            $quantity * $unitPrice
        );

        if ($discountAmount > $lineAmount) {
            throw new InvalidArgumentException(
                'Discount cannot exceed the line amount.'
            );
        }

        $vatRegistered =
            $configuration['vat_registered'] === true;

        $pricesIncludeVat =
            $configuration['prices_include_vat'] === true;

        $vatRate = (float) $configuration['vat_rate'];

        if (!$vatRegistered) {
            $vatRate = 0.00;
        }

        if ($vatRate < 0 || $vatRate > 100) {
            $vatRate = 0.00;
        }

        if ($pricesIncludeVat && $vatRate > 0) {
            return $this->calculateIncludedVat(
                $lineAmount,
                $discountAmount,
                $vatRate
            );
        }

        return $this->calculateExcludedVat(
            $lineAmount,
            $discountAmount,
            $vatRate
        );
    }

    private function configuration(
        int $companyId,
        string $priceSettingKey
    ): array {
        $vatRegistered = $this->settingModel->get(
            $companyId,
            'vat_registered',
            '0'
        ) === '1';

        $pricesIncludeVat = $this->settingModel->get(
            $companyId,
            $priceSettingKey,
            '0'
        ) === '1';

        $vatRate = (float) $this->settingModel->get(
            $companyId,
            'vat_rate',
            '0'
        );

        if ($vatRate < 0 || $vatRate > 100) {
            $vatRate = 0.00;
        }

        if (!$vatRegistered) {
            $vatRate = 0.00;
        }

        return [
            'vat_registered' => $vatRegistered,
            'prices_include_vat' => $pricesIncludeVat,
            'vat_rate' => $vatRate,
        ];
    }

    private function calculateExcludedVat(
        float $lineAmount,
        float $discountAmount,
        float $vatRate
    ): array {
        $netSubtotal = $this->money($lineAmount);

        $netDiscount = $this->money(
            $discountAmount
        );

        $netAmount = $this->money(
            $netSubtotal - $netDiscount
        );

        $taxAmount = $this->money(
            $netAmount * ($vatRate / 100)
        );

        $totalAmount = $this->money(
            $netAmount + $taxAmount
        );

        return [
            'subtotal' => $netSubtotal,
            'discount_amount' => $netDiscount,
            'net_amount' => $netAmount,
            'vat_rate' => $vatRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }

    private function calculateIncludedVat(
        float $lineAmount,
        float $discountAmount,
        float $vatRate
    ): array {
        $grossAmount = $this->money(
            $lineAmount - $discountAmount
        );

        $divisor = 1 + ($vatRate / 100);

        $netAmount = $this->money(
            $grossAmount / $divisor
        );

        $taxAmount = $this->money(
            $grossAmount - $netAmount
        );

        $netDiscount = $this->money(
            $discountAmount / $divisor
        );

        $netSubtotal = $this->money(
            $netAmount + $netDiscount
        );

        return [
            'subtotal' => $netSubtotal,
            'discount_amount' => $netDiscount,
            'net_amount' => $netAmount,
            'vat_rate' => $vatRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $grossAmount,
        ];
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }
}