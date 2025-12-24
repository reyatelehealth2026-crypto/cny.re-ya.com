# ⚡ Quick Start Guide

## 5 นาทีติดตั้งระบบ LINE CRM Pharmacy

### Step 1: Upload Files
อัพโหลดไฟล์ทั้งหมดไปยัง server ของคุณ

### Step 2: เปิด Installation Wizard
```
https://yourdomain.com/v1/install/install_fresh.php
```

### Step 3: ทำตาม Wizard
1. ✅ ตรวจสอบ System Requirements
2. 🗄️ ใส่ข้อมูล Database
3. ⚙️ ตั้งค่า Application URL
4. 👤 สร้าง Admin Account
5. 🎉 เสร็จสิ้น!

### Step 4: ตั้งค่า LINE
1. ไปที่ [LINE Developers](https://developers.line.biz/)
2. สร้าง Messaging API Channel
3. ตั้งค่า Webhook URL: `https://yourdomain.com/v1/webhook.php`
4. คัดลอก Channel Secret และ Access Token
5. เพิ่มใน Admin > LINE Accounts

### Step 5: ลบ install folder
```bash
rm -rf install/
```

---

## 🔑 Default Login
- URL: `https://yourdomain.com/v1/`
- Username: (ที่ตั้งไว้ตอนติดตั้ง)
- Password: (ที่ตั้งไว้ตอนติดตั้ง)

## 📱 ทดสอบระบบ
1. เพิ่มเพื่อน LINE OA ของคุณ
2. ส่งข้อความ "สวัสดี"
3. ระบบควรบันทึกข้อความใน Admin Panel

## 🆘 มีปัญหา?
- ตรวจสอบ: `https://yourdomain.com/v1/install/check_system.php`
- ดู error_log ของ server
- อ่าน DEPLOY_GUIDE.md สำหรับรายละเอียดเพิ่มเติม
