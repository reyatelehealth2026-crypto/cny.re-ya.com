# Reply Token Storage Fix - Summary

## สิ่งที่ทำไปแล้ว ✅

### 1. แก้ไขโค้ดใน webhook.php
**ปัญหาเดิม:**
- ❌ Error ถูก ignore ไปโดยไม่มี log (`// Ignore error`)
- ❌ เวลาหมดอายุผิด (ตั้งเป็น 19 นาที แต่ LINE token หมดอายุใน 1 นาที)
- ❌ มีการ query `SHOW COLUMNS` ทุกครั้งที่มี webhook (ช้า)

**แก้ไขแล้ว:**
- ✅ เพิ่ม error logging เพื่อดูว่าเกิดอะไรขึ้นถ้า save ไม่สำเร็จ
- ✅ แก้เวลาหมดอายุเป็น 50 วินาที (ถูกต้องตาม LINE spec)
- ✅ ลบ column check ออก (ประหยัด performance)
- ✅ เพิ่ม log เมื่อ save สำเร็จ

**โค้ดใหม่:**
```php
// บันทึก reply_token ใน users table (หมดอายุใน 50 วินาที - LINE tokens expire in 1 minute)
if ($replyToken) {
    try {
        $expires = date('Y-m-d H:i:s', time() + 50); // หมดอายุใน 50 วินาที (เผื่อ delay)
        $stmt = $db->prepare("UPDATE users SET reply_token = ?, reply_token_expires = ? WHERE id = ?");
        $stmt->execute([$replyToken, $expires, $user['id']]);
        error_log("Reply token saved for user {$user['id']}, expires: {$expires}");
    } catch (Exception $e) {
        error_log('Reply token save failed: ' . $e->getMessage());
        error_log('User ID: ' . ($user['id'] ?? 'unknown') . ', Token: ' . substr($replyToken, 0, 20));
    }
}
```

### 2. สร้าง Diagnostic Script
**ไฟล์:** `install/check_reply_token_storage.php`

**ตรวจสอบ:**
- ✓ Column มีอยู่ในฐานข้อมูลหรือไม่
- ✓ มี reply_token ที่ถูก save ไว้หรือไม่
- ✓ Token หมดอายุหรือยัง
- ✓ Webhook มีการทำงานหรือไม่
- ✓ Messages ล่าสุดมี token หรือไม่

**วิธีใช้:**
```
https://emp.re-ya.net/install/check_reply_token_storage.php
```

### 3. สร้างเอกสารวินิจฉัย
**ไฟล์:** `install/REPLY_TOKEN_STORAGE_DIAGNOSIS.md`

**เนื้อหา:**
- อธิบายปัญหาและสาเหตุที่เป็นไปได้
- วิธีตรวจสอบและแก้ไข
- SQL queries สำหรับ debug
- แนวทางการทดสอบ

### 4. Deploy ไปยัง Production
```bash
✅ Committed: 3 files changed, 592 insertions(+), 9 deletions(-)
✅ Pushed to: emp.re-ya.net
✅ Auto-deployed via cPanel
```

## ขั้นตอนถัดไป 🔍

### 1. ทดสอบว่า Token ถูก Save หรือไม่
```bash
# 1. ส่งข้อความจาก LINE app
# 2. เปิด diagnostic script ทันที
https://emp.re-ya.net/install/check_reply_token_storage.php

# 3. ดูว่ามี token ใน users table หรือไม่
```

### 2. ตรวจสอบ Error Log
```bash
# ดู error log ว่ามี message อะไร
tail -f ~/public_html/emp.re-ya.net/error_log | grep -i "reply token"
```

**ถ้า save สำเร็จ จะเห็น:**
```
Reply token saved for user 123, expires: 2026-01-18 15:30:45
```

**ถ้า save ไม่สำเร็จ จะเห็น:**
```
Reply token save failed: [error message]
User ID: 123, Token: abcd1234...
```

### 3. ตรวจสอบด้วย SQL
```sql
-- ดู token ล่าสุด
SELECT 
    id,
    display_name,
    reply_token,
    reply_token_expires,
    CASE 
        WHEN reply_token_expires > NOW() THEN 'Valid'
        ELSE 'Expired'
    END as status,
    TIMESTAMPDIFF(SECOND, NOW(), reply_token_expires) as seconds_left
FROM users 
WHERE reply_token IS NOT NULL
ORDER BY reply_token_expires DESC
LIMIT 10;

-- ดู messages ล่าสุดที่มี token
SELECT 
    id,
    user_id,
    message_type,
    reply_token IS NOT NULL as has_token,
    created_at
FROM messages 
WHERE direction = 'incoming'
ORDER BY created_at DESC
LIMIT 10;
```

## สาเหตุที่เป็นไปได้ถ้ายังไม่ทำงาน

### Scenario A: Column ไม่มีในฐานข้อมูล
**อาการ:**
- Diagnostic แสดง "No reply_token columns found"

**แก้ไข:**
```sql
-- เพิ่ม columns เอง
ALTER TABLE users ADD COLUMN reply_token VARCHAR(255);
ALTER TABLE users ADD COLUMN reply_token_expires DATETIME;
```

### Scenario B: Webhook ไม่ได้รับ Token จาก LINE
**อาการ:**
- Columns มี แต่ไม่มี token เลย
- Messages table ก็ไม่มี token

**ตรวจสอบ:**
1. LINE Developers Console → Messaging API → Webhook URL ถูกต้องหรือไม่
2. Webhook มี error หรือไม่ (ดู error_log)
3. LINE ส่ง webhook มาหรือไม่ (ดู LINE Console → Webhook logs)

### Scenario C: Database Permission Issue
**อาการ:**
- Error log แสดง "UPDATE failed" หรือ "Access denied"

**แก้ไข:**
```sql
-- ให้สิทธิ์ UPDATE กับ users table
GRANT UPDATE ON database.users TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

### Scenario D: User ID ไม่ตรงกัน
**อาการ:**
- Token ถูกส่งมา แต่ UPDATE ไม่สำเร็จเพราะหา user ไม่เจอ

**ตรวจสอบ:**
```sql
-- ดูว่า user มีอยู่จริงหรือไม่
SELECT id, line_user_id, display_name 
FROM users 
WHERE line_user_id = 'U1234567890abcdef';
```

## ข้อมูลเพิ่มเติม

### LINE Reply Token Specs
- ⏱️ หมดอายุใน **1 นาที** หลังจาก event เกิด
- 🔒 ใช้ได้ **ครั้งเดียว** เท่านั้น
- 💰 ไม่เสียค่าใช้จ่าย (ต่างจาก Push API)
- 📝 ต้องใช้ภายใน 1 นาทีหลังจากได้รับ

### Push API vs Reply API
| Feature | Reply API | Push API |
|---------|-----------|----------|
| Cost | ฟรี | นับ quota |
| Speed | เร็วกว่า | ช้ากว่า |
| Timing | ภายใน 1 นาที | ได้ตลอดเวลา |
| Usage | ใช้ได้ครั้งเดียว | ใช้ได้หลายครั้ง |

### แนะนำ: Implement Fallback
ถ้า reply_token หมดอายุหรือไม่มี ให้ใช้ Push API แทนอัตโนมัติ:

```php
function sendLineMessage($line, $db, $userId, $messages) {
    // ลอง Reply API ก่อน
    $stmt = $db->prepare("
        SELECT reply_token 
        FROM users 
        WHERE id = ? 
        AND reply_token IS NOT NULL 
        AND reply_token_expires > NOW()
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['reply_token']) {
        try {
            $line->replyMessage($user['reply_token'], $messages);
            // ลบ token หลังใช้ (ใช้ได้ครั้งเดียว)
            $db->prepare("UPDATE users SET reply_token = NULL WHERE id = ?")->execute([$userId]);
            error_log("Used Reply API for user {$userId}");
            return true;
        } catch (Exception $e) {
            error_log('Reply API failed: ' . $e->getMessage());
        }
    }
    
    // ถ้า Reply API ไม่ได้ ใช้ Push API
    $lineUserId = getUserLineId($db, $userId);
    if ($lineUserId) {
        $line->pushMessage($lineUserId, $messages);
        error_log("Used Push API for user {$userId}");
        return true;
    }
    
    return false;
}
```

## ไฟล์ที่เกี่ยวข้อง
- ✅ `webhook.php` - แก้ไขแล้ว (lines 818-830)
- ✅ `install/check_reply_token_storage.php` - สร้างใหม่
- ✅ `install/REPLY_TOKEN_STORAGE_DIAGNOSIS.md` - สร้างใหม่
- 📄 `database/schema_complete.sql` - มี columns อยู่แล้ว
- 📄 `classes/LineAPI.php` - อาจต้องเพิ่ม fallback logic

## สรุป
✅ แก้ไขโค้ดเรียบร้อย - เพิ่ม error logging และแก้เวลาหมดอายุ
✅ สร้าง diagnostic tools - ตรวจสอบได้ง่าย
✅ Deploy แล้ว - รอทดสอบ
⏭️ ขั้นตอนถัดไป - ส่งข้อความทดสอบและดู diagnostic

**ทดสอบเลย:** ส่งข้อความจาก LINE app แล้วเปิด `https://emp.re-ya.net/install/check_reply_token_storage.php` ดูผลทันที!
