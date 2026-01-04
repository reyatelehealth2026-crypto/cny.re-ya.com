-- =============================================
-- WMS (Warehouse Management System) Migration
-- Pick-Pack-Ship Module
-- Version: 1.0
-- Description: Creates tables and fields for WMS operations
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- ALTER TRANSACTIONS TABLE FOR WMS
-- Note: Using stored procedure to handle IF NOT EXISTS for columns
-- =============================================

-- Drop procedure if exists and create new one
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

DELIMITER //
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(64),
    IN columnName VARCHAR(64),
    IN columnDef VARCHAR(1024)
)
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = tableName 
        AND COLUMN_NAME = columnName
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDef);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- Add WMS columns to transactions table
CALL AddColumnIfNotExists('transactions', 'wms_status', "ENUM('pending_pick','picking','picked','packing','packed','ready_to_ship','shipped','on_hold') DEFAULT NULL");
CALL AddColumnIfNotExists('transactions', 'picker_id', 'INT NULL');
CALL AddColumnIfNotExists('transactions', 'packer_id', 'INT NULL');
CALL AddColumnIfNotExists('transactions', 'pick_started_at', 'DATETIME NULL');
CALL AddColumnIfNotExists('transactions', 'pick_completed_at', 'DATETIME NULL');
CALL AddColumnIfNotExists('transactions', 'pack_started_at', 'DATETIME NULL');
CALL AddColumnIfNotExists('transactions', 'pack_completed_at', 'DATETIME NULL');
CALL AddColumnIfNotExists('transactions', 'shipped_at', 'DATETIME NULL');
CALL AddColumnIfNotExists('transactions', 'carrier', 'VARCHAR(50) NULL');
CALL AddColumnIfNotExists('transactions', 'package_weight', 'DECIMAL(10,2) NULL');
CALL AddColumnIfNotExists('transactions', 'package_dimensions', 'VARCHAR(50) NULL');
CALL AddColumnIfNotExists('transactions', 'wms_exception', 'VARCHAR(255) NULL');
CALL AddColumnIfNotExists('transactions', 'wms_exception_resolved_at', 'DATETIME NULL');
CALL AddColumnIfNotExists('transactions', 'wms_exception_resolved_by', 'INT NULL');
CALL AddColumnIfNotExists('transactions', 'label_printed_at', 'DATETIME NULL');

-- Drop the procedure after use
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Add indexes (will fail silently if already exist)
-- Using CREATE INDEX IF NOT EXISTS (MySQL 8.0+) or ignore errors


-- =============================================
-- WMS ACTIVITY LOGS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `wms_activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `action` ENUM(
        'pick_started', 
        'item_picked', 
        'pick_completed', 
        'pack_started', 
        'pack_completed', 
        'label_printed', 
        'shipped',
        'item_short', 
        'item_damaged', 
        'on_hold', 
        'exception_resolved'
    ) NOT NULL,
    `item_id` INT NULL COMMENT 'Reference to transaction_items.id',
    `staff_id` INT NULL COMMENT 'Reference to admin_users.id',
    `notes` TEXT NULL,
    `metadata` JSON NULL COMMENT 'Additional data like quantity, reason, etc.',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_wms_log_order` (`order_id`),
    INDEX `idx_wms_log_line_account` (`line_account_id`),
    INDEX `idx_wms_log_action` (`action`),
    INDEX `idx_wms_log_created` (`created_at`),
    INDEX `idx_wms_log_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- WMS BATCH PICKS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `wms_batch_picks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NOT NULL,
    `batch_number` VARCHAR(20) NOT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `picker_id` INT NULL COMMENT 'Reference to admin_users.id',
    `total_orders` INT DEFAULT 0,
    `total_items` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    
    UNIQUE KEY `uk_batch_number` (`batch_number`),
    INDEX `idx_batch_line_account` (`line_account_id`),
    INDEX `idx_batch_status` (`status`),
    INDEX `idx_batch_picker` (`picker_id`),
    INDEX `idx_batch_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- WMS BATCH PICK ORDERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `wms_batch_pick_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `batch_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `pick_status` ENUM('pending', 'picked') DEFAULT 'pending',
    `picked_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`batch_id`) REFERENCES `wms_batch_picks`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_batch_order` (`batch_id`, `order_id`),
    INDEX `idx_batch_pick_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- WMS PICK ITEMS TABLE (Track individual item pick status)
-- =============================================
CREATE TABLE IF NOT EXISTS `wms_pick_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `transaction_item_id` INT NOT NULL COMMENT 'Reference to transaction_items.id',
    `product_id` INT NOT NULL,
    `quantity_required` INT NOT NULL,
    `quantity_picked` INT DEFAULT 0,
    `status` ENUM('pending', 'picked', 'short', 'damaged') DEFAULT 'pending',
    `picked_by` INT NULL,
    `picked_at` DATETIME NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_order_item` (`order_id`, `transaction_item_id`),
    INDEX `idx_pick_item_order` (`order_id`),
    INDEX `idx_pick_item_product` (`product_id`),
    INDEX `idx_pick_item_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;
