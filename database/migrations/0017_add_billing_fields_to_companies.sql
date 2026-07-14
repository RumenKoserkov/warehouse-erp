ALTER TABLE companies
    ADD COLUMN legal_name VARCHAR(255) NULL AFTER name,
    ADD COLUMN manager_name VARCHAR(255) NULL AFTER vat_number,

    ADD COLUMN billing_address VARCHAR(255) NULL AFTER manager_name,
    ADD COLUMN billing_city VARCHAR(100) NULL AFTER billing_address,
    ADD COLUMN billing_postal_code VARCHAR(20) NULL AFTER billing_city,
    ADD COLUMN billing_country VARCHAR(100) NOT NULL DEFAULT 'Bulgaria'
        AFTER billing_postal_code,

    ADD COLUMN billing_phone VARCHAR(50) NULL AFTER billing_country,
    ADD COLUMN billing_email VARCHAR(255) NULL AFTER billing_phone,
    ADD COLUMN billing_website VARCHAR(255) NULL AFTER billing_email,

    ADD COLUMN bank_name VARCHAR(255) NULL AFTER billing_website,
    ADD COLUMN iban VARCHAR(50) NULL AFTER bank_name,
    ADD COLUMN bic VARCHAR(20) NULL AFTER iban;