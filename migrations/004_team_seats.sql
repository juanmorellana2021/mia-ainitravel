-- migrations/004_team_seats.sql
-- Team seats: additional logins belonging to a client account (S/39/mo each)

CREATE TABLE IF NOT EXISTS mia_client_seats (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    client_id     INT UNSIGNED    NOT NULL,
    name          VARCHAR(120)    NOT NULL,
    email         VARCHAR(255)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
    role          ENUM('admin','agent') NOT NULL DEFAULT 'agent',
    active        TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_seat_email (email),
    KEY idx_seat_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
