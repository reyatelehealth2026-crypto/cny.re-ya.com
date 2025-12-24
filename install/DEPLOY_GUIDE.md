# 🚀 LINE CRM Pharmacy - Deployment Guide

## ข้อกำหนดระบบ (Requirements)

### Server
- PHP 8.0+ (แนะนำ 8.1+)
- MySQL 5.7+ หรือ MariaDB 10.3+
- Apache/Nginx with mod_rewrite
- SSL Certificate (HTTPS required for LINE)
- Minimum 1GB RAM, 10GB Storage

### PHP Extensions
- pdo_mysql
- curl
- json
- mbstring
- openssl
- gd (สำหรับ image processing)

## 📁 โครงสร้างไฟล์

```
/public_html/v1/
├── api/                 # API endpoints
├── assets/              # CSS, JS, Images
├── auth/                # Authentication
├── classes/             # PHP Classes
├── config/              # Configuration files
│   ├── config.php       # Main config (สร้างจาก config.example.php)
│   ├── config.example.php # ตัวอย่าง config
│   └── database.php     # Database connection class
├── cron/                # Cron jobs
├── database/            # SQL migrations
│   ├── schema_complete.sql  # Complete database schema
│   └── migration_*.sql      # Individual migrations
├── docs/                # Documentation
├── includes/            # Shared includes
├── install/             # Installation scripts
│   ├── install_fresh.php    # Fresh installation wizard
│   ├── install_all.php      # Run all migrations
│   └── check_system.php     # System health check
├── modules/             # AI Chat modules
│   └── AIChat/          # AI Chatbot module
├── shop/                # Shop management
├── uploads/             # User uploads (chmod 755)
├── user/                # User panel
└── webhook.php          # LINE Webhook
```

## 🔧 ขั้นตอนการติดตั้ง

### วิธีที่ 1: Fresh Installation (แนะนำ)

1. **Upload Files**
   ```bash
   # อัพโหลดไฟล์ทั้งหมดไปยัง server
   # ตั้งค่า permission
   chmod 755 uploads/
   chmod 644 config/config.php
   ```

2. **เปิด Installation Wizard**
   ```
   https://yourdomain.com/v1/install/install_fresh.php
   ```

3. **ทำตามขั้นตอนใน Wizard**
   - ตรวจสอบ System Requirements
   - ใส่ข้อมูล Database
   - ตั้งค่า Application
   - สร้าง Admin Account

4. **ลบโฟลเดอร์ install/**
   ```bash
   rm -rf install/
   ```

### วิธีที่ 2: Manual Installation

1. **สร้าง Database**
   ```sql
   CREATE DATABASE linecrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import Schema**
   ```bash
   mysql -u username -p linecrm < database/schema_complete.sql
   ```

3. **สร้าง Config**
   ```bash
   cp config/config.example.php config/config.php
   # แก้ไขค่าใน config.php
   ```

4. **รัน Migrations**
   ```
   https://yourdomain.com/v1/install/install_all.php
   ```

5. **ตรวจสอบระบบ**
   ```
   https://yourdomain.com/v1/install/check_system.php
   ```

## 🔗 ตั้งค่า LINE Account

### 1. LINE Developers Console
1. ไปที่ https://developers.line.biz/
2. สร้าง Provider (ถ้ายังไม่มี)
3. สร้าง Messaging API Channel

### 2. ตั้งค่า Webhook
- Webhook URL: `https://yourdomain.com/v1/webhook.php`
- Use webhook: ✅ เปิด
- Auto-reply messages: ❌ ปิด
- Greeting messages: ❌ ปิด

### 3. คัดลอก Credentials
- Channel ID
- Channel Secret
- Channel Access Token (Long-lived)

### 4. เพิ่มใน Admin Panel
- ไปที่ Admin > LINE Accounts
- เพิ่มบัญชีใหม่
- ใส่ Channel Secret และ Access Token

## 📱 ตั้งค่า LIFF Apps

### LIFF Apps ที่ต้องสร้าง:

| Name | Endpoint URL | View Type |
|------|-------------|-----------|
| Main | /liff-main.php | full |
| Shop | /liff-shop.php | full |
| Register | /liff-register.php | tall |
| Checkout | /liff-checkout.php | full |
| My Orders | /liff-my-orders.php | full |
| Member Card | /liff-member-card.php | tall |

### วิธีสร้าง LIFF:
1. ไปที่ LINE Developers > Channel > LIFF
2. Add LIFF app
3. ใส่ Endpoint URL
4. เลือก View type
5. คัดลอก LIFF ID
6. ใส่ใน Admin > LIFF Settings

## 🤖 ตั้งค่า AI (Gemini)

1. ไปที่ https://aistudio.google.com/
2. สร้าง API Key
3. ใส่ใน Admin > AI Settings > Gemini API Key
4. เปิดใช้งาน Pharmacy AI Mode

### AI Commands:
- `/ai` - เข้าโหมด AI ทั่วไป
- `/mims` - เข้าโหมด MIMS Pharmacist AI
- `/triage` - เข้าโหมดซักประวัติ
- `/human` หรือ `/exit` - ออกจากโหมด AI

## ⏰ ตั้งค่า Cron Jobs

```bash
# Appointment Reminders (ทุก 15 นาที)
*/15 * * * * php /path/to/v1/cron/appointment_reminder.php

# Scheduled Reports (ทุกวัน 8:00)
0 8 * * * php /path/to/v1/cron/scheduled_reports.php

# Sync Worker (ทุก 5 นาที)
*/5 * * * * php /path/to/v1/cron/sync_worker.php

# Cleanup old logs (ทุกวัน 3:00)
0 3 * * * php /path/to/v1/cron/cleanup.php
```

## 🔐 Security Checklist

- [ ] เปลี่ยน default admin password
- [ ] ลบโฟลเดอร์ install/ หลังติดตั้งเสร็จ
- [ ] ตั้งค่า .htaccess ป้องกัน config folder
- [ ] เปิด HTTPS (บังคับสำหรับ LINE)
- [ ] ตั้งค่า CORS ให้ถูกต้อง
- [ ] ปิด PHP error display ใน production
- [ ] ตั้งค่า firewall
- [ ] Backup database เป็นประจำ

### .htaccess สำหรับ config folder:
```apache
# config/.htaccess
<Files "*">
    Order Deny,Allow
    Deny from all
</Files>
<Files "*.php">
    Order Allow,Deny
    Allow from all
</Files>
```

## 🔄 การอัพเดทระบบ

1. Backup database และไฟล์
2. อัพโหลดไฟล์ใหม่
3. รัน migrations ใหม่ (ถ้ามี)
4. Clear cache

## 🐛 Troubleshooting

### Webhook ไม่ทำงาน
- ตรวจสอบ SSL Certificate
- ตรวจสอบ error_log
- ทดสอบด้วย LINE Official Account Manager

### AI ไม่ตอบ
- ตรวจสอบ Gemini API Key
- ตรวจสอบ quota ของ API
- ดู dev_logs ในระบบ

### LIFF ไม่เปิด
- ตรวจสอบ LIFF ID
- ตรวจสอบ Endpoint URL
- ต้องเปิดใน LINE App เท่านั้น

## 📞 Support

หากมีปัญหาในการติดตั้ง:
1. ตรวจสอบ `install/check_system.php`
2. ดู error_log ของ server
3. ดู dev_logs ในระบบ (Admin > Dev Logs)
4. ติดต่อทีมพัฒนา
