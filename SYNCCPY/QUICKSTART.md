# 🚀 CNY Sync System - Quick Start Guide

ทำตามขั้นตอนนี้เพื่อเริ่มใช้งานภายใน 5 นาที!

---

## ⚡ Step-by-Step Installation

### 1️⃣ Import Database Schema (1 นาที)

```bash
mysql -u your_user -p your_database < database/sync_schema.sql
```

หรือใช้ phpMyAdmin:
- เปิด phpMyAdmin
- เลือก database
- คลิก "Import"
- เลือกไฟล์ `database/sync_schema.sql`
- คลิก "Go"

---

### 2️⃣ ปรับแต่ง Paths (1 นาที)

แก้ไฟล์ `public/sync_api.php` บรรทัดที่ 10-14:

```php
// ปรับ path ให้ตรงกับโครงสร้างของคุณ
require_once __DIR__ . '/../../config/config.php';           // ← แก้ path นี้
require_once __DIR__ . '/../../config/database.php';         // ← แก้ path นี้
require_once __DIR__ . '/../../classes/CnyPharmacyAPI.php';  // ← แก้ path นี้
```

แก้ไฟล์ `public/sync_worker.php` บรรทัดที่ 29-33 เช่นกัน

---

### 3️⃣ ทดสอบ API Connection (30 วินาที)

```bash
php -r "
require 'config/config.php';
require 'config/database.php';
require 'classes/CnyPharmacyAPI.php';

\$db = Database::getInstance()->getConnection();
\$api = new CnyPharmacyAPI(\$db);
\$result = \$api->testConnection();

echo \$result['success'] ? '✓ API OK' : '✗ API Failed';
"
```

ผลลัพธ์ควรเป็น: `✓ API OK`

---

### 4️⃣ สร้าง Batch แรก (1 นาที)

**วิธีที่ 1: ใช้ Migration Script (แนะนำ)**
```bash
php migrate_to_queue.php
```

Script นี้จะ:
- ✅ สร้าง tables
- ✅ ดึง SKU ทั้งหมดจาก API
- ✅ เพิ่มเข้า queue อัตโนมัติ

**วิธีที่ 2: ใช้ Dashboard**
1. เปิด browser: `http://localhost/path/to/public/sync_dashboard.php`
2. คลิก "Create Batch"
3. เลือก "Fetch from API"
4. คลิก "Create Batch"

---

### 5️⃣ เริ่ม Sync! (1 นาที)

**วิธีที่ 1: รัน Worker ผ่าน CLI (แนะนำ)**
```bash
php public/sync_worker.php
```

**วิธีที่ 2: รัน Worker แบบ Background**
```bash
# Linux/Mac
nohup php public/sync_worker.php > worker.log 2>&1 &

# ดู log
tail -f worker.log
```

**วิธีที่ 3: รันผ่าน Cron (Production)**
```bash
# เพิ่มใน crontab
*/5 * * * * cd /path/to/project && php public/sync_worker.php --mode=batch --batch-size=50
```

---

## 📊 ดู Progress

### Dashboard (แบบ Real-time)
เปิด browser:
```
http://localhost/path/to/public/sync_dashboard.php
```

### CLI (แบบ Live)
```bash
watch -n 2 'mysql -u user -p database -e "SELECT * FROM v_queue_summary"'
```

### SQL (แบบ Manual)
```sql
-- สรุป queue status
SELECT * FROM v_queue_summary;

-- ดู progress
SELECT 
    (SELECT COUNT(*) FROM sync_queue WHERE status = 'completed') as completed,
    (SELECT COUNT(*) FROM sync_queue) as total,
    ROUND((SELECT COUNT(*) FROM sync_queue WHERE status = 'completed') / 
          (SELECT COUNT(*) FROM sync_queue) * 100, 2) as percent
FROM dual;
```

---

## 🎯 Common Commands

### รัน Worker แบบต่างๆ

```bash
# รันจนกว่า queue จะหมด
php public/sync_worker.php

# รัน 1 batch แล้วหยุด
php public/sync_worker.php --mode=batch

# จำกัด jobs
php public/sync_worker.php --max-jobs=100

# ปรับ batch size
php public/sync_worker.php --batch-size=5
```

### จัดการ Queue

```bash
# ดูสถิติ
curl http://localhost/path/to/public/sync_api.php?action=stats

# ล้าง failed jobs
curl -X POST http://localhost/path/to/public/sync_api.php?action=clear_queue

# Cleanup stuck jobs
curl -X POST http://localhost/path/to/public/sync_api.php?action=cleanup_stuck
```

### สร้าง Batch ใหม่

```bash
# จาก API (SKU ทั้งหมด)
curl -X POST http://localhost/path/to/public/sync_api.php?action=create_batch \
  -H "Content-Type: application/json" \
  -d '{"name": "Full Sync", "source": "api", "priority": 5}'

# จาก SKU list
curl -X POST http://localhost/path/to/public/sync_api.php?action=create_batch \
  -H "Content-Type: application/json" \
  -d '{"name": "Partial", "source": "manual", "skus": ["0001","0002"], "priority": 3}'
```

---

## 🐛 Troubleshooting

### ❌ Worker ไม่ทำงาน

**ตรวจสอบ:**
```bash
# 1. มี pending jobs หรือไม่?
mysql -u user -p database -e "SELECT COUNT(*) FROM sync_queue WHERE status='pending'"

# 2. Worker รันอยู่หรือไม่?
ps aux | grep sync_worker

# 3. ดู error log
tail -f worker.log
```

**แก้ไข:**
```bash
# Cleanup stuck jobs
curl -X POST http://localhost/path/to/public/sync_api.php?action=cleanup_stuck

# เริ่มใหม่
php public/sync_worker.php
```

### ❌ Memory Error

**แก้ที่:** `config/sync_config.php`
```php
public const WORKER_MEMORY_LIMIT = '512M';  // เพิ่ม memory
public const BATCH_SIZE = 5;                 // ลด batch size
```

### ❌ API Timeout

**แก้ที่:** `config/sync_config.php`
```php
public const MAX_REQUESTS_PER_MINUTE = 10;   // ลด requests
public const DELAY_BETWEEN_JOBS_MS = 1000;   // เพิ่ม delay
public const API_TIMEOUT = 60;               // เพิ่ม timeout
```

---

## ✅ Verification Checklist

หลังจากรันเสร็จ ตรวจสอบว่า:

```sql
-- 1. มี jobs ใน queue
SELECT COUNT(*) FROM sync_queue;
-- ควรเห็นตัวเลข > 0

-- 2. มี jobs สำเร็จแล้ว
SELECT COUNT(*) FROM sync_queue WHERE status = 'completed';
-- ควรเพิ่มขึ้นเรื่อยๆ

-- 3. มีสินค้าใน products table
SELECT COUNT(*) FROM products WHERE sku IS NOT NULL;
-- ควรเท่ากับจำนวน completed jobs

-- 4. ไม่มี jobs ล้มเหลวมากเกินไป
SELECT COUNT(*) FROM sync_queue WHERE status = 'failed';
-- ควร < 5% ของ total
```

---

## 🎓 Next Steps

### สำหรับ Development
1. ดู logs: `tail -f worker.log`
2. Monitor dashboard: เปิด browser ดู real-time
3. Tune performance: ปรับ batch size และ rate limiting

### สำหรับ Production
1. ตั้ง Cron Job (ดู `PRODUCTION.md`)
2. ตั้ง Systemd Service (รัน worker แบบ daemon)
3. ตั้ง Monitoring & Alerts
4. ตั้ง Backup script

---

## 📚 Additional Resources

- **Full Documentation**: `README.md`
- **Production Guide**: `PRODUCTION.md`
- **Database Schema**: `database/sync_schema.sql`
- **Source Code**: `src/` directory

---

## 🆘 Need Help?

หากมีปัญหา:
1. ตรวจสอบ error log: `tail -f worker.log`
2. ดู database logs: `SELECT * FROM sync_logs ORDER BY created_at DESC LIMIT 50`
3. ตรวจสอบ configuration: `config/sync_config.php`

---

**Happy Syncing! 🎉**
