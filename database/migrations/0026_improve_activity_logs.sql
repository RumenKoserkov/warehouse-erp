ALTER TABLE logs
    ADD COLUMN severity VARCHAR(20)
        NOT NULL DEFAULT 'info'
        AFTER action,

    ADD COLUMN request_id CHAR(32) NULL
        AFTER description,

    ADD COLUMN request_method VARCHAR(10) NULL
        AFTER request_id,

    ADD COLUMN request_uri VARCHAR(2048) NULL
        AFTER request_method,

    ADD COLUMN context JSON NULL
        AFTER request_uri,

    ADD INDEX index_logs_company_created (
        company_id,
        created_at
    ),

    ADD INDEX index_logs_company_action_created (
        company_id,
        action,
        created_at
    ),

    ADD INDEX index_logs_company_entity_created (
        company_id,
        entity_type,
        entity_id,
        created_at
    ),

    ADD INDEX index_logs_company_user_created (
        company_id,
        user_id,
        created_at
    ),

    ADD INDEX index_logs_company_severity_created (
        company_id,
        severity,
        created_at
    ),

    ADD INDEX index_logs_request_id (
        request_id
    );