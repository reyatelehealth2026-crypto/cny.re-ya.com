# LINE OA Manager

ระบบจัดการ LINE Official Account แบบครบวงจร รองรับหลายบัญชี LINE OA และหลายผู้ใช้

![LINE OA Manager](https://img.shields.io/badge/version-2.5-green) ![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-blue) ![License](https://img.shields.io/badge/license-MIT-orange)

## ✨ Features

### สำหรับ Admin
- 🔐 จัดการหลายบัญชี LINE OA ในระบบเดียว
- 👥 จัดการผู้ใช้ระบบ (Admin/User)
- 📊 Dashboard รวมสถิติทุกบัญชี
- 💬 ดูและตอบข้อความลูกค้า
- 📢 Broadcast ข้อความ
- 🤖 ตั้งค่า Auto-Reply
- 🛒 ระบบร้านค้าออนไลน์
- 📈 Analytics และรายงาน

### สำหรับ User (ผู้ใช้ทั่วไป)
- 🔗 เชื่อมต่อ LINE OA ของตัวเองได้
- 💬 จัดการข้อความลูกค้า
- 📢 ส่ง Broadcast
- 🤖 ตั้งค่าตอบกลับอัตโนมัติ
- 🛍️ จัดการสินค้าและคำสั่งซื้อ
- 👋 ตั้งค่าข้อความต้อนรับ
- 📊 ดูสถิติการใช้งาน

## 📋 Requirements

- PHP >= 7.4
- MySQL >= 5.7 หรือ MariaDB >= 10.2
- Extensions: PDO, PDO_MySQL, cURL, JSON, Mbstring
- Web Server: Apache หรือ Nginx
- SSL Certificate (HTTPS) - จำเป็นสำหรับ LINE Webhook

## 🚀 Installation

### วิธีที่ 1: ใช้ Installation Wizard (แนะนำ)

1. **อัพโหลดไฟล์ไปยัง Web Server**
   ```bash
   # Clone หรือ Download แล้ว Upload ไปยัง public_html หรือ www
   ```

2. **เปิดเบราว์เซอร์ไปที่**
   ```
   https://yourdomain.com/install/
   ```

3. **ทำตามขั้นตอนใน Installation Wizard**
   - ตรวจสอบ Requirements
   - กรอกข้อมูลฐานข้อมูล
   - ตั้งค่าระบบ
   - สร้างบัญชี Admin
   - ติดตั้ง

4. **ลบโฟลเดอร์ `install`** หลังติดตั้งเสร็จ

### วิธีที่ 2: ติดตั้งแบบ Manual

1. **สร้างฐานข้อมูล**
   ```sql
   CREATE DATABASE line_oa_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import Schema**
   ```bash
   mysql -u username -p line_oa_manager < database/schema.sql
   mysql -u username -p line_oa_manager < database/migration_admin_users.sql
   ```

3. **แก้ไขไฟล์ config/config.php**
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'line_oa_manager');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   
   define('APP_NAME', 'LINE OA Manager');
   define('APP_URL', 'https://yourdomain.com');
   define('BASE_URL', 'https://yourdomain.com');
   ```

4. **ตั้งค่า Permissions**
   ```bash
   chmod 755 config/
   chmod 755 uploads/
   chmod -R 755 uploads/products/
   chmod -R 755 uploads/slips/
   ```

5. **สร้างบัญชี Admin** (รหัสผ่าน: admin123)
   ```sql
   INSERT INTO admin_users (username, email, password, display_name, role) 
   VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');
   ```

## 🔧 Configuration

### ตั้งค่า LINE Messaging API

1. ไปที่ [LINE Developers Console](https://developers.line.biz/console/)
2. สร้าง Provider และ Channel (Messaging API)
3. คัดลอก **Channel ID**, **Channel Secret**
4. สร้าง **Channel Access Token**
5. ตั้งค่า **Webhook URL**:
   ```
   https://yourdomain.com/webhook.php?account=YOUR_ACCOUNT_ID
   ```
6. เปิด **Use webhook** และปิด **Auto-reply messages**

### ตั้งค่า Webhook (สำคัญ!)

ใน LINE Developers Console > Messaging API:
- Webhook URL: `https://yourdomain.com/webhook.php?account=1`
- Use webhook: **Enabled**
- Auto-reply messages: **Disabled**
- Greeting messages: **Disabled**

## 📁 Directory Structure

```
line-oa-manager/
├── api/                    # API endpoints
├── auth/                   # Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── setup-account.php
├── classes/                # PHP Classes
│   ├── LineAPI.php
│   ├── LineAccountManager.php
│   └── ...
├── config/                 # Configuration files
│   ├── config.php
│   └── database.php
├── cron/                   # Cron jobs
├── database/               # SQL migrations
├── includes/               # Header, Footer, Auth
├── install/                # Installation wizard
├── shop/                   # Shop management (Admin)
├── uploads/                # Uploaded files
├── user/                   # User panel pages
├── index.php               # Admin Dashboard
├── webhook.php             # LINE Webhook handler
└── README.md
```

## 👤 User Roles

| Role | Description |
|------|-------------|
| **Admin** | จัดการทุกอย่าง, เข้าถึงทุกบัญชี LINE |
| **User** | ใช้งานได้ 1 บัญชี LINE, เชื่อมต่อ LINE OA ของตัวเอง |

## 🔐 Default Login

หลังติดตั้งผ่าน Wizard จะใช้ข้อมูลที่กรอกไว้

หากติดตั้งแบบ Manual:
- **Username:** admin
- **Password:** admin123

⚠️ **กรุณาเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบ!**

## 📱 URLs

| URL | Description |
|-----|-------------|
| `/auth/login.php` | หน้าเข้าสู่ระบบ |
| `/auth/register.php` | หน้าสมัครสมาชิก |
| `/index.php` | Admin Dashboard |
| `/user/dashboard.php` | User Dashboard |
| `/admin-users.php` | จัดการผู้ใช้ระบบ |
| `/line-accounts.php` | จัดการบัญชี LINE |

## 📱 PWA (Progressive Web App)

ระบบรองรับการติดตั้งเป็นแอปบนมือถือ (PWA)

### วิธีติดตั้งบนมือถือ

**Android (Chrome):**
1. เปิดเว็บไซต์ใน Chrome
2. กดเมนู (⋮) > "Add to Home screen" หรือ "Install app"
3. กด "Install"

**iOS (Safari):**
1. เปิดเว็บไซต์ใน Safari
2. กดปุ่ม Share (□↑)
3. เลื่อนลงแล้วกด "Add to Home Screen"
4. กด "Add"

### สร้าง Icons

```bash
# ไปที่โฟลเดอร์ icons
cd assets/icons

# รัน script สร้าง placeholder icons
php create_placeholder.php

# หรือใช้ PWA Builder (แนะนำ)
# https://www.pwabuilder.com/imageGenerator
```

### Features ของ PWA
- ✅ ติดตั้งลงหน้าจอหลักได้
- ✅ ทำงานแบบ Offline (บางส่วน)
- ✅ Push Notifications (ต้องตั้งค่าเพิ่ม)
- ✅ รองรับ iOS และ Android

## 🛠️ Troubleshooting

### Webhook ไม่ทำงาน
1. ตรวจสอบว่า URL เป็น HTTPS
2. ตรวจสอบ Channel Secret ถูกต้อง
3. ดู error log ใน `webhook.php`

### ไม่สามารถส่งข้อความได้
1. ตรวจสอบ Channel Access Token
2. ตรวจสอบว่า Token ยังไม่หมดอายุ
3. ทดสอบการเชื่อมต่อในหน้า LINE Accounts

### Upload รูปไม่ได้
1. ตรวจสอบ permission ของโฟลเดอร์ `uploads/`
2. ตรวจสอบ `upload_max_filesize` ใน php.ini

## 📄 License

MIT License - ใช้งานได้ฟรี ทั้งส่วนตัวและเชิงพาณิชย์

## 🤝 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ สามารถเปิด Issue ได้

---

Made with ❤️ for LINE Official Account Management
