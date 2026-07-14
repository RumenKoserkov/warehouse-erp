ALTER TABLE sales
    ADD COLUMN vat_registered TINYINT(1) NOT NULL DEFAULT 0
        AFTER status,
    ADD COLUMN prices_include_vat TINYINT(1) NOT NULL DEFAULT 0
        AFTER vat_registered,
    ADD COLUMN default_vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00
        AFTER prices_include_vat;

ALTER TABLE purchases
    ADD COLUMN vat_registered TINYINT(1) NOT NULL DEFAULT 0
        AFTER status,
    ADD COLUMN prices_include_vat TINYINT(1) NOT NULL DEFAULT 0
        AFTER vat_registered,
    ADD COLUMN default_vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00
        AFTER prices_include_vat;

ALTER TABLE sale_items
    ADD COLUMN vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00
        AFTER discount_amount,
    ADD COLUMN net_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00
        AFTER vat_rate,
    ADD COLUMN tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00
        AFTER net_amount;

ALTER TABLE purchase_items
    ADD COLUMN vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00
        AFTER discount_amount,
    ADD COLUMN net_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00
        AFTER vat_rate,
    ADD COLUMN tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00
        AFTER net_amount;

UPDATE sale_items
SET net_amount = total_price
WHERE net_amount = 0.00
AND total_price != 0.00;

UPDATE purchase_items
SET net_amount = total_price
WHERE net_amount = 0.00
AND total_price != 0.00;