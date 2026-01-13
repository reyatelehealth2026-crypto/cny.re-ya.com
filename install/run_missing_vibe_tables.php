<?php
/**
 * Create missing Vibe Selling v2 tables
 * - drug_pricing_rules
 * - ghost_draft_learning
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>🔧 Create Missing Vibe Selling Tables</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Create drug_pricing_rules
    echo "📝 Creating drug_pricing_rules table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS drug_pricing_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rule_name VARCHAR(100) NOT NULL,
            rule_type ENUM('category', 'brand', 'generic', 'margin', 'promotion') NOT NULL DEFAULT 'margin',
            category_id INT NULL COMMENT 'Product category ID if applicable',
            brand_name VARCHAR(255) NULL COMMENT 'Brand name if applicable',
            min_margin DECIMAL(5,2) DEFAULT 15.00 COMMENT 'Minimum margin percentage',
            max_margin DECIMAL(5,2) DEFAULT 40.00 COMMENT 'Maximum margin percentage',
            target_margin DECIMAL(5,2) DEFAULT 25.00 COMMENT 'Target margin percentage',
            price_rounding ENUM('none', 'nearest_5', 'nearest_10', 'up_5', 'up_10') DEFAULT 'nearest_5',
            conditions JSON COMMENT 'Additional conditions for rule application',
            priority INT DEFAULT 0 COMMENT 'Higher priority rules applied first',
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (rule_type),
            INDEX idx_category (category_id),
            INDEX idx_priority (priority),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created drug_pricing_rules\n";
    
    // Insert default pricing rules
    $db->exec("
        INSERT INTO drug_pricing_rules (rule_name, rule_type, min_margin, max_margin, target_margin, priority) VALUES
        ('Default Margin', 'margin', 15.00, 40.00, 25.00, 0),
        ('Generic Drugs', 'generic', 20.00, 50.00, 35.00, 10),
        ('Brand Drugs', 'brand', 10.00, 30.00, 20.00, 10),
        ('OTC Products', 'category', 15.00, 35.00, 25.00, 5)
        ON DUPLICATE KEY UPDATE target_margin = VALUES(target_margin)
    ");
    echo "✅ Inserted default pricing rules\n";
    
    // Create ghost_draft_learning
    echo "\n📝 Creating ghost_draft_learning table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS ghost_draft_learning (
            id INT AUTO_INCREMENT PRIMARY KEY,
            line_account_id INT NULL,
            user_id INT NOT NULL COMMENT 'Customer user ID',
            pharmacist_id INT NULL COMMENT 'Pharmacist who edited',
            customer_message TEXT NOT NULL COMMENT 'Original customer message',
            ai_draft TEXT NOT NULL COMMENT 'AI generated draft',
            pharmacist_final TEXT NOT NULL COMMENT 'Final message sent by pharmacist',
            edit_distance INT COMMENT 'Levenshtein distance between draft and final',
            edit_ratio DECIMAL(5,4) COMMENT 'Edit distance / original length ratio',
            was_accepted TINYINT(1) DEFAULT 0 COMMENT '1 if draft was used with minimal edits',
            context_stage VARCHAR(50) COMMENT 'Consultation stage at time of draft',
            context_symptoms JSON COMMENT 'Detected symptoms in conversation',
            context_drugs JSON COMMENT 'Drugs mentioned in conversation',
            context_health_profile JSON COMMENT 'Customer health profile snapshot',
            feedback_rating TINYINT NULL COMMENT 'Pharmacist rating 1-5',
            feedback_notes TEXT NULL COMMENT 'Pharmacist feedback notes',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_line_account (line_account_id),
            INDEX idx_user (user_id),
            INDEX idx_pharmacist (pharmacist_id),
            INDEX idx_accepted (was_accepted),
            INDEX idx_stage (context_stage),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created ghost_draft_learning\n";
    
    // Verify
    echo "\n📋 Verifying tables:\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'drug_pricing_rules'");
    if ($stmt->rowCount() > 0) {
        $count = $db->query("SELECT COUNT(*) FROM drug_pricing_rules")->fetchColumn();
        echo "✅ drug_pricing_rules exists ($count rows)\n";
    } else {
        echo "❌ drug_pricing_rules NOT found\n";
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'ghost_draft_learning'");
    if ($stmt->rowCount() > 0) {
        $count = $db->query("SELECT COUNT(*) FROM ghost_draft_learning")->fetchColumn();
        echo "✅ ghost_draft_learning exists ($count rows)\n";
    } else {
        echo "❌ ghost_draft_learning NOT found\n";
    }
    
    echo "\n🎉 Done!\n";
    echo "\n<a href='../dev-dashboard.php'>👉 Back to Dev Dashboard</a>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
