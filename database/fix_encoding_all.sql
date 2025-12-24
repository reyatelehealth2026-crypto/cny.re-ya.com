-- =============================================
-- Fix Thai Encoding for All Tables
-- Run this to fix encoding issues
-- =============================================

-- Set database charset
ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix users table
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users MODIFY display_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users MODIFY status_message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix messages table
ALTER TABLE messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE messages MODIFY content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix auto_replies table
ALTER TABLE auto_replies CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE auto_replies MODIFY keyword VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE auto_replies MODIFY reply_content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix products table
ALTER TABLE products CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE products MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE products MODIFY description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix product_categories table
ALTER TABLE product_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE product_categories MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE product_categories MODIFY description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix orders table
ALTER TABLE orders CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE orders MODIFY shipping_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE orders MODIFY shipping_address TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE orders MODIFY note TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix order_items table
ALTER TABLE order_items CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE order_items MODIFY product_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix broadcasts table
ALTER TABLE broadcasts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE broadcasts MODIFY title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE broadcasts MODIFY content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix groups table
ALTER TABLE `groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `groups` MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `groups` MODIFY description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix templates table
ALTER TABLE templates CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE templates MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE templates MODIFY content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix welcome_settings table
ALTER TABLE welcome_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE welcome_settings MODIFY text_content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix shop_settings table
ALTER TABLE shop_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE shop_settings MODIFY shop_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE shop_settings MODIFY welcome_message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE shop_settings MODIFY bank_accounts TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix line_accounts table
ALTER TABLE line_accounts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE line_accounts MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix admin_users table
ALTER TABLE admin_users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE admin_users MODIFY display_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix scheduled_messages table
ALTER TABLE scheduled_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE scheduled_messages MODIFY title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE scheduled_messages MODIFY content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix rich_menus table
ALTER TABLE rich_menus CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE rich_menus MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE rich_menus MODIFY chat_bar_text VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
