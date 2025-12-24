-- Migration: Flex Templates Table
-- สำหรับเก็บเทมเพลต Flex Message ที่สร้างจาก Builder

CREATE TABLE IF NOT EXISTS flex_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT 'custom',
    flex_json LONGTEXT NOT NULL,
    thumbnail_url VARCHAR(500) NULL,
    line_account_id INT NULL,
    use_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_line_account (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default templates
INSERT INTO flex_templates (name, category, flex_json) VALUES
('Product Card', 'product', '{"type":"bubble","hero":{"type":"image","url":"https://developers-resource.landpress.line.me/fx/img/01_1_cafe.png","size":"full","aspectRatio":"20:13","aspectMode":"cover"},"body":{"type":"box","layout":"vertical","contents":[{"type":"text","text":"ชื่อสินค้า","weight":"bold","size":"xl"},{"type":"text","text":"รายละเอียดสินค้า","size":"sm","color":"#666666","margin":"md","wrap":true},{"type":"box","layout":"baseline","margin":"md","contents":[{"type":"text","text":"฿","size":"sm","color":"#FF5551","flex":0},{"type":"text","text":"999","size":"xl","color":"#FF5551","weight":"bold","flex":0}]}]},"footer":{"type":"box","layout":"vertical","spacing":"sm","contents":[{"type":"button","style":"primary","color":"#06C755","action":{"type":"uri","label":"🛒 สั่งซื้อ","uri":"https://example.com"}}]}}'),
('Promotion', 'promotion', '{"type":"bubble","styles":{"hero":{"backgroundColor":"#FF6B6B"}},"hero":{"type":"box","layout":"vertical","contents":[{"type":"text","text":"🎉 SALE","size":"3xl","weight":"bold","color":"#FFFFFF","align":"center"},{"type":"text","text":"ลดสูงสุด 50%","size":"xl","color":"#FFFFFF","align":"center","margin":"md"}],"paddingAll":"30px"},"body":{"type":"box","layout":"vertical","contents":[{"type":"text","text":"โปรโมชั่นพิเศษ!","weight":"bold","size":"lg"},{"type":"text","text":"สินค้าลดราคาพิเศษ เฉพาะวันนี้เท่านั้น!","size":"sm","color":"#666666","wrap":true,"margin":"md"}]},"footer":{"type":"box","layout":"vertical","contents":[{"type":"button","style":"primary","color":"#FF6B6B","action":{"type":"uri","label":"🛍️ ช้อปเลย!","uri":"https://example.com"}}]}}')
ON DUPLICATE KEY UPDATE name = name;
