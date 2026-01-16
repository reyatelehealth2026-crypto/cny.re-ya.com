# Quick Reply Guide - คู่มือการใช้งาน Quick Reply

## ✅ Quick Reply ทำงานได้แล้ว!

Quick Reply ใน Auto-Reply ทำงานได้เต็มรูปแบบแล้ว ไม่ต้องแก้ไขอะไรเพิ่มเติม

## 🎯 วิธีใช้งาน

### 1. สร้าง Auto-Reply Rule พร้อม Quick Reply
1. ไปที่ `auto-reply.php` (Auto-Reply Settings)
2. สร้าง rule ใหม่หรือแก้ไข rule เดิม
3. ในช่อง "Quick Reply" ใส่ JSON format:

```json
[
  {
    "type": "message",
    "label": "ใช่",
    "text": "ใช่ครับ ต้องการ"
  },
  {
    "type": "message",
    "label": "ไม่",
    "text": "ไม่ครับ ขอบคุณ"
  },
  {
    "type": "uri",
    "label": "ดูเพิ่มเติม",
    "uri": "https://example.com"
  }
]
```

### 2. ทดสอบ
1. ส่งคำที่ตรงกับ keyword ไปยัง LINE bot
2. Bot จะตอบกลับพร้อมปุ่ม Quick Reply ด้านล่างข้อความ
3. กดปุ่มเพื่อทดสอบ

### 3. ตรวจสอบ
เปิด `install/test_quick_reply.php` เพื่อดู:
- รายการ Auto-Reply rules ที่มี Quick Reply
- จำนวนปุ่มในแต่ละ rule
- LINE API format ที่ถูกส่งไป

## 📋 Quick Reply Button Types

### 1. Message Button (ส่งข้อความ)
```json
{
  "type": "message",
  "label": "ข้อความที่แสดงบนปุ่ม",
  "text": "ข้อความที่จะส่งเมื่อกดปุ่ม"
}
```

### 2. URI Button (เปิดลิงก์)
```json
{
  "type": "uri",
  "label": "เปิดเว็บไซต์",
  "uri": "https://example.com"
}
```

### 3. Postback Button (ส่งข้อมูลแบบซ่อน)
```json
{
  "type": "postback",
  "label": "เลือก",
  "data": "action=select&id=123",
  "displayText": "เลือกแล้ว"
}
```

### 4. Datetime Picker (เลือกวันเวลา)
```json
{
  "type": "datetimepicker",
  "label": "เลือกวันที่",
  "data": "action=date",
  "mode": "date"
}
```

### 5. Camera Button (เปิดกล้อง)
```json
{
  "type": "camera",
  "label": "ถ่ายรูป"
}
```

### 6. Camera Roll Button (เลือกรูปจากแกลเลอรี่)
```json
{
  "type": "cameraRoll",
  "label": "เลือกรูป"
}
```

### 7. Location Button (แชร์ตำแหน่ง)
```json
{
  "type": "location",
  "label": "แชร์ตำแหน่ง"
}
```

## 🔧 Technical Details

### Code Flow
1. User ส่งข้อความมา → `webhook.php` รับ
2. `checkAutoReply($db, $messageText, $lineAccountId)` ตรวจสอบ keyword
3. ถ้า match → สร้าง message พร้อม Quick Reply structure
4. ส่งไปยัง LINE API ด้วย `$line->replyMessage($replyToken, [$autoReply])`
5. LINE แสดง Quick Reply buttons ให้ user

### Quick Reply Structure in Database
```sql
-- auto_replies table
quick_reply TEXT  -- JSON array of button objects
```

### Quick Reply Structure in LINE API
```json
{
  "type": "text",
  "text": "ข้อความตอบกลับ",
  "quickReply": {
    "items": [
      {
        "type": "action",
        "action": {
          "type": "message",
          "label": "ใช่",
          "text": "ใช่ครับ"
        }
      }
    ]
  }
}
```

## ✅ Validation & Error Handling

### ปัญหาที่แก้ไขแล้ว:
1. ✅ **"Undefined array key 'label'"** - ข้ามปุ่มที่ไม่มี label
2. ✅ **URI button without uri** - ข้ามปุ่มที่ไม่มี uri
3. ✅ **Message type detection** - บันทึก message_type ถูกต้อง

### Code Location:
- `webhook.php` lines 1690-1795: Quick Reply processing
- `webhook.php` line 1028: Send to LINE API
- `webhook.php` line 1042: Save to database

## 📊 Limitations

### LINE API Limits:
- สูงสุด **13 ปุ่ม** ต่อ Quick Reply
- Label สูงสุด **20 ตัวอักษร**
- Text/Data สูงสุด **300 ตัวอักษร**

### Best Practices:
- ใช้ label สั้นๆ กระชับ
- จัดเรียงปุ่มตามความสำคัญ
- ใช้ icon emoji เพื่อให้เข้าใจง่าย
- ทดสอบบนมือถือจริง

## 🧪 Testing

### Test Script:
```bash
# Open in browser
http://your-domain.com/install/test_quick_reply.php
```

### Manual Test:
1. สร้าง auto-reply rule ด้วย keyword "test"
2. เพิ่ม Quick Reply buttons
3. ส่ง "test" ไปยัง LINE bot
4. ตรวจสอบว่าปุ่มแสดงถูกต้อง
5. กดปุ่มเพื่อทดสอบ action

## 📝 Examples

### Example 1: Yes/No Question
```json
[
  {"type": "message", "label": "✅ ใช่", "text": "ใช่ครับ"},
  {"type": "message", "label": "❌ ไม่", "text": "ไม่ครับ"}
]
```

### Example 2: Product Categories
```json
[
  {"type": "message", "label": "💊 ยา", "text": "ดูยา"},
  {"type": "message", "label": "🧴 เครื่องสำอาง", "text": "ดูเครื่องสำอาง"},
  {"type": "message", "label": "🍼 ของใช้เด็ก", "text": "ดูของใช้เด็ก"}
]
```

### Example 3: Contact Options
```json
[
  {"type": "uri", "label": "📞 โทร", "uri": "tel:0991915416"},
  {"type": "location", "label": "📍 ตำแหน่ง"},
  {"type": "uri", "label": "🌐 เว็บไซต์", "uri": "https://cny.re-ya.com"}
]
```

## 🚀 Status

✅ **Quick Reply ทำงานได้เต็มรูปแบบ**
- ส่งไปยัง LINE API ถูกต้อง
- รองรับทุก button types
- มี validation ป้องกัน error
- บันทึก message_type ถูกต้อง

ไม่ต้องแก้ไขอะไรเพิ่มเติม - พร้อมใช้งานได้เลย! 🎉
