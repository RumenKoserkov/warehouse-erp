ALTER TABLE invoices
    DROP INDEX unique_company_active_sale_invoice,
    DROP INDEX unique_company_invoice_number;


ALTER TABLE invoices
    DROP COLUMN active_sale_id;


ALTER TABLE invoices
    ADD COLUMN active_sale_id BIGINT UNSIGNED NULL
        AFTER related_invoice_id;


UPDATE invoices
SET active_sale_id =
    CASE
        WHEN sale_id IS NOT NULL
            AND status <> 'cancelled'
        THEN sale_id
        ELSE NULL
    END;


ALTER TABLE invoices
    ADD UNIQUE KEY unique_company_active_sale_invoice (
        company_id,
        active_sale_id
    ),

    ADD UNIQUE KEY unique_company_document_number (
        company_id,
        document_type,
        invoice_number
    );