ALTER TABLE clients
    ADD COLUMN client_type VARCHAR(20) NOT NULL DEFAULT 'company',
    ADD COLUMN vat_number VARCHAR(30) NULL,
    ADD COLUMN billing_address VARCHAR(255) NULL,
    ADD COLUMN billing_city VARCHAR(100) NULL,
    ADD COLUMN billing_postal_code VARCHAR(20) NULL,
    ADD COLUMN billing_country VARCHAR(100) NOT NULL DEFAULT 'Bulgaria',
    ADD COLUMN billing_email VARCHAR(255) NULL;

UPDATE clients
SET billing_address = address
WHERE billing_address IS NULL
AND address IS NOT NULL
AND address != '';

UPDATE clients
SET billing_email = email
WHERE billing_email IS NULL
AND email IS NOT NULL
AND email != '';