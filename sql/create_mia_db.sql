-- Mia dedicated database setup
-- Run as root MySQL user

CREATE DATABASE IF NOT EXISTS mia_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'miauser'@'localhost' IDENTIFIED BY 'MiaPass2026!';
GRANT ALL PRIVILEGES ON mia_db.* TO 'miauser'@'localhost';
FLUSH PRIVILEGES;
