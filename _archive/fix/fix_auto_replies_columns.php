    <?php
    /**
     * Fix auto_replies table - Add missing columns
     */
    require_once 'config/config.php';
    require_once 'config/database.php';

    echo "<h2>Fix auto_replies Table</h2>";

    try {
        $db = Database::getInstance()->getConnection();
        
        // Columns to add
        $columns = [
            'alt_text' => "ALTER TABLE auto_replies ADD COLUMN alt_text VARCHAR(400) DEFAULT NULL COMMENT 'Alt text for Flex Message' AFTER reply_content",
            'sender_name' => "ALTER TABLE auto_replies ADD COLUMN sender_name VARCHAR(100) DEFAULT NULL COMMENT 'Custom sender name' AFTER alt_text",
            'sender_icon' => "ALTER TABLE auto_replies ADD COLUMN sender_icon VARCHAR(500) DEFAULT NULL COMMENT 'Custom sender icon URL' AFTER sender_name",
            'quick_reply' => "ALTER TABLE auto_replies ADD COLUMN quick_reply TEXT DEFAULT NULL COMMENT 'Quick reply buttons JSON' AFTER sender_icon",
            'description' => "ALTER TABLE auto_replies ADD COLUMN description VARCHAR(255) DEFAULT NULL COMMENT 'Rule description' AFTER keyword",
            'tags' => "ALTER TABLE auto_replies ADD COLUMN tags VARCHAR(255) DEFAULT NULL COMMENT 'Tags for categorization' AFTER description",
            'use_count' => "ALTER TABLE auto_replies ADD COLUMN use_count INT DEFAULT 0 COMMENT 'Number of times used' AFTER priority",
            'last_used_at' => "ALTER TABLE auto_replies ADD COLUMN last_used_at TIMESTAMP NULL COMMENT 'Last time this rule was triggered' AFTER use_count",
            'enable_share' => "ALTER TABLE auto_replies ADD COLUMN enable_share TINYINT(1) DEFAULT 0 COMMENT 'Enable share button' AFTER quick_reply",
            'share_button_label' => "ALTER TABLE auto_replies ADD COLUMN share_button_label VARCHAR(50) DEFAULT '📤 แชร์ให้เพื่อน' COMMENT 'Share button label' AFTER enable_share",
        ];
        
        foreach ($columns as $colName => $sql) {
            // Check if column exists (use query instead of prepare for SHOW COLUMNS)
            $check = $db->query("SHOW COLUMNS FROM auto_replies LIKE '{$colName}'");
            
            if ($check->rowCount() == 0) {
                try {
                    $db->exec($sql);
                    echo "<p style='color:green'>✅ Added column: {$colName}</p>";
                } catch (PDOException $e) {
                    echo "<p style='color:red'>❌ Error adding {$colName}: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p style='color:gray'>⏭️ Column exists: {$colName}</p>";
            }
        }
        
        // Show current structure
        echo "<h3>Current Table Structure</h3>";
        echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        $cols = $db->query("SHOW COLUMNS FROM auto_replies")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
        }
        echo "</table>";
        
        echo "<hr><p style='color:green; font-weight:bold'>✅ Done! ลองทดสอบ auto-reply อีกครั้ง</p>";
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
