<?php
/**
 * Run Vibe Selling OS v2 Migration
 * สร้างตารางสำหรับระบบ AI-Powered Pharmacy Assistant
 * - Drug Interactions (Requirements: 10.5)
 * - Customer Health Profiles
 * - Symptom Analysis Cache
 * - Drug Recognition Cache
 * - Prescription OCR Results
 * - Pharmacy Ghost Learning
 * - Consultation Stages
 * - Pharmacy Context Keywords
 * - Consultation Analytics
 * 
 * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>💊 Vibe Selling OS v2 Migration (Pharmacy Edition)</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Define new tables to check (including drug_interactions for Requirements 10.5)
    $vibeTables = [
        'drug_interactions',
        'vibe_selling_settings',
        'customer_health_profiles',
        'symptom_analysis_cache',
        'drug_recognition_cache',
        'prescription_ocr_results',
        'pharmacy_ghost_learning',
        'consultation_stages',
        'pharmacy_context_keywords',
        'consultation_analytics'
    ];
    
    // Check if tables already exist
    echo "📋 Checking existing Vibe Selling v2 tables:\n";
    foreach ($vibeTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "⚠️ Table '$table' already exists\n";
        } else {
            echo "➡️ Table '$table' will be created\n";
        }
    }
    echo "\n";
    
    $success = 0;
    $skipped = 0;
    $errors = 0;
    
    echo "🔄 Running Vibe Selling v2 migration...\n\n";

    // =====================================================
    // Part 1: Create new tables
    // =====================================================
    $sqlFile = __DIR__ . '/../database/migration_vibe_selling_v2.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Extract CREATE TABLE statements
    preg_match_all('/CREATE TABLE IF NOT EXISTS[^;]+;/s', $sql, $matches);
    
    echo "📝 Creating Vibe Selling v2 tables:\n";
    foreach ($matches[0] as $createStmt) {
        try {
            $db->exec($createStmt);
            // Extract table name for display
            preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/', $createStmt, $tableMatch);
            $tableName = $tableMatch[1] ?? 'unknown';
            echo "✅ Created table: $tableName\n";
            $success++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/', $createStmt, $tableMatch);
                $tableName = $tableMatch[1] ?? 'unknown';
                echo "⚠️ Skipped (already exists): $tableName\n";
                $skipped++;
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n";
    
    // =====================================================
    // Part 2: Insert default context keywords
    // =====================================================
    echo "📝 Inserting default pharmacy context keywords:\n";
    
    // Extract INSERT statements
    preg_match_all('/INSERT INTO[^;]+;/s', $sql, $insertMatches);
    
    foreach ($insertMatches[0] as $insertStmt) {
        try {
            $db->exec($insertStmt);
            echo "✅ Inserted default context keywords\n";
            $success++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠️ Skipped (keywords already exist)\n";
                $skipped++;
            } else {
                echo "❌ Error inserting keywords: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "✅ Success: $success operations\n";
    if ($skipped > 0) {
        echo "⚠️ Skipped: $skipped operations\n";
    }
    if ($errors > 0) {
        echo "❌ Errors: $errors operations\n";
    }
    echo "========================================\n";

    
    // =====================================================
    // Part 3: Verify tables
    // =====================================================
    echo "\n📋 Verifying Vibe Selling v2 tables:\n";
    foreach ($vibeTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            echo "✅ Table '$table' exists ($count rows)\n";
        } else {
            echo "❌ Table '$table' NOT found\n";
        }
    }
    
    // Show customer_health_profiles structure
    echo "\n📋 Customer Health Profiles structure:\n";
    try {
        $stmt = $db->query("DESCRIBE customer_health_profiles");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - {$row['Field']}: {$row['Type']}\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ Could not describe table: " . $e->getMessage() . "\n";
    }
    
    // Show consultation_stages enum values
    echo "\n📋 Consultation Stage values:\n";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM consultation_stages LIKE 'stage'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
            if (isset($matches[1])) {
                $values = explode("','", $matches[1]);
                foreach ($values as $val) {
                    echo "   - $val\n";
                }
            }
        }
    } catch (PDOException $e) {
        echo "   ⚠️ Could not check enum values: " . $e->getMessage() . "\n";
    }
    
    // Show pharmacy_context_keywords widget types
    echo "\n📋 Widget Types available:\n";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM pharmacy_context_keywords LIKE 'widget_type'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
            if (isset($matches[1])) {
                $values = explode("','", $matches[1]);
                foreach ($values as $val) {
                    echo "   - $val\n";
                }
            }
        }
    } catch (PDOException $e) {
        echo "   ⚠️ Could not check enum values: " . $e->getMessage() . "\n";
    }
    
    // Show default keywords count
    echo "\n📋 Default Context Keywords:\n";
    try {
        $stmt = $db->query("SELECT keyword_type, COUNT(*) as cnt FROM pharmacy_context_keywords GROUP BY keyword_type");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - {$row['keyword_type']}: {$row['cnt']} keywords\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ Could not count keywords: " . $e->getMessage() . "\n";
    }
    
    // Verify drug_interactions table (Requirements 10.5)
    echo "\n📋 Drug Interactions table (Requirements 10.5):\n";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'drug_interactions'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $db->query("SELECT COUNT(*) FROM drug_interactions");
            $count = $countStmt->fetchColumn();
            echo "   ✅ Table exists with $count interactions\n";
            
            // Show severity distribution
            $stmt = $db->query("SELECT severity, COUNT(*) as cnt FROM drug_interactions GROUP BY severity ORDER BY FIELD(severity, 'contraindicated', 'severe', 'moderate', 'mild')");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "   - {$row['severity']}: {$row['cnt']} interactions\n";
            }
        } else {
            echo "   ❌ Table NOT found\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ Could not check drug_interactions: " . $e->getMessage() . "\n";
    }
    
    // Verify indexes
    echo "\n📋 Indexes on customer_health_profiles:\n";
    try {
        $stmt = $db->query("SHOW INDEX FROM customer_health_profiles");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - {$row['Key_name']} ({$row['Column_name']})\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ Could not check indexes: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Vibe Selling OS v2 Migration completed!\n";
    echo "\n<a href='../inbox.php'>👉 Go to Inbox</a>\n";
    echo "<a href='../inbox-v2.php'>👉 Go to Inbox v2 (after implementation)</a>\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
