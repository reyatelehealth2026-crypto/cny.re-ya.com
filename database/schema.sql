            -- LINE OA Manager Database Schema

            CREATE DATABASE IF NOT EXISTS line_oa_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            USE line_oa_manager;

            -- Users (LINE Users)
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_user_id VARCHAR(50) UNIQUE NOT NULL,
                display_name VARCHAR(255),
                picture_url TEXT,
                status_message TEXT,
                is_blocked TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );

            -- Groups (User Groups)
            CREATE TABLE groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                color VARCHAR(7) DEFAULT '#3B82F6',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- User-Group Relationship
            CREATE TABLE user_groups (
                user_id INT,
                group_id INT,
                PRIMARY KEY (user_id, group_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
            );

            -- Messages
            CREATE TABLE messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                direction ENUM('incoming', 'outgoing') NOT NULL,
                message_type VARCHAR(50) DEFAULT 'text',
                content TEXT,
                reply_token VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );


            -- Auto-Reply Rules
            CREATE TABLE auto_replies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                keyword VARCHAR(255) NOT NULL,
                match_type ENUM('exact', 'contains', 'starts_with', 'regex') DEFAULT 'contains',
                reply_type VARCHAR(50) DEFAULT 'text',
                reply_content TEXT NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                priority INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- Broadcasts
            CREATE TABLE broadcasts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message_type VARCHAR(50) DEFAULT 'text',
                content TEXT NOT NULL,
                target_type ENUM('all', 'group') DEFAULT 'all',
                target_group_id INT NULL,
                sent_count INT DEFAULT 0,
                status ENUM('draft', 'scheduled', 'sent', 'failed') DEFAULT 'draft',
                scheduled_at TIMESTAMP NULL,
                sent_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (target_group_id) REFERENCES groups(id) ON DELETE SET NULL
            );

            -- Rich Menus
            CREATE TABLE rich_menus (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_rich_menu_id VARCHAR(100),
                name VARCHAR(255) NOT NULL,
                chat_bar_text VARCHAR(50),
                size_width INT DEFAULT 2500,
                size_height INT DEFAULT 1686,
                areas JSON,
                image_path VARCHAR(255),
                is_default TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- Templates
            CREATE TABLE templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                category VARCHAR(100),
                message_type VARCHAR(50) DEFAULT 'text',
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- Scheduled Messages
            CREATE TABLE scheduled_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message_type VARCHAR(50) DEFAULT 'text',
                content TEXT NOT NULL,
                target_type ENUM('all', 'group', 'user') DEFAULT 'all',
                target_id INT NULL,
                scheduled_at TIMESTAMP NOT NULL,
                repeat_type ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none',
                status ENUM('pending', 'sent', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- AI Chat Settings
            CREATE TABLE ai_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                is_enabled TINYINT(1) DEFAULT 0,
                system_prompt TEXT,
                model VARCHAR(50) DEFAULT 'gpt-3.5-turbo',
                max_tokens INT DEFAULT 500,
                temperature DECIMAL(2,1) DEFAULT 0.7,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- Analytics Events
            CREATE TABLE analytics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(50) NOT NULL,
                event_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- Telegram Notifications Settings
            CREATE TABLE telegram_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                is_enabled TINYINT(1) DEFAULT 0,
                notify_new_follower TINYINT(1) DEFAULT 1,
                notify_new_message TINYINT(1) DEFAULT 1,
                notify_unfollow TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- Insert default settings
            INSERT INTO ai_settings (system_prompt) VALUES ('คุณเป็นผู้ช่วยที่เป็นมิตรและช่วยเหลือลูกค้า ตอบคำถามอย่างสุภาพและกระชับ');
            INSERT INTO telegram_settings (is_enabled) VALUES (0);


            -- =============================================
            -- LINE SHOP MODULE
            -- =============================================

            -- Product Categories
            CREATE TABLE product_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                image_url VARCHAR(500),
                sort_order INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- Products
            CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                sale_price DECIMAL(10,2) NULL,
                image_url VARCHAR(500),
                stock INT DEFAULT 0,
                sku VARCHAR(100),
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL
            );

            -- Product Images (multiple images per product)
            CREATE TABLE product_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                image_url VARCHAR(500) NOT NULL,
                sort_order INT DEFAULT 0,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            );

            -- Shopping Cart
            CREATE TABLE cart_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_cart_item (user_id, product_id)
            );

            -- Orders
            CREATE TABLE orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                shipping_fee DECIMAL(10,2) DEFAULT 0,
                discount_amount DECIMAL(10,2) DEFAULT 0,
                grand_total DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'confirmed', 'paid', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending',
                payment_method VARCHAR(50),
                payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
                shipping_name VARCHAR(255),
                shipping_phone VARCHAR(20),
                shipping_address TEXT,
                shipping_tracking VARCHAR(100),
                note TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            -- Order Items
            CREATE TABLE order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT,
                product_name VARCHAR(255) NOT NULL,
                product_price DECIMAL(10,2) NOT NULL,
                quantity INT NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
            );

            -- Payment Slips
            CREATE TABLE payment_slips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                image_url VARCHAR(500) NOT NULL,
                amount DECIMAL(10,2),
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                admin_note TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            );

            -- Shop Settings
            CREATE TABLE shop_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                shop_name VARCHAR(255) DEFAULT 'LINE Shop',
                shop_logo VARCHAR(500),
                welcome_message TEXT,
                shipping_fee DECIMAL(10,2) DEFAULT 50,
                free_shipping_min DECIMAL(10,2) DEFAULT 500,
                bank_accounts TEXT,
                promptpay_number VARCHAR(20),
                contact_phone VARCHAR(20),
                is_open TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- Insert default shop settings
            INSERT INTO shop_settings (shop_name, welcome_message, bank_accounts) VALUES 
            ('LINE Shop', 'ยินดีต้อนรับสู่ร้านค้าของเรา!', '{"banks":[{"name":"กสิกรไทย","account":"xxx-x-xxxxx-x","holder":"ชื่อบัญชี"}]}');

            -- Sample Categories
            INSERT INTO product_categories (name, description, sort_order) VALUES
            ('สินค้าแนะนำ', 'สินค้าขายดีและแนะนำ', 1),
            ('สินค้าใหม่', 'สินค้ามาใหม่', 2),
            ('โปรโมชั่น', 'สินค้าลดราคา', 3);


            -- User Session State (สำหรับเก็บสถานะการสนทนา)
            CREATE TABLE IF NOT EXISTS user_states (
                user_id INT PRIMARY KEY,
                state VARCHAR(50) DEFAULT NULL,
                state_data JSON,
                expires_at TIMESTAMP NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            -- Webhook Event Deduplication (ป้องกัน webhook ซ้ำ)
            CREATE TABLE IF NOT EXISTS webhook_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id VARCHAR(100) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created_at (created_at)
            );

            -- Auto cleanup old webhook events (เก็บแค่ 24 ชม.)
            -- Run this periodically: DELETE FROM webhook_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);

            -- =============================================
            -- MULTI-LINE ACCOUNT MANAGEMENT
            -- =============================================

            -- LINE Accounts (หลายบัญชี LINE OA)
            CREATE TABLE IF NOT EXISTS line_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL COMMENT 'ชื่อบัญชี LINE OA',
                channel_id VARCHAR(100) COMMENT 'Channel ID',
                channel_secret VARCHAR(100) NOT NULL COMMENT 'Channel Secret',
                channel_access_token TEXT NOT NULL COMMENT 'Channel Access Token',
                webhook_url VARCHAR(500) COMMENT 'Webhook URL',
                basic_id VARCHAR(50) COMMENT 'LINE Basic ID (@xxx)',
                picture_url VARCHAR(500) COMMENT 'รูปโปรไฟล์',
                is_active TINYINT(1) DEFAULT 1,
                is_default TINYINT(1) DEFAULT 0 COMMENT 'บัญชีหลัก',
                settings JSON COMMENT 'ตั้งค่าเพิ่มเติม',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_channel_secret (channel_secret)
            );

            -- เพิ่ม line_account_id ในตาราง users
            ALTER TABLE users ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
            ALTER TABLE users ADD INDEX idx_line_account (line_account_id);

            -- เพิ่ม line_account_id ในตาราง messages
            ALTER TABLE messages ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
            ALTER TABLE messages ADD INDEX idx_msg_line_account (line_account_id);

            -- เพิ่ม line_account_id ในตาราง orders
            ALTER TABLE orders ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
            ALTER TABLE orders ADD INDEX idx_order_line_account (line_account_id);

            -- เพิ่ม line_account_id ในตาราง auto_replies
            ALTER TABLE auto_replies ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
            ALTER TABLE auto_replies ADD INDEX idx_reply_line_account (line_account_id);

            -- เพิ่ม line_account_id ในตาราง broadcasts
            ALTER TABLE broadcasts ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
            ALTER TABLE broadcasts ADD INDEX idx_broadcast_line_account (line_account_id);

            -- เพิ่ม line_account_id ในตาราง products
            ALTER TABLE products ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
            ALTER TABLE products ADD INDEX idx_product_line_account (line_account_id);

            -- เพิ่ม line_account_id ในตาราง product_categories
            ALTER TABLE product_categories ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
            ALTER TABLE product_categories ADD INDEX idx_cat_line_account (line_account_id);

            -- เพิ่ม line_account_id ในตาราง scheduled_messages
            ALTER TABLE scheduled_messages ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
            ALTER TABLE scheduled_messages ADD INDEX idx_scheduled_line_account (line_account_id);
