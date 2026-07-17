CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    email_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,

    attempt_count SMALLINT UNSIGNED
        NOT NULL DEFAULT 0,

    first_attempt_at DATETIME NOT NULL,
    last_attempt_at DATETIME NOT NULL,

    locked_until DATETIME NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_login_email_ip (
        email_hash,
        ip_address
    ),

    INDEX index_login_attempts_locked (
        locked_until
    ),

    INDEX index_login_attempts_last_attempt (
        last_attempt_at
    )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;