-- SQL скрипт для создания базы данных email_tracker
-- Выполните этот скрипт перед использованием системы

CREATE DATABASE IF NOT EXISTS email_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE email_tracker;

-- Таблица для очереди email
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(255) DEFAULT NULL,
    discord_id VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'sent', 'failed', 'retry') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_attempt TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at),
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для статистики
CREATE TABLE IF NOT EXISTS email_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total_emails INT DEFAULT 0,
    sent_emails INT DEFAULT 0,
    failed_emails INT DEFAULT 0,
    pending_emails INT DEFAULT 0,
    last_batch_sent TIMESTAMP NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Инициализация статистики
INSERT IGNORE INTO email_stats (id, total_emails) VALUES (1, 0);

-- Показать информацию о созданных таблицах
SHOW TABLES;
SELECT 'База данных email_tracker успешно создана!' as message;
