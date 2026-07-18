CREATE TABLE inventory_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,

    adjustment_number VARCHAR(20) NULL,

    adjustment_date DATE NOT NULL,

    reason_type VARCHAR(30) NOT NULL,
    reason_description VARCHAR(500) NOT NULL,

    status VARCHAR(20)
        NOT NULL DEFAULT 'draft',

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

    UNIQUE KEY unique_inventory_adjustment_number (
        company_id,
        adjustment_number
    ),

    INDEX index_inventory_adjustments_company_date (
        company_id,
        adjustment_date
    ),

    INDEX index_inventory_adjustments_warehouse_status (
        company_id,
        warehouse_id,
        status
    ),

    INDEX index_inventory_adjustments_reason (
        company_id,
        reason_type,
        adjustment_date
    ),

    CONSTRAINT fk_inventory_adjustments_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_inventory_adjustments_warehouse
        FOREIGN KEY (warehouse_id)
        REFERENCES warehouses(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_inventory_adjustments_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_inventory_adjustments_completed_by
        FOREIGN KEY (completed_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_inventory_adjustments_cancelled_by
        FOREIGN KEY (cancelled_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE inventory_adjustment_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    inventory_adjustment_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    product_name VARCHAR(255) NOT NULL,
    product_internal_code VARCHAR(100) NOT NULL,
    product_barcode VARCHAR(100) NULL,
    product_unit VARCHAR(30) NOT NULL,

    direction VARCHAR(10) NOT NULL,

    quantity DECIMAL(14,3) NOT NULL,

    signed_quantity DECIMAL(14,3)
        GENERATED ALWAYS AS (
            CASE
                WHEN direction = 'increase'
                    THEN quantity
                WHEN direction = 'decrease'
                    THEN -quantity
                ELSE 0
            END
        ) STORED,

    stock_quantity_at_add DECIMAL(14,3) NOT NULL,

    quantity_before DECIMAL(14,3) NULL,
    quantity_after DECIMAL(14,3) NULL,

    item_note VARCHAR(500) NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_adjustment_product (
        inventory_adjustment_id,
        product_id
    ),

    INDEX index_adjustment_items_company (
        company_id,
        inventory_adjustment_id
    ),

    INDEX index_adjustment_items_product (
        company_id,
        product_id
    ),

    CONSTRAINT fk_adjustment_items_adjustment
        FOREIGN KEY (inventory_adjustment_id)
        REFERENCES inventory_adjustments(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_adjustment_items_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_adjustment_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;