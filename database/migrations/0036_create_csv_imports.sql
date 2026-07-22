CREATE TABLE csv_import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,

    import_type VARCHAR(30) NOT NULL,
    import_mode VARCHAR(30) NOT NULL,

    original_filename VARCHAR(255) NOT NULL,

    delimiter_name VARCHAR(20) NULL,

    status VARCHAR(40)
        NOT NULL DEFAULT 'processing',

    validate_only TINYINT(1)
        NOT NULL DEFAULT 0,

    total_rows INT UNSIGNED
        NOT NULL DEFAULT 0,

    successful_rows INT UNSIGNED
        NOT NULL DEFAULT 0,

    failed_rows INT UNSIGNED
        NOT NULL DEFAULT 0,

    error_message VARCHAR(1000) NULL,

    created_by_user_id
        BIGINT UNSIGNED NOT NULL,

    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX index_csv_import_batches_company (
        company_id,
        created_at
    ),

    INDEX index_csv_import_batches_status (
        company_id,
        status
    ),

    INDEX index_csv_import_batches_type (
        company_id,
        import_type
    ),

    CONSTRAINT fk_csv_import_batches_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_csv_import_batches_user
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE csv_import_errors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    batch_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,

    `row_number` INT UNSIGNED NOT NULL,

    column_name VARCHAR(100) NULL,

    error_message VARCHAR(1000) NOT NULL,

    row_data LONGTEXT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    INDEX index_csv_import_errors_batch (
        batch_id,
        `row_number`
    ),

    INDEX index_csv_import_errors_company (
        company_id,
        batch_id
    ),

    CONSTRAINT fk_csv_import_errors_batch
        FOREIGN KEY (batch_id)
        REFERENCES csv_import_batches(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_csv_import_errors_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;