# คู่มือติดตั้ง LINE CRM บน likesms.net/v1

## ปัญหาที่พบ
`.htaccess` ที่ root ของ `likesms.net` มี `RewriteBase /sms/` ทำให้ redirect ผิด

## ขั้นตอนการติดตั้ง

### 1. สร้างฐานข้อมูลใน cPanel
- เข้า cPanel → MySQL Databases
- สร้าง Database ใหม่ เช่น `likesmsn_linecrm`
- สร้าง User ใหม่ เช่น `likesmsn_linecrm`
- Add User to Database (All Privileges)

### 2. อัพโหลดไฟล์
- อัพโหลดไฟล์ทั้งหมดไปที่ `/public_html/v1/`
- หรือ Extract `LINECRM_v1.zip` ที่โฟลเดอร์ `/v1/`

### 3. สร้าง .htaccess สำหรับ /v1
**สำคัญมาก!** ต้องสร้างไฟล์ `.htaccess` ใหม่ในโฟลเดอร์ `/v1/`

Copy เนื้อหาจากไฟล์ `htaccess_v1.txt` ไปสร้างเป็น `/v1/.htaccess`

หรือสร้างไฟล์ `.htaccess` ใน `/v1/` ด้วยเนื้อหา:
```apache
RewriteEngine On
RewriteBase /v1/
AddDefaultCharset UTF-8
Options -Indexes
```

### 4. แก้ไข config.php
- Copy `config/config_likesms.php` เป็น `config/config.php`
- แก้ไขข้อมูลฐานข้อมูล:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'likesmsn_linecrm');    // ชื่อ DB ที่สร้าง
define('DB_USER', 'likesmsn_linecrm');    // username
define('DB_PASS', 'รหัสผ่านที่ตั้ง');      // password
```

### 5. เปิดหน้าติดตั้ง
เข้า: `https://likesms.net/v1/install/`

### 6. ตั้งค่า LINE Account
- เข้า `https://likesms.net/v1/line-accounts.php`
- เพิ่ม/แก้ไข LINE Account
- ใส่ LIFF ID ที่ Tab ขั้นสูง

### 7. ทดสอบ LIFF Main
เข้า: `https://likesms.net/v1/liff-main.php?debug=1`

## URLs สำคัญ
- หน้าหลัก: `https://likesms.net/v1/`
- ติดตั้ง: `https://likesms.net/v1/install/`
- LINE Accounts: `https://likesms.net/v1/line-accounts.php`
- LIFF Main: `https://likesms.net/v1/liff-main.php`
- Webhook: `https://likesms.net/v1/webhook.php`

## หมายเหตุ
- ถ้ายังเข้าไม่ได้ ให้ตรวจสอบว่า `.htaccess` ใน `/v1/` มี `RewriteBase /v1/` หรือยัง
- ถ้า redirect ไป `/sms/` แสดงว่า `.htaccess` ของ root ยังทำงานอยู่
