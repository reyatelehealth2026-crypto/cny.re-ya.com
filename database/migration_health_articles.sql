-- Migration: Health Articles for Landing Page
-- บทความสุขภาพสำหรับ SEO และให้ความรู้ลูกค้า

-- Health Article Categories
CREATE TABLE IF NOT EXISTS `health_article_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'fas fa-folder',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_category_slug` (`line_account_id`, `slug`),
    INDEX `idx_category_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Health Articles
CREATE TABLE IF NOT EXISTS `health_articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NULL,
    `category_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `excerpt` TEXT NULL COMMENT 'Short description for preview',
    `content` LONGTEXT NOT NULL,
    `featured_image` VARCHAR(500) NULL,
    `author_name` VARCHAR(100) NULL,
    `author_title` VARCHAR(100) NULL COMMENT 'e.g. เภสัชกร, แพทย์',
    `author_image` VARCHAR(500) NULL,
    `tags` JSON NULL COMMENT 'Array of tags for SEO',
    `meta_title` VARCHAR(255) NULL,
    `meta_description` VARCHAR(500) NULL,
    `meta_keywords` VARCHAR(500) NULL,
    `view_count` INT DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_published` TINYINT(1) DEFAULT 0,
    `published_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_article_slug` (`line_account_id`, `slug`),
    INDEX `idx_article_published` (`is_published`, `published_at`),
    INDEX `idx_article_featured` (`is_featured`, `is_published`),
    INDEX `idx_article_category` (`category_id`),
    FULLTEXT KEY `ft_article_search` (`title`, `excerpt`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO `health_article_categories` (`name`, `slug`, `description`, `icon`, `sort_order`) VALUES
('สุขภาพทั่วไป', 'general-health', 'บทความเกี่ยวกับสุขภาพทั่วไป', 'fas fa-heartbeat', 1),
('โภชนาการ', 'nutrition', 'บทความเกี่ยวกับอาหารและโภชนาการ', 'fas fa-apple-alt', 2),
('ยาและวิตามิน', 'medicine-vitamins', 'ความรู้เกี่ยวกับยาและวิตามิน', 'fas fa-pills', 3),
('โรคและการรักษา', 'diseases-treatment', 'ข้อมูลโรคและวิธีการรักษา', 'fas fa-stethoscope', 4),
('สุขภาพจิต', 'mental-health', 'บทความเกี่ยวกับสุขภาพจิต', 'fas fa-brain', 5),
('ออกกำลังกาย', 'exercise', 'เคล็ดลับการออกกำลังกาย', 'fas fa-running', 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Insert sample articles
INSERT INTO `health_articles` (`category_id`, `title`, `slug`, `excerpt`, `content`, `author_name`, `author_title`, `tags`, `is_featured`, `is_published`, `published_at`) VALUES
(1, 'วิธีดูแลสุขภาพในหน้าหนาว', 'winter-health-tips', 
'เคล็ดลับการดูแลสุขภาพในช่วงอากาศเย็น ป้องกันหวัดและโรคระบบทางเดินหายใจ',
'<h2>การดูแลสุขภาพในหน้าหนาว</h2>
<p>ช่วงหน้าหนาวเป็นช่วงที่ร่างกายต้องการการดูแลเป็นพิเศษ เนื่องจากอากาศที่เย็นลงทำให้ระบบภูมิคุ้มกันทำงานหนักขึ้น</p>

<h3>1. รักษาความอบอุ่นของร่างกาย</h3>
<p>สวมเสื้อผ้าหนาๆ หลายชั้น โดยเฉพาะบริเวณหน้าอก คอ และศีรษะ</p>

<h3>2. ทานอาหารที่มีประโยชน์</h3>
<p>เน้นอาหารที่มีวิตามินซีสูง เช่น ส้ม ฝรั่ง มะนาว เพื่อเสริมภูมิคุ้มกัน</p>

<h3>3. ดื่มน้ำอุ่นเป็นประจำ</h3>
<p>น้ำอุ่นช่วยให้ร่างกายอบอุ่นและช่วยระบบย่อยอาหาร</p>

<h3>4. พักผ่อนให้เพียงพอ</h3>
<p>นอนหลับ 7-8 ชั่วโมงต่อคืน เพื่อให้ร่างกายได้ซ่อมแซมตัวเอง</p>',
'ภก.สมชาย ใจดี', 'เภสัชกร', '["หน้าหนาว", "สุขภาพ", "ภูมิคุ้มกัน", "ป้องกันหวัด"]', 1, 1, NOW()),

(3, 'วิตามินซี ประโยชน์และวิธีทาน', 'vitamin-c-benefits',
'ทำความรู้จักวิตามินซี ประโยชน์ต่อร่างกาย และวิธีทานที่ถูกต้อง',
'<h2>วิตามินซี สารอาหารสำคัญ</h2>
<p>วิตามินซีเป็นวิตามินที่ละลายในน้ำ ร่างกายไม่สามารถสร้างเองได้ จึงต้องได้รับจากอาหารหรืออาหารเสริม</p>

<h3>ประโยชน์ของวิตามินซี</h3>
<ul>
<li>เสริมสร้างระบบภูมิคุ้มกัน</li>
<li>ช่วยในการสร้างคอลลาเจน</li>
<li>ต้านอนุมูลอิสระ</li>
<li>ช่วยดูดซึมธาตุเหล็ก</li>
</ul>

<h3>ปริมาณที่แนะนำ</h3>
<p>ผู้ใหญ่ควรได้รับวิตามินซี 60-90 มิลลิกรัมต่อวัน</p>

<h3>แหล่งอาหารที่มีวิตามินซีสูง</h3>
<p>ส้ม ฝรั่ง สตรอว์เบอร์รี่ บร็อคโคลี่ พริกหวาน</p>',
'ภญ.สมหญิง รักสุขภาพ', 'เภสัชกร', '["วิตามินซี", "อาหารเสริม", "ภูมิคุ้มกัน"]', 1, 1, NOW()),

(2, '5 อาหารเสริมภูมิคุ้มกัน', 'immune-boosting-foods',
'รวม 5 อาหารที่ช่วยเสริมสร้างภูมิคุ้มกันให้แข็งแรง',
'<h2>อาหารเสริมภูมิคุ้มกัน</h2>
<p>การทานอาหารที่มีประโยชน์เป็นวิธีธรรมชาติในการเสริมสร้างภูมิคุ้มกัน</p>

<h3>1. กระเทียม</h3>
<p>มีสารอัลลิซินที่ช่วยต้านเชื้อโรค</p>

<h3>2. ขิง</h3>
<p>ช่วยลดการอักเสบและเสริมภูมิคุ้มกัน</p>

<h3>3. โยเกิร์ต</h3>
<p>มีโปรไบโอติกส์ที่ดีต่อระบบย่อยอาหาร</p>

<h3>4. ผักโขม</h3>
<p>อุดมไปด้วยวิตามินและแร่ธาตุ</p>

<h3>5. อัลมอนด์</h3>
<p>มีวิตามินอีที่เป็นสารต้านอนุมูลอิสระ</p>',
'ภก.สมชาย ใจดี', 'เภสัชกร', '["อาหาร", "ภูมิคุ้มกัน", "โภชนาการ"]', 0, 1, NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);
