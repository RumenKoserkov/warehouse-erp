CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NULL,

    document_type VARCHAR(30) NOT NULL DEFAULT 'invoice',
    invoice_number VARCHAR(50) NULL,

    invoice_date DATE NOT NULL,
    supply_date DATE NOT NULL,
    due_date DATE NULL,

    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    currency VARCHAR(10) NOT NULL DEFAULT 'EUR',

    vat_registered TINYINT(1) NOT NULL DEFAULT 0,
    prices_include_vat TINYINT(1) NOT NULL DEFAULT 0,
    default_vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    supplier_legal_name VARCHAR(255) NOT NULL,
    supplier_eik VARCHAR(30) NOT NULL,
    supplier_vat_number VARCHAR(30) NULL,
    supplier_manager_name VARCHAR(255) NULL,

    supplier_address VARCHAR(255) NOT NULL,
    supplier_city VARCHAR(100) NOT NULL,
    supplier_postal_code VARCHAR(20) NULL,
    supplier_country VARCHAR(100) NOT NULL,

    supplier_phone VARCHAR(50) NULL,
    supplier_email VARCHAR(255) NULL,

    supplier_bank_name VARCHAR(255) NULL,
    supplier_iban VARCHAR(50) NULL,
    supplier_bic VARCHAR(20) NULL,

    client_type VARCHAR(20) NOT NULL DEFAULT 'company',
    client_display_name VARCHAR(255) NOT NULL,
    client_legal_name VARCHAR(255) NOT NULL,
    client_eik VARCHAR(30) NULL,
    client_vat_number VARCHAR(30) NULL,

    client_address VARCHAR(255) NOT NULL,
    client_city VARCHAR(100) NOT NULL,
    client_postal_code VARCHAR(20) NULL,
    client_country VARCHAR(100) NOT NULL,
    client_email VARCHAR(255) NULL,

    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    note TEXT NULL,

    issued_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_company_invoice_number (
        company_id,
        invoice_number
    ),

    UNIQUE KEY unique_company_sale_invoice (
        company_id,
        sale_id
    ),

    INDEX index_invoices_company_status (
        company_id,
        status
    ),

    INDEX index_invoices_company_date (
        company_id,
        invoice_date
    ),

    CONSTRAINT fk_invoices_company_id
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_invoices_client_id
        FOREIGN KEY (client_id)
        REFERENCES clients(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_invoices_sale_id
        FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_invoices_created_by_user_id
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    invoice_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,

    description VARCHAR(255) NOT NULL,
    product_internal_code VARCHAR(50) NULL,

    quantity DECIMAL(12,3) NOT NULL,
    unit VARCHAR(30) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    net_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX index_invoice_items_invoice_id (
        invoice_id
    ),

    CONSTRAINT fk_invoice_items_invoice_id
        FOREIGN KEY (invoice_id)
        REFERENCES invoices(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_invoice_items_company_id
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_invoice_items_product_id
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;