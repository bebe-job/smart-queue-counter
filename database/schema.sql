-- Create Database
CREATE DATABASE IF NOT EXISTS smart_queue_db;
USE smart_queue_db;

-- 1. Table for Queue Ticket Transactions
CREATE TABLE IF NOT EXISTS queues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_number INT NOT NULL,
    status ENUM('Waiting', 'Serving', 'Completed', 'Cancelled') DEFAULT 'Waiting',
    counter_number INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    served_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL
);

-- 2. Table for ESP32 Sensor Diagnostics (IR Sensor & Push Button Logs)
CREATE TABLE IF NOT EXISTS sensor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_type VARCHAR(20) NOT NULL,
    status_value VARCHAR(20) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Table for Service Counter Status
CREATE TABLE IF NOT EXISTS counter_status (
    counter_id INT PRIMARY KEY,
    current_queue_number INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Initial Default Record for Counter 1
INSERT INTO counter_status (counter_id, current_queue_number, is_active) 
VALUES (1, 0, TRUE)
ON DUPLICATE KEY UPDATE counter_id=1;