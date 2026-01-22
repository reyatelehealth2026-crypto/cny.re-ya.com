# แนวทางการอัพเกรดและปรับปรุงระบบ (Project Upgrade Guidelines)

เอกสารนี้สรุปแผนงานเชิงกลยุทธ์สำหรับการอัพเกรด LINE Telepharmacy Platform เป้าหมายคือการปรับปรุงการดูแลรักษา (Maintainability), ประสิทธิภาพ (Performance), และความสามารถในการขยายระบบ (Scalability) โดยไม่กระทบต่อการใช้งานปัจจุบัน

## 1. การปรับโครงสร้างสถาปัตยกรรม (Backend Architecture)
**สถานะปัจจุบัน:** ใช้รูปแบบ "Page Controller" (หนึ่งไฟล์ PHP ต่อหนึ่งหน้า เช่น `inbox-v2.php`, `index.php`) ซึ่งมีการปนกันระหว่าง Logic และการแสดงผล (HTML)
**เป้าหมาย:** เปลี่ยนเป็น Model-View-Controller (MVC) หรือโครงสร้างแบบ Modular

### แผนระยะสั้น (Short-term):
- **Standardize Autoloading:** จัดการ Class ทั้งหมดใน `classes/` และ `modules/` ให้โหลดผ่าน Composer (PSR-4)
- **Dependency Injection:** เลิกใช้ `global $db` และเปลี่ยนไปส่งตัวแปร Database ผ่าน Constructor แทน
- **Config Centralization:** รวบรวมค่าตั่งค่าจาก `config/config.php` และ `config/database.php` ให้เป็นระบบเดียว หรือใช้ `.env`

### แผนระยะยาว (Long-term):
- **Front Controller:** ตั้งค่าให้ Request ทั้งหมดวิ่งไปที่ `public/index.php` และใช้ Router (เช่น `FastRoute` หรือ `Symfony Routing`) ในการจัดการ
- **Extract Controllers:** ย้าย Logic จากไฟล์ เช่น `inbox-v2.php` ไปยัง `App\Controllers\InboxController`
- **Template Engine:** ใช้ Twig หรือสร้าง View Class อย่างง่าย เพื่อแยก HTML ออกจาก PHP อย่างเด็ดขาด

## 2. คุณภาพโค้ดและมาตรฐาน (Code Quality)
**สถานะปัจจุบัน:** รูปแบบการเขียนโค้ดหลากหลาย, มีการเขียน SQL สดในไฟล์, และ Type Safety ยังไม่ครอบคลุม
**เป้าหมาย:** มาตรฐาน PSR-12, Type Safety, และมี Automated Testing

### สิ่งที่ควรทำ:
- **Linting:** ติดตั้ง `php-cs-fixer` เพื่อบังคับใช้มาตรฐาน PSR-12 โดยอัตโนมัติ
- **Static Analysis:** ติดตั้ง `phpstan` เพื่อช่วยหาบั๊กและตรวจสอบ Type Errors
- **Strict Types:** เพิ่ม `declare(strict_types=1);` ในไฟล์ใหม่และไฟล์ที่ถูก Refactor
- **Testing:** ขยาย `tests/` ด้วย PHPUnit โดยเริ่มจาก Class ที่เป็น "Service" (เช่น `DrugPricingEngineService`)

## 3. การปรับปรุงฐานข้อมูล (Database Modernization)
**สถานะปัจจุบัน:** ไฟล์ SQL ดิบใน `database/` ต้องรัน Manual
**เป้าหมาย:** ระบบ Migrations ที่มี Version Control

### สิ่งที่ควรทำ:
- **Migration Tool:** นำ `phinx` หรือ `doctrine/migrations` มาใช้งาน
- **Convert Schema:** สร้าง Migration file แรกจาก `install_complete_latest.sql` เพื่อเป็นจุดเริ่มต้น
- **Query Builder:** ค่อยๆ เปลี่ยนจากการเขียน SQL สด (`SELECT * FROM...`) เป็นการใช้ Query Builder หรือ ORM เพื่อความปลอดภัยและอ่านง่าย

## 4. การปรับปรุง Frontend (Frontend Modernization)
**สถานะปัจจุบัน:** ไฟล์ JS ขนาดใหญ่ (`inbox-v2-fab.js`), เขียน CSS ในไฟล์ PHP, และใช้ CDN
**เป้าหมาย:** Component-based architecture และมี Build System

### สิ่งที่ควรทำ:
- **Build System:** เริ่มต้นโปรเจคด้วย **Vite** หรือ **Webpack**
- **Asset Management:** ย้าย `assets/js` และ `assets/css` ไปไว้ในโฟลเดอร์ `src/` แล้ว Compile เตรียมไว้ใน `public/assets/`
- **Modules:** แตกไฟล์ `inbox-v2-fab.js` ออกเป็น ES Modules ย่อยๆ (เช่น `components/ChatPanel.js`, `services/Api.js`)
- **TailwindCSS:** (ทางเลือก) หากต้องการทำ Design System แบบ "Aurora" แนะนำให้ติดตั้ง TailwindCSS อย่างเป็นทางการแทนการเขียน CSS เอง

## 5. โครงสร้างโฟลเดอร์ที่แนะนำ (Recommended Structure)
รูปแบบที่ควรจะเป็นในอนาคต:
```
/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Views/      (or templates/)
├── config/
├── database/
│   ├── migrations/
│   └── seeds/
├── public/         (Document Root - ไฟล์ที่เข้าถึงได้จากเว็บ)
│   ├── assets/     (Compiled JS/CSS)
│   └── index.php   (Front Controller จุดเดียว)
├── src/            (Frontend Source Code)
│   ├── js/
│   └── css/
├── tests/
└── vendor/
```

## ขั้นตอนแรกที่แนะนำ (Recommended First Step)
**"การเริ่มต้นแบบปลอดภัย (Safe Modernization)"**
1.  **Composer Init:** สร้าง `composer.json` ให้จัดการ Library และ Autoloading ทั้งหมด
2.  **Linting Setup:** วางระบบตรวจสอบโค้ด เพื่อป้องกันไม่ให้โค้ดใหม่รกกว่าเดิม
3.  **Refactor One Module:** เลือก 1 ฟีเจอร์ (เช่น `Templates`) แล้วลองเขียนใหม่ในรูปแบบ MVC เพื่อเป็นต้นแบบ
