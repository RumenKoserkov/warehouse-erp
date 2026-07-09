CREATE TABLE purchase_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    purchase_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    product_name VARCHAR(255) NOT NULL,
    product_internal_code VARCHAR(50) NOT NULL,

    quantity DECIMAL(12,3) NOT NULL,
    unit VARCHAR(30) NOT NULL,

    unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_purchase_items_purchase_id
        FOREIGN KEY (purchase_id)
        REFERENCES purchases(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_purchase_items_company_id
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_purchase_items_product_id
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;