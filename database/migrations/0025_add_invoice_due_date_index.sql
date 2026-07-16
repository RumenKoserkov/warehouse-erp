ALTER TABLE invoices
    ADD INDEX index_invoices_company_due_date (
        company_id,
        document_type,
        status,
        due_date
    );