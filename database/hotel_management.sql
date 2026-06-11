-- ============================================================
-- Hotel Management System - Database Schema
-- Database: hotel_management
-- Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS `hotel_management`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `hotel_management`;

-- ============================================================
-- Drop tables in reverse FK dependency order
-- ============================================================

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `extra_charges`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bills`;
DROP TABLE IF EXISTS `check_ins`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `guests`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `room_types`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `roles`;

-- ============================================================
-- 1. roles - 角色表
-- ============================================================
CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. staff - 员工表
-- ============================================================
CREATE TABLE `staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role_id` INT NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. room_types - 房型表
-- ============================================================
CREATE TABLE `room_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `base_price` DECIMAL(10, 2) NOT NULL,
    `max_occupancy` INT DEFAULT 2,
    `amenities` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. rooms - 房间表
-- ============================================================
CREATE TABLE `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_number` VARCHAR(20) NOT NULL UNIQUE,
    `room_type_id` INT NOT NULL,
    `floor` INT DEFAULT 1,
    `status` ENUM('available', 'occupied', 'maintenance', 'reserved') DEFAULT 'available',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. guests - 客户表
-- ============================================================
CREATE TABLE `guests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `id_type` VARCHAR(50) DEFAULT NULL,
    `id_number` VARCHAR(100) DEFAULT NULL,
    `nationality` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `zip_code` VARCHAR(20) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `vip_status` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. reservations - 预订表
-- ============================================================
CREATE TABLE `reservations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reservation_code` VARCHAR(20) NOT NULL UNIQUE,
    `guest_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `check_in_date` DATE NOT NULL,
    `check_out_date` DATE NOT NULL,
    `num_guests` INT DEFAULT 1,
    `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
    `special_requests` TEXT DEFAULT NULL,
    `total_amount` DECIMAL(10, 2) DEFAULT 0.00,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `staff`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. check_ins - 入住记录表
-- ============================================================
CREATE TABLE `check_ins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reservation_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `guest_id` INT NOT NULL,
    `actual_check_in` DATETIME NOT NULL,
    `actual_check_out` DATETIME DEFAULT NULL,
    `status` ENUM('active', 'completed') DEFAULT 'active',
    `processed_by` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `staff`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. bills - 账单表
-- ============================================================
CREATE TABLE `bills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bill_number` VARCHAR(20) NOT NULL UNIQUE,
    `reservation_id` INT NOT NULL,
    `guest_id` INT NOT NULL,
    `room_charges` DECIMAL(10, 2) DEFAULT 0.00,
    `extra_charges` DECIMAL(10, 2) DEFAULT 0.00,
    `tax_amount` DECIMAL(10, 2) DEFAULT 0.00,
    `discount` DECIMAL(10, 2) DEFAULT 0.00,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `staff`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. payments - 支付记录表
-- ============================================================
CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bill_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `payment_method` ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'online') NOT NULL,
    `payment_date` DATETIME NOT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `processed_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bill_id`) REFERENCES `bills`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `staff`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. extra_charges - 额外费用表
-- ============================================================
CREATE TABLE `extra_charges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bill_id` INT NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `charge_type` ENUM('minibar', 'laundry', 'room_service', 'phone', 'other') DEFAULT 'other',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bill_id`) REFERENCES `bills`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. activity_logs - 操作日志表
-- ============================================================
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Indexes for performance
-- ============================================================
CREATE INDEX idx_staff_role ON `staff`(`role_id`);
CREATE INDEX idx_rooms_type ON `rooms`(`room_type_id`);
CREATE INDEX idx_rooms_status ON `rooms`(`status`);
CREATE INDEX idx_reservations_guest ON `reservations`(`guest_id`);
CREATE INDEX idx_reservations_room ON `reservations`(`room_id`);
CREATE INDEX idx_reservations_status ON `reservations`(`status`);
CREATE INDEX idx_reservations_dates ON `reservations`(`check_in_date`, `check_out_date`);
CREATE INDEX idx_checkins_reservation ON `check_ins`(`reservation_id`);
CREATE INDEX idx_checkins_status ON `check_ins`(`status`);
CREATE INDEX idx_bills_reservation ON `bills`(`reservation_id`);
CREATE INDEX idx_bills_guest ON `bills`(`guest_id`);
CREATE INDEX idx_bills_status ON `bills`(`status`);
CREATE INDEX idx_payments_bill ON `payments`(`bill_id`);
CREATE INDEX idx_extracharges_bill ON `extra_charges`(`bill_id`);
CREATE INDEX idx_activitylogs_user ON `activity_logs`(`user_id`);
CREATE INDEX idx_activitylogs_module ON `activity_logs`(`module`);
CREATE INDEX idx_activitylogs_created ON `activity_logs`(`created_at`);
