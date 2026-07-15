CREATE TABLE document_sequences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(30) NOT NULL,

    next_number BIGINT UNSIGNED NOT NULL DEFAULT 1,
    last_issued_number BIGINT UNSIGNED NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_company_document_sequence (
        company_id,
        document_type
    ),

    CONSTRAINT fk_document_sequences_company_id
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


INSERT INTO document_sequences
(
    company_id,
    document_type,
    next_number,
    last_issued_number
)
SELECT
    companies.id,
    'invoice',
    1,
    NULL
FROM companies;


ALTER TABLE invoices
    ADD COLUMN issued_by_user_id BIGINT UNSIGNED NULL
        AFTER created_by_user_id,

    ADD INDEX index_invoices_issued_by_user_id (
        issued_by_user_id
    ),

    ADD CONSTRAINT fk_invoices_issued_by_user_id
        FOREIGN KEY (issued_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL;