<?php
/**
 * Migration Script: Unify Tags System
 * Version: 1.0
 * Date: 2025-12-16
 * 
 * รวมระบบ Tags ให้ใช้ user_tags และ user_tag_assignments เป็นหลัก
 * 
 * โครงสร้างใหม่:
 * - user_tags: เก็บ tag definitions (id, line_account_id, name, color, description)
 * - user_tag_assignments: เก็บ user-tag relationships (user_id, tag_id, assigned_by)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🏷️ Unify Tags Migration</h2>";
echo "<pre style='background:#f5f5f5;padding:15px;border-radius:8px;font-family:monospace'>";

// Color mapping from name to hex
$colorMap = [
    'gray' => '#6B7280',
    'blue' => '#3B82F6',
    'green' => '#10B981',
    'red' => '#EF4444',
    'yellow' => '#F59E0B',
    'purple' => '#8B5CF6',
    'pink' => '#EC4899',
    'indigo' => '#6366F1',
    'teal' => '#14B8A6',
    'orange' => '#F97316'
];

$results = [];

try {
    echo "=== Starting Unify Tags Migration ===\n\n";

    // =====================================================
    // Step 1: Create user_tags table
    // =====================================================
    echo "1. Creating user_tags table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `user_tags` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `line_account_id` INT DEFAULT NULL COMMENT 'LINE Account ID (NULL = global)',
        `name` VARCHAR(100) NOT NULL COMMENT 'Tag name',
        `color` VARCHAR(7) DEFAULT '#3B82F6' COMMENT 'Hex color code',
        `description` TEXT COMMENT 'Tag description',
        `auto_assign_rules` JSON DEFAULT NULL COMMENT 'Auto-assign rules',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_line_account (line_account_id),
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ user_tags table ready\n";
    $results['user_tags_table'] = 'created';

    // =====================================================
    // Step 2: Create user_tag_assignments table
    // =====================================================
    echo "\n2. Creating user_tag_assignments table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL COMMENT 'User ID from users table',
        `tag_id` INT NOT NULL COMMENT 'Tag ID from user_tags table',
        `assigned_by` VARCHAR(50) DEFAULT 'manual' COMMENT 'Assignment method',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_tag` (`user_id`, `tag_id`),
        INDEX idx_user_id (user_id),
        INDEX idx_tag_id (tag_id),
        INDEX idx_assigned_by (assigned_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ user_tag_assignments table ready\n";
    $results['user_tag_assignments_table'] = 'created';

    // =====================================================
    // Step 3: Check for old 'tags' table and migrate
    // =====================================================
    echo "\n3. Checking for old 'tags' table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    $hasOldTagsTable = $stmt->fetch() !== false;
    
    if ($hasOldTagsTable) {
        echo "   Found old 'tags' table. Migrating data...\n";
        
        $stmt = $db->query("SELECT * FROM tags");
        $oldTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $migrated = 0;
        $skipped = 0;
        
        foreach ($oldTags as $tag) {
            $color = $tag['color'] ?? 'blue';
            $hexColor = $colorMap[$color] ?? (strpos($color, '#') === 0 ? $color : '#3B82F6');
            
            $checkStmt = $db->prepare("SELECT id FROM user_tags WHERE name = ? LIMIT 1");
            $checkStmt->execute([$tag['name']]);
            
            if (!$checkStmt->fetch()) {
                $insertStmt = $db->prepare("INSERT INTO user_tags (name, color, created_at) VALUES (?, ?, ?)");
                $insertStmt->execute([$tag['name'], $hexColor, $tag['created_at'] ?? date('Y-m-d H:i:s')]);
                $migrated++;
                echo "   - Migrated: {$tag['name']} (color: $hexColor)\n";
            } else {
                $skipped++;
            }
        }
        
        echo "   ✅ Migrated $migrated tags, skipped $skipped (already exist)\n";
        $results['migrate_old_tags'] = ['migrated' => $migrated, 'skipped' => $skipped];
        
        // Create tag ID mapping for assignment migration
        echo "\n   Creating tag ID mapping...\n";
        $tagMapping = [];
        $stmt = $db->query("SELECT id, name FROM tags");
        $oldTagsById = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($oldTagsById as $oldId => $name) {
            $stmt = $db->prepare("SELECT id FROM user_tags WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            $newId = $stmt->fetchColumn();
            if ($newId) {
                $tagMapping[$oldId] = $newId;
            }
        }
        echo "   ✅ Tag mapping created: " . count($tagMapping) . " tags\n";
        
        // Check for old user_tags (assignment table with user_id, tag_id)
        echo "\n   Checking for old assignment data...\n";
        try {
            $stmt = $db->query("DESCRIBE user_tags");
            $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            // If user_tags has both user_id and tag_id, it's the old assignment table
            // We need to check a different way since we just created the new structure
            $stmt = $db->query("SHOW TABLES LIKE 'user_tags_old'");
            if ($stmt->fetch()) {
                echo "   Found user_tags_old table. Migrating assignments...\n";
                $stmt = $db->query("SELECT * FROM user_tags_old");
                $oldAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $assignMigrated = 0;
                foreach ($oldAssignments as $assign) {
                    if (isset($assign['user_id']) && isset($assign['tag_id'])) {
                        $newTagId = $tagMapping[$assign['tag_id']] ?? null;
                        if ($newTagId) {
                            try {
                                $stmt = $db->prepare("INSERT IGNORE INTO user_tag_assignments (user_id, tag_id, assigned_by, created_at) VALUES (?, ?, 'migrated', ?)");
                                $stmt->execute([$assign['user_id'], $newTagId, $assign['created_at'] ?? date('Y-m-d H:i:s')]);
                                $assignMigrated++;
                            } catch (Exception $e) {}
                        }
                    }
                }
                echo "   ✅ Migrated $assignMigrated assignments\n";
                $results['migrate_assignments'] = $assignMigrated;
            }
        } catch (Exception $e) {
            echo "   ℹ️ No old assignment data to migrate\n";
        }
        
    } else {
        echo "   ℹ️ No old 'tags' table found. Skipping migration.\n";
        $results['migrate_old_tags'] = 'no old table';
    }

    // =====================================================
    // Step 4: Create default tags if needed
    // =====================================================
    echo "\n4. Creating default tags if needed...\n";
    $stmt = $db->query("SELECT COUNT(*) FROM user_tags");
    $tagCount = $stmt->fetchColumn();
    
    if ($tagCount == 0) {
        $defaultTags = [
            ['ลูกค้าใหม่', '#10B981', 'ลูกค้าที่เพิ่งเพิ่มเพื่อน'],
            ['VIP', '#EF4444', 'ลูกค้า VIP'],
            ['รอชำระเงิน', '#F59E0B', 'รอการชำระเงิน'],
            ['ชำระแล้ว', '#3B82F6', 'ชำระเงินแล้ว'],
            ['ส่งแล้ว', '#8B5CF6', 'จัดส่งสินค้าแล้ว'],
            ['สนใจสินค้า', '#EC4899', 'สนใจสินค้า']
        ];
        
        $insertStmt = $db->prepare("INSERT INTO user_tags (name, color, description) VALUES (?, ?, ?)");
        foreach ($defaultTags as $tag) {
            $insertStmt->execute($tag);
            echo "   - Created: {$tag[0]}\n";
        }
        echo "   ✅ Created " . count($defaultTags) . " default tags\n";
        $results['default_tags'] = count($defaultTags);
    } else {
        echo "   ℹ️ Already have $tagCount tags. Skipping default creation.\n";
        $results['default_tags'] = "existing: $tagCount";
    }

    // =====================================================
    // Step 5: Show current status
    // =====================================================
    echo "\n5. Current Status:\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM user_tags");
    $totalTags = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM user_tag_assignments");
    $totalAssignments = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM user_tag_assignments");
    $usersWithTags = $stmt->fetchColumn();
    
    echo "   - Total Tags: $totalTags\n";
    echo "   - Total Assignments: $totalAssignments\n";
    echo "   - Users with Tags: $usersWithTags\n";
    
    $results['total_tags'] = $totalTags;
    $results['total_assignments'] = $totalAssignments;
    $results['users_with_tags'] = $usersWithTags;

    // =====================================================
    // Summary
    // =====================================================
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Migration completed successfully!\n";
    echo str_repeat("=", 50) . "\n\n";
    
    echo "Summary:\n";
    echo "- user_tags table: Ready (tag definitions)\n";
    echo "- user_tag_assignments table: Ready (user-tag relationships)\n";
    echo "- All pages now use unified tag system\n\n";
    
    echo "Files updated to use unified tags:\n";
    echo "- messages.php\n";
    echo "- messages-v2.php\n";
    echo "- chat.php\n";
    echo "- broadcast-catalog.php\n";
    echo "- broadcast-catalog-v2.php\n";
    echo "- user-detail.php\n";
    echo "- user-tags.php (reference)\n";
    echo "- api/ajax_handler.php\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    $results['error'] = $e->getMessage();
}

echo "\n\nResults JSON:\n";
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";
?>
