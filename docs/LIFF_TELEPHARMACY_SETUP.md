# 🏥 LIFF Telepharmacy Setup Guide

## ภาพรวม

LIFF Telepharmacy เป็นระบบ Single Page Application (SPA) สำหรับร้านขายยาออนไลน์ผ่าน LINE ประกอบด้วย:

- 🛒 ร้านค้าออนไลน์พร้อมระบบตะกร้า
- 💊 ระบบตรวจสอบปฏิกิริยาระหว่างยา
- 📋 ระบบอนุมัติยาที่ต้องสั่งโดยเภสัชกร
- 📹 Video Call ปรึกษาเภสัชกร
- 🤖 AI Assistant สำหรับถามเรื่องยา
- 💳 บัตรสมาชิกและระบบสะสมแต้ม
- ⏰ ระบบเตือนทานยา

---

## 📋 ขั้นตอนการติดตั้ง

### 1. รัน Database Migration

เปิด browser และเข้าไปที่:
```
https://your-domain.com/install/run_liff_telepharmacy_migration.php
```

หรือรันผ่าน command line:
```bash
php install/run_liff_telepharmacy_migration.php
```

### 2. ตั้งค่า LIFF ID ใน LINE Developers Console

1. ไปที่ [LINE Developers Console](https://developers.line.biz/)
2. เลือก Provider และ Channel ของคุณ
3. ไปที่ **LIFF** tab
4. สร้าง LIFF App ใหม่หรือแก้ไขที่มีอยู่:
   - **Endpoint URL**: `https://your-domain.com/liff/index.php`
   - **Size**: `Full`
   - **Scope**: `profile`, `openid`
   - **Bot link feature**: `On (Aggressive)`

5. คัดลอก **LIFF ID** (เช่น `1234567890-abcdefgh`)

### 3. บันทึก LIFF ID ในระบบ

ไปที่หน้า Admin > LINE Accounts และใส่ LIFF ID ที่ได้

หรือรัน SQL:
```sql
UPDATE line_accounts 
SET liff_id = 'YOUR_LIFF_ID_HERE' 
WHERE id = 1;
```

### 4. ตั้งค่า Rich Menu (Optional)

สร้าง Rich Menu ที่มีปุ่มเปิด LIFF:
- **Action Type**: URI
- **URI**: `https://liff.line.me/YOUR_LIFF_ID`

---

## 🔗 วิธีเข้าใช้งาน

### ผ่าน LINE App (แนะนำ)
```
https://liff.line.me/YOUR_LIFF_ID
```

### ผ่าน Browser (Guest Mode)
```
https://your-domain.com/liff/index.php
```

### เข้าหน้าเฉพาะ
```
https://your-domain.com/liff/index.php?page=shop
https://your-domain.com/liff/index.php?page=orders
https://your-domain.com/liff/index.php?page=health-profile
```

---

## 📱 หน้าที่มีในระบบ

| Route | หน้า | คำอธิบาย |
|-------|------|----------|
| `/` หรือ `/home` | หน้าหลัก | Dashboard แบบ Telecare |
| `/shop` | ร้านค้า | สินค้าทั้งหมด |
| `/cart` | ตะกร้า | รายการสินค้าในตะกร้า |
| `/checkout` | ชำระเงิน | ฟอร์มสั่งซื้อ |
| `/orders` | ออเดอร์ | ประวัติการสั่งซื้อ |
| `/member` | บัตรสมาชิก | QR Code และข้อมูลสมาชิก |
| `/profile` | โปรไฟล์ | ข้อมูลส่วนตัว |
| `/video-call` | ปรึกษาเภสัชกร | Video Call |
| `/ai-assistant` | ผู้ช่วย AI | ถามเรื่องยา |
| `/health-profile` | ข้อมูลสุขภาพ | ประวัติการแพทย์ |
| `/medication-reminders` | เตือนทานยา | ตั้งเวลาเตือน |
| `/wishlist` | รายการโปรด | สินค้าที่ชอบ |
| `/notifications` | การแจ้งเตือน | ตั้งค่าการแจ้งเตือน |

---

## 🔧 API Endpoints

### Health Profile
```
GET  /api/health-profile.php?action=get&line_user_id=xxx
POST /api/health-profile.php (action: update_personal, add_allergy, add_medication)
```

### Prescription Approval
```
POST /api/prescription-approval.php?action=create
POST /api/prescription-approval.php?action=check_status
```

### Medication Reminders
```
GET  /api/medication-reminders.php?action=list&line_user_id=xxx
POST /api/medication-reminders.php (action: add, mark_taken)
```

### Drug Interactions
```
POST /api/drug-interactions.php?action=check
```

### LIFF Bridge
```
POST /api/liff-bridge.php (action: send_message, order_placed)
```

---

## 🎨 Customization

### เปลี่ยนสี Theme

แก้ไขไฟล์ `liff/assets/css/liff-app.css`:

```css
:root {
    --primary-color: #11B0A6;      /* สีหลัก */
    --primary-dark: #0D8A82;       /* สีหลักเข้ม */
    --primary-light: #E6F7F6;      /* สีหลักอ่อน */
    --secondary-color: #6366F1;    /* สีรอง */
    --accent-color: #F97316;       /* สีเน้น */
}
```

### เปลี่ยน Logo

ตั้งค่าใน Admin > Shop Settings หรือ:
```sql
UPDATE shop_settings SET shop_logo = 'https://your-domain.com/path/to/logo.png';
```

---

## 🔒 ระบบยาที่ต้องสั่งโดยเภสัชกร (Prescription)

### ตั้งค่าสินค้าเป็นยาที่ต้องสั่ง
```sql
UPDATE products SET is_prescription = 1 WHERE id = xxx;
```

### Flow การสั่งซื้อยา Rx
1. ลูกค้าเพิ่มยา Rx ลงตะกร้า
2. ระบบแสดง warning และบล็อกการ checkout
3. ลูกค้ากด "ปรึกษาเภสัชกร" → Video Call
4. เภสัชกรอนุมัติ → สร้าง approval record (หมดอายุ 24 ชม.)
5. ลูกค้าสามารถ checkout ได้

---

## ⚠️ ระบบตรวจสอบปฏิกิริยาระหว่างยา

### เพิ่มข้อมูลปฏิกิริยายา
```sql
INSERT INTO drug_interactions (drug1_id, drug2_id, severity, description, recommendation)
VALUES (1, 2, 'moderate', 'อาจทำให้ง่วงซึมมากขึ้น', 'หลีกเลี่ยงการขับรถ');
```

### Severity Levels
- `mild` - แจ้งเตือนเฉยๆ
- `moderate` - ต้อง acknowledge ก่อนเพิ่มลงตะกร้า
- `severe` - บล็อกการเพิ่ม ต้องปรึกษาเภสัชกร

---

## 📞 Support

หากพบปัญหาในการติดตั้ง:
1. ตรวจสอบ error_log ของ PHP
2. ตรวจสอบ Console ใน Browser (F12)
3. ตรวจสอบว่า LIFF ID ถูกต้อง
4. ตรวจสอบว่า Endpoint URL ตรงกับ domain

---

## 📝 Changelog

### Version 1.0
- Initial release
- SPA architecture with client-side routing
- Member card with QR code
- Shop with infinite scroll
- Cart and checkout
- Drug interaction checking
- Prescription approval system
- Video call consultation
- AI assistant integration
- Health profile management
- Medication reminders
