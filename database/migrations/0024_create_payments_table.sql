CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NOT NULL,

    received_by_user_id BIGINT UNSIGNED NULL,

    payment_date DATE NOT NULL,

    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL,

    payment_method VARCHAR(30)
        NOT NULL DEFAULT 'bank_transfer',

    external_reference VARCHAR(100) NULL,
    note TEXT NULL,

    status VARCHAR(20)
        NOT NULL DEFAULT 'completed',

    cancelled_at TIMESTAMP NULL DEFAULT NULL,

    cancelled_by_user_id BIGINT UNSIGNED NULL,

    cancellation_reason VARCHAR(500) NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX index_payments_company_date (
        company_id,
        payment_date
    ),

    INDEX index_payments_invoice_status (
        invoice_id,
        status
    ),

    INDEX index_payments_received_by_user (
        received_by_user_id
    ),

    INDEX index_payments_cancelled_by_user (
        cancelled_by_user_id
    ),

    CONSTRAINT fk_payments_company_id
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_payments_invoice_id
        FOREIGN KEY (invoice_id)
        REFERENCES invoices(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_payments_received_by_user_id
        FOREIGN KEY (received_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_payments_cancelled_by_user_id
        FOREIGN KEY (cancelled_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;