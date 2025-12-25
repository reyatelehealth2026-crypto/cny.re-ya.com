<?php
/**
 * Fix Script: Unify Tags System
 * Version: 1.0
 * Date: 2025-12-16
 * 
 * รวมระบบ Tags ให้ใช้โครงสร้างเดียวกันทั้งระบบ:
 * - user_tags: เก็บ tag definitions
 * - user_tag_assignments: เก็บ user-tag relationships
 * 
 * ไฟล์ที่ได้รับการอัพเดท:
 * - messages.php
 * - messages-v2.php
 * - chat.php
 * - broadcast-catalog.php
 * - broadcast-catalog-v2.php
 * - user-detail.php
 * - user-tags.php (reference)
 * - api/ajax_handler.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Fix Unify Tags</title>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:900px;margin:0 auto}";
echo ".success{color:#10B981}.error{color:#EF4444}.warning{color:#F59E0B}";
echo "pre{background:#f5f5f5;padding:15px;border-radius:8px;overflow-x:auto}";
echo ".card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin:15px 0}";
echo "h1{color:#1f2937}h2{color:#374151;border-bottom:2px solid #e5e7eb;padding-bottom:10px}";
echo ".tag{display:inline-block;padding:2px 8px;border-radius:9999px;font-size:12px;margin:2px}";
echo "</style></head><body>";

echo "<h1>🏷️ Fix Unify Tags System</h1>";
echo "<p>รวมระบบ Tags ให้ใช้โครงสร้างเดียวกันทั้งระบบ</p>";

// Color mapping
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
    // =====================================================
    // Step 1: Create user_tags table
    // =====================================================
    echo "<div class='card'><h2>1. สร้างตาราง user_tags</h2>";
    
    $db->exec("CREATE TABLE IF NOT EXISTS `user_tags` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `line_account_id` INT DEFAULT NULL,
        `name` VARCHAR(100) NOT NULL,
        `color` VARCHAR(7) DEFAULT '#3B82F6',
        `description` TEXT,
        `auto_assign_rules` JSON DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_line_account (line_account_id),
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "<p class='success'>✅ ตาราง user_tags พร้อมใช้งาน</p>";
    $results['user_tags_table'] = 'success';
    echo "</div>";

    // =====================================================
    // Step 2: Create user_tag_assignments table
    // =====================================================
    echo "<div class='card'><h2>2. สร้างตาราง user_tag_assignments</h2>";
    
    $db->exec("CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `tag_id` INT NOT NULL,
        `assigned_by` VARCHAR(50) DEFAULT 'manual',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_tag` (`user_id`, `tag_id`),
        INDEX idx_user_id (user_id),
        INDEX idx_tag_id (tag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "<p class='success'>✅ ตาราง user_tag_assignments พร้อมใช้งาน</p>";
    $results['user_tag_assignments_table'] = 'success';
    echo "</div>";

    // =====================================================
    // Step 3: Migrate from old 'tags' table
    // =====================================================
    echo "<div class='card'><h2>3. Migrate จากตาราง tags เก่า</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    $hasOldTagsTable = $stmt->fetch() !== false;
    
    if ($hasOldTagsTable) {
        echo "<p class='warning'>⚠️ พบตาราง tags เก่า กำลัง migrate...</p>";
        
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
                echo "<p>- Migrated: <span class='tag' style='background:{$hexColor}20;color:{$hexColor}'>{$tag['name']}</span></p>";
            } else {
                $skipped++;
            }
        }
        
        echo "<p class='success'>✅ Migrated $migrated tags, skipped $skipped (already exist)</p>";
        $results['migrate_old_tags'] = "migrated: $migrated, skipped: $skipped";
    } else {
        echo "<p>ℹ️ ไม่พบตาราง tags เก่า</p>";
        $results['migrate_old_tags'] = 'no old table';
    }
    echo "</div>";

    // =====================================================
    // Step 4: Migrate old user_tags assignments (if structure is old)
    // =====================================================
    echo "<div class='card'><h2>4. ตรวจสอบโครงสร้าง user_tags</h2>";
    
    $stmt = $db->query("DESCRIBE user_tags");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('user_id', $columns) && in_array('tag_id', $columns) && !in_array('name', $columns)) {
        echo "<p class='warning'>⚠️ พบโครงสร้างเก่า (assignment table) ต้อง migrate ด้วยตนเอง</p>";
        echo "<p>กรุณา backup ข้อมูลและ restructure ตาราง</p>";
        $results['structure_check'] = 'old structure found';
    } else {
        echo "<p class='success'>✅ โครงสร้าง user_tags ถูกต้อง (tag definition table)</p>";
        $results['structure_check'] = 'correct';
    }
    echo "</div>";

    // =====================================================
    // Step 5: Create default tags
    // =====================================================
    echo "<div class='card'><h2>5. สร้าง Default Tags</h2>";
    
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
            echo "<p>- Created: <span class='tag' style='background:{$tag[1]}20;color:{$tag[1]}'>{$tag[0]}</span></p>";
        }
        echo "<p class='success'>✅ สร้าง " . count($defaultTags) . " default tags</p>";
        $results['default_tags'] = 'created ' . count($defaultTags);
    } else {
        echo "<p>ℹ️ มี $tagCount tags อยู่แล้ว ข้าม</p>";
        $results['default_tags'] = "existing: $tagCount";
    }
    echo "</div>";

    // =====================================================
    // Step 6: Show current tags
    // =====================================================
    echo "<div class='card'><h2>6. Tags ปัจจุบัน</h2>";
    
    $stmt = $db->query("SELECT t.*, COALESCE(COUNT(DISTINCT uta.user_id), 0) as user_count 
                        FROM user_tags t 
                        LEFT JOIN user_tag_assignments uta ON t.id = uta.tag_id 
                        GROUP BY t.id 
                        ORDER BY t.name");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tags) > 0) {
        echo "<table style='width:100%;border-collapse:collapse'>";
        echo "<tr style='background:#f9fafb'><th style='padding:10px;text-align:left;border-bottom:1px solid #e5e7eb'>ID</th>";
        echo "<th style='padding:10px;text-align:left;border-bottom:1px solid #e5e7eb'>Tag</th>";
        echo "<th style='padding:10px;text-align:left;border-bottom:1px solid #e5e7eb'>Color</th>";
        echo "<th style='padding:10px;text-align:center;border-bottom:1px solid #e5e7eb'>Users</th></tr>";
        
        foreach ($tags as $tag) {
            echo "<tr>";
            echo "<td style='padding:10px;border-bottom:1px solid #e5e7eb'>{$tag['id']}</td>";
            echo "<td style='padding:10px;border-bottom:1px solid #e5e7eb'>";
            echo "<span class='tag' style='background:{$tag['color']}20;color:{$tag['color']}'>{$tag['name']}</span></td>";
            echo "<td style='padding:10px;border-bottom:1px solid #e5e7eb'>{$tag['color']}</td>";
            echo "<td style='padding:10px;text-align:center;border-bottom:1px solid #e5e7eb'>{$tag['user_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>ไม่มี tags</p>";
    }
    echo "</div>";

    // =====================================================
    // Step 7: Show assignments count
    // =====================================================
    echo "<div class='card'><h2>7. สถิติ Tag Assignments</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM user_tag_assignments");
    $assignmentCount = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM user_tag_assignments");
    $usersWithTags = $stmt->fetchColumn();
    
    echo "<p>📊 Total Assignments: <strong>$assignmentCount</strong></p>";
    echo "<p>👥 Users with Tags: <strong>$usersWithTags</strong></p>";
    $results['assignments'] = $assignmentCount;
    $results['users_with_tags'] = $usersWithTags;
    echo "</div>";

    // =====================================================
    // Summary
    // =====================================================
    echo "<div class='card' style='background:#f0fdf4;border-color:#86efac'>";
    echo "<h2 style='color:#166534'>✅ Migration สำเร็จ!</h2>";
    echo "<p>ระบบ Tags ถูกรวมให้ใช้โครงสร้างเดียวกันแล้ว:</p>";
    echo "<ul>";
    echo "<li><strong>user_tags</strong> - เก็บ tag definitions (id, name, color, description)</li>";
    echo "<li><strong>user_tag_assignments</strong> - เก็บ user-tag relationships (user_id, tag_id)</li>";
    echo "</ul>";
    echo "<p>ไฟล์ที่ใช้ระบบนี้:</p>";
    echo "<ul>";
    echo "<li>user-tags.php (หน้าจัดการ Tags)</li>";
    echo "<li>messages.php, messages-v2.php, chat.php (หน้าแชท)</li>";
    echo "<li>broadcast.php, broadcast-catalog.php, broadcast-catalog-v2.php (หน้า Broadcast)</li>";
    echo "<li>users.php, user-detail.php (หน้าจัดการ Users)</li>";
    echo "<li>api/ajax_handler.php (API)</li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='card' style='background:#fef2f2;border-color:#fca5a5'>";
    echo "<h2 class='error'>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='card'><h2>📋 Results Summary</h2><pre>" . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre></div>";

echo "</body></html>";
?>
