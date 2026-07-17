CREATE TABLE inventory_counts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,

    count_number VARCHAR(20) NULL,

    count_date DATE NOT NULL,

    snapshot_transaction_id
        BIGINT UNSIGNED NOT NULL DEFAULT 0,

    snapshot_at DATETIME NOT NULL,

    status VARCHAR(20)
        NOT NULL DEFAULT 'draft',

    active_warehouse_id BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN status = 'draft'
                    THEN warehouse_id
                ELSE NULL
            END
        ) STORED,

    notes TEXT NULL,

    created_by_user_id BIGINT UNSIGNED NOT NULL,

    completed_by_user_id BIGINT UNSIGNED NULL,
    completed_at DATETIME NULL,

    cancelled_by_user_id BIGINT UNSIGNED NULL,
    cancelled_at DATETIME NULL,
    cancellation_reason VARCHAR(500) NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_inventory_count_number (
        company_id,
        count_number
    ),

    UNIQUE KEY unique_open_inventory_count (
        company_id,
        active_warehouse_id
    ),

    INDEX index_inventory_counts_company_date (
        company_id,
        count_date
    ),

    INDEX index_inventory_counts_warehouse (
        company_id,
        warehouse_id,
        status
    ),

    CONSTRAINT fk_inventory_counts_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_inventory_counts_warehouse
        FOREIGN KEY (warehouse_id)
        REFERENCES warehouses(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_inventory_counts_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_inventory_counts_completed_by
        FOREIGN KEY (completed_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_inventory_counts_cancelled_by
        FOREIGN KEY (cancelled_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE inventory_count_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    inventory_count_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    product_name VARCHAR(255) NOT NULL,
    product_internal_code VARCHAR(100) NOT NULL,
    product_barcode VARCHAR(100) NULL,
    product_unit VARCHAR(30) NOT NULL,

    system_quantity DECIMAL(14,3) NOT NULL,

    counted_quantity DECIMAL(14,3) NULL,

    difference_quantity DECIMAL(14,3)
        GENERATED ALWAYS AS (
            CASE
                WHEN counted_quantity IS NULL
                    THEN NULL
                ELSE counted_quantity - system_quantity
            END
        ) STORED,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_inventory_count_product (
        inventory_count_id,
        product_id
    ),

    INDEX index_inventory_count_items_company (
        company_id,
        inventory_count_id
    ),

    INDEX index_inventory_count_items_product (
        company_id,
        product_id
    ),

    CONSTRAINT fk_inventory_count_items_count
        FOREIGN KEY (inventory_count_id)
        REFERENCES inventory_counts(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_inventory_count_items_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_inventory_count_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;