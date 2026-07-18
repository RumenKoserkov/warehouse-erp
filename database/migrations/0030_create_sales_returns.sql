CREATE TABLE sales_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,

    return_number VARCHAR(20) NULL,

    return_date DATE NOT NULL,

    reason_type VARCHAR(30) NOT NULL,
    reason_description VARCHAR(500) NOT NULL,

    status VARCHAR(20)
        NOT NULL DEFAULT 'draft',

    active_sale_id BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN status = 'draft'
                    THEN sale_id
                ELSE NULL
            END
        ) STORED,

    subtotal_amount DECIMAL(14,2)
        NOT NULL DEFAULT 0.00,

    discount_amount DECIMAL(14,2)
        NOT NULL DEFAULT 0.00,

    net_amount DECIMAL(14,2)
        NOT NULL DEFAULT 0.00,

    tax_amount DECIMAL(14,2)
        NOT NULL DEFAULT 0.00,

    total_amount DECIMAL(14,2)
        NOT NULL DEFAULT 0.00,

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

    UNIQUE KEY unique_sales_return_number (
        company_id,
        return_number
    ),

    UNIQUE KEY unique_open_return_per_sale (
        company_id,
        active_sale_id
    ),

    INDEX index_sales_returns_company_date (
        company_id,
        return_date
    ),

    INDEX index_sales_returns_sale (
        company_id,
        sale_id,
        status
    ),

    INDEX index_sales_returns_warehouse (
        company_id,
        warehouse_id,
        status
    ),

    CONSTRAINT fk_sales_returns_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_sales_returns_sale
        FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_sales_returns_warehouse
        FOREIGN KEY (warehouse_id)
        REFERENCES warehouses(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_sales_returns_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_sales_returns_completed_by
        FOREIGN KEY (completed_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_sales_returns_cancelled_by
        FOREIGN KEY (cancelled_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE sales_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    sales_return_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,

    sale_item_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    product_name VARCHAR(255) NOT NULL,
    product_internal_code VARCHAR(100) NOT NULL,
    product_unit VARCHAR(30) NOT NULL,

    sold_quantity DECIMAL(14,3) NOT NULL,

    return_quantity DECIMAL(14,3) NOT NULL,

    restock_quantity DECIMAL(14,3)
        NOT NULL DEFAULT 0.000,

    non_restock_quantity DECIMAL(14,3)
        GENERATED ALWAYS AS (
            return_quantity -
            restock_quantity
        ) STORED,

    unit_price DECIMAL(14,4) NOT NULL,

    subtotal_amount DECIMAL(14,2) NOT NULL,
    discount_amount DECIMAL(14,2) NOT NULL,
    net_amount DECIMAL(14,2) NOT NULL,

    vat_rate DECIMAL(5,2) NOT NULL,
    tax_amount DECIMAL(14,2) NOT NULL,
    total_amount DECIMAL(14,2) NOT NULL,

    stock_quantity_before DECIMAL(14,3) NULL,
    stock_quantity_after DECIMAL(14,3) NULL,

    item_note VARCHAR(500) NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_sales_return_sale_item (
        sales_return_id,
        sale_item_id
    ),

    INDEX index_sales_return_items_company (
        company_id,
        sales_return_id
    ),

    INDEX index_sales_return_items_sale_item (
        company_id,
        sale_item_id
    ),

    INDEX index_sales_return_items_product (
        company_id,
        product_id
    ),

    CONSTRAINT fk_sales_return_items_return
        FOREIGN KEY (sales_return_id)
        REFERENCES sales_returns(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_sales_return_items_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_sales_return_items_sale_item
        FOREIGN KEY (sale_item_id)
        REFERENCES sale_items(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_sales_return_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;