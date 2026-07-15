ALTER TABLE invoices
    DROP INDEX unique_company_sale_invoice;


ALTER TABLE invoices
    ADD COLUMN related_invoice_id BIGINT UNSIGNED NULL
        AFTER sale_id,

    ADD COLUMN active_sale_id BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN sale_id IS NOT NULL
                    AND status <> 'cancelled'
                THEN sale_id
                ELSE NULL
            END
        ) STORED
        AFTER related_invoice_id,

    ADD COLUMN correction_reason VARCHAR(500) NULL
        AFTER note,

    ADD COLUMN cancelled_at TIMESTAMP NULL DEFAULT NULL
        AFTER issued_at,

    ADD COLUMN cancelled_by_user_id BIGINT UNSIGNED NULL
        AFTER cancelled_at,

    ADD COLUMN cancellation_reason VARCHAR(500) NULL
        AFTER cancelled_by_user_id,

    ADD INDEX index_invoices_related_invoice (
        company_id,
        related_invoice_id
    ),

    ADD INDEX index_invoices_cancelled_by_user (
        cancelled_by_user_id
    ),

    ADD UNIQUE KEY unique_company_active_sale_invoice (
        company_id,
        active_sale_id
    ),

    ADD CONSTRAINT fk_invoices_related_invoice_id
        FOREIGN KEY (related_invoice_id)
        REFERENCES invoices(id)
        ON DELETE RESTRICT,

    ADD CONSTRAINT fk_invoices_cancelled_by_user_id
        FOREIGN KEY (cancelled_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL;


ALTER TABLE invoice_items
    ADD COLUMN source_invoice_item_id BIGINT UNSIGNED NULL
        AFTER product_id,

    ADD INDEX index_invoice_items_source_item (
        source_invoice_item_id
    ),

    ADD CONSTRAINT fk_invoice_items_source_item_id
        FOREIGN KEY (source_invoice_item_id)
        REFERENCES invoice_items(id)
        ON DELETE RESTRICT;


INSERT IGNORE INTO document_sequences
(
    company_id,
    document_type,
    next_number,
    last_issued_number
)
SELECT
    companies.id,
    'credit_note',
    1,
    NULL
FROM companies;