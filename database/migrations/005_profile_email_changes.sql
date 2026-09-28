ALTER TABLE users
    ADD COLUMN pending_email VARCHAR(254) NULL DEFAULT NULL,
    ADD UNIQUE KEY uq_users_pending_email (pending_email);

CREATE TABLE email_change_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    new_email VARCHAR(254) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_change_tokens_hash (token_hash),
    KEY idx_email_change_tokens_user (user_id),
    KEY idx_email_change_tokens_expiry (expires_at),
    CONSTRAINT fk_email_change_tokens_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
