CREATE TABLE promotions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,
    code VARCHAR(100) NULL,

    discount_type VARCHAR(30) NOT NULL,
    discount_value DECIMAL(14,4) NOT NULL,

    maximum_discount_amount DECIMAL(14,2) NULL,

    minimum_order_amount DECIMAL(14,2)
        NOT NULL DEFAULT 0.00,

    starts_on DATE NOT NULL,
    ends_on DATE NULL,

    max_uses INT UNSIGNED NULL,

    used_count INT UNSIGNED
        NOT NULL DEFAULT 0,

    is_active TINYINT(1)
        NOT NULL DEFAULT 1,

    notes TEXT NULL,

    created_by_user_id BIGINT UNSIGNED NOT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_promotion_code (
        company_id,
        code
    ),

    INDEX index_promotions_company_active (
        company_id,
        is_active,
        starts_on,
        ends_on
    ),

    INDEX index_promotions_usage (
        company_id,
        used_count,
        max_uses
    ),

    CONSTRAINT fk_promotions_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_promotions_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


ALTER TABLE sales
    ADD COLUMN promotion_id BIGINT UNSIGNED NULL
        AFTER client_id,

    ADD COLUMN promotion_name VARCHAR(255) NULL
        AFTER promotion_id,

    ADD COLUMN promotion_code VARCHAR(100) NULL
        AFTER promotion_name,

    ADD COLUMN promotion_discount_amount DECIMAL(14,2)
        NOT NULL DEFAULT 0.00
        AFTER discount_amount,

    ADD INDEX index_sales_promotion (
        company_id,
        promotion_id
    ),

    ADD CONSTRAINT fk_sales_promotion
        FOREIGN KEY (promotion_id)
        REFERENCES promotions(id)
        ON DELETE SET NULL;


ALTER TABLE sale_items
    ADD COLUMN promotion_discount_amount DECIMAL(14,2)
        NOT NULL DEFAULT 0.00
        AFTER discount_amount;


CREATE TABLE promotion_usages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,
    promotion_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,

    promotion_name VARCHAR(255) NOT NULL,
    promotion_code VARCHAR(100) NULL,

    discount_amount DECIMAL(14,2) NOT NULL,

    status VARCHAR(20)
        NOT NULL DEFAULT 'used',

    cancelled_at DATETIME NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_promotion_usage_sale (
        company_id,
        sale_id
    ),

    INDEX index_promotion_usages_promotion (
        company_id,
        promotion_id,
        status
    ),

    CONSTRAINT fk_promotion_usages_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_promotion_usages_promotion
        FOREIGN KEY (promotion_id)
        REFERENCES promotions(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_promotion_usages_sale
        FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;