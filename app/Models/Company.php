<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Company extends Model
{
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM companies
            WHERE id = :id
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
        ]);

        $company = $statement->fetch();

        if ($company === false) {
            return null;
        }

        return $company;
    }

    public function updateBillingInformation(
        int $id,
        array $data
    ): bool {
        $sql = "
            UPDATE companies
            SET
                legal_name = :legal_name,
                eik = :eik,
                vat_number = :vat_number,
                manager_name = :manager_name,

                billing_address = :billing_address,
                billing_city = :billing_city,
                billing_postal_code = :billing_postal_code,
                billing_country = :billing_country,

                billing_phone = :billing_phone,
                billing_email = :billing_email,
                billing_website = :billing_website,

                bank_name = :bank_name,
                iban = :iban,
                bic = :bic,

                updated_at = NOW()
            WHERE id = :id
        ";

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'legal_name' => $data['legal_name'],
            'eik' => $data['eik'],
            'vat_number' => $data['vat_number'],
            'manager_name' => $data['manager_name'],

            'billing_address' => $data['billing_address'],
            'billing_city' => $data['billing_city'],
            'billing_postal_code' => $data['billing_postal_code'],
            'billing_country' => $data['billing_country'],

            'billing_phone' => $data['billing_phone'],
            'billing_email' => $data['billing_email'],
            'billing_website' => $data['billing_website'],

            'bank_name' => $data['bank_name'],
            'iban' => $data['iban'],
            'bic' => $data['bic'],

            'id' => $id,
        ]);
    }
}