# LINE CRM - Project Requirements Document
## ระบบจัดการลูกค้าสัมพันธ์ผ่าน LINE Official Account

**Version:** 2.5  
**Last Updated:** December 17, 2025  
**Project URL:** https://likesms.net/v1/

---

## 1. ภาพรวมโปรเจค (Project Overview)

### 1.1 วัตถุประสงค์
ระบบ LINE CRM เป็นแพลตฟอร์มจัดการลูกค้าสัมพันธ์ผ่าน LINE Official Account ที่ช่วยให้ธุรกิจสามารถ:
- จัดการและติดตามลูกค้าผ่าน LINE
- ขายสินค้าออนไลน์ผ่าน LIFF (LINE Front-end Framework)
- ส่งข้อความ Broadcast และ Flex Message
- วิเคราะห์ข้อมูลลูกค้าและยอดขาย
- ใช้ AI ช่วยตอบแชทอัตโนมัติ

### 1.2 กลุ่มเป้าหมาย
- ร้านค้าออนไลน์ขนาดเล็ก-กลาง
- ร้านขายยา/เวชภัณฑ์
- ธุรกิจบริการที่ใช้ LINE เป็นช่องทางหลัก
- ผู้ประกอบการที่ต้องการระบบ CRM ราคาประหยัด

---

## 2. ความต้องการด้านฟังก์ชัน (Functional Requirements)

### 2.1 ระบบจัดการบัญชี LINE (LINE Account Management)
| ID | Requirement | Priority |
|----|-------------|----------|
| LA-01 | รองรับหลาย LINE Official Account | High |
| LA-02 | ตั้งค่า Channel Access Token และ Channel Secret | High |
| LA-03 | ตั้งค่า LIFF ID สำหรับแต่ละฟีเจอร์ (Shop, Checkout, Main, Video Call) | High |
| LA-04 | เลือก Bot Mode (General/Shop/Business) | Medium |
| LA-05 | ตั้งค่า Webhook URL อัตโนมัติ | High |

### 2.2 ระบบจัดการผู้ใช้/ลูกค้า (User/Customer Management)
| ID | Requirement | Priority |
|----|-------------|----------|
| US-01 | แสดงรายชื่อผู้ใช้ที่เพิ่มเพื่อน LINE OA | High |
| US-02 | ดูประวัติการสนทนา | High |
| US-03 | ดูประวัติการสั่งซื้อ | High |
| US-04 | ระบบแท็ก (Tags) สำหรับจัดกลุ่มลูกค้า | High |
| US-05 | บันทึกโน้ต/หมายเหตุลูกค้า | Medium |
| US-06 | ดูข้อมูลโปรไฟล์จาก LINE (ชื่อ, รูป) | High |
| US-07 | ระบบ Customer Segments | Medium |

### 2.3 ระบบแชท (Chat System)
| ID | Requirement | Priority |
|----|-------------|----------|
| CH-01 | รับ-ส่งข้อความ Text | High |
| CH-02 | รับ-ส่งรูปภาพ | High |
| CH-03 | รับ-ส่ง Sticker | Medium |
| CH-04 | รับ-ส่ง Location | Medium |
| CH-05 | ส่ง Flex Message | High |
| CH-06 | Quick Reply | Medium |
| CH-07 | ระบบ Auto Reply | High |
| CH-08 | AI Chatbot (Gemini) | Medium |

### 2.4 ระบบร้านค้า (Shop System)
| ID | Requirement | Priority |
|----|-------------|----------|
| SH-01 | จัดการสินค้า (CRUD) | High |
| SH-02 | หมวดหมู่สินค้า | High |
| SH-03 | รูปภาพสินค้า | High |
| SH-04 | ราคาปกติ/ราคาลด | High |
| SH-05 | จัดการ Stock | High |
| SH-06 | SKU และ Barcode | Medium |
| SH-07 | ข้อมูลผู้ผลิต (Manufacturer) | Medium |
| SH-08 | ชื่อสามัญยา (Generic Name) | Medium |
| SH-09 | วิธีใช้ (Usage Instructions) | Medium |
| SH-10 | หน่วยนับ (Unit) | Medium |
| SH-11 | Import สินค้าจาก CSV | High |
| SH-12 | Export สินค้าเป็น CSV | High |

### 2.5 ระบบ LIFF Shop (Customer-facing)
| ID | Requirement | Priority |
|----|-------------|----------|
| LF-01 | หน้าแสดงสินค้า (Product Grid) | High |
| LF-02 | ค้นหาสินค้า | High |
| LF-03 | กรองตามหมวดหมู่ | High |
| LF-04 | ดูรายละเอียดสินค้า (Product Detail Modal) | High |
| LF-05 | ตะกร้าสินค้า (Cart) | High |
| LF-06 | หน้า Checkout | High |
| LF-07 | กรอกที่อยู่จัดส่ง | High |
| LF-08 | อัพโหลดสลิปโอนเงิน | High |
| LF-09 | ประวัติการสั่งซื้อ | Medium |

### 2.6 ระบบคำสั่งซื้อ (Order Management)
| ID | Requirement | Priority |
|----|-------------|----------|
| OR-01 | รายการคำสั่งซื้อ | High |
| OR-02 | รายละเอียดคำสั่งซื้อ | High |
| OR-03 | อัพเดทสถานะ (Pending/Confirmed/Shipped/Completed/Cancelled) | High |
| OR-04 | ตรวจสอบสลิปการโอนเงิน | High |
| OR-05 | แจ้งเตือนลูกค้าผ่าน LINE | High |

### 2.7 ระบบ Broadcast
| ID | Requirement | Priority |
|----|-------------|----------|
| BC-01 | ส่งข้อความถึงผู้ใช้ทั้งหมด | High |
| BC-02 | ส่งข้อความตามแท็ก/กลุ่ม | High |
| BC-03 | ส่ง Flex Message | High |
| BC-04 | ตั้งเวลาส่ง (Scheduled) | Medium |
| BC-05 | สถิติการส่ง | Medium |
| BC-06 | Broadcast Catalog (สินค้า) | Medium |

### 2.8 ระบบ Flex Message Builder
| ID | Requirement | Priority |
|----|-------------|----------|
| FX-01 | สร้าง Flex Message แบบ Visual | Medium |
| FX-02 | Template สำเร็จรูป | Medium |
| FX-03 | Preview ก่อนส่ง | Medium |
| FX-04 | บันทึก Template | Medium |

### 2.9 ระบบ Rich Menu
| ID | Requirement | Priority |
|----|-------------|----------|
| RM-01 | สร้าง Rich Menu | Medium |
| RM-02 | อัพโหลดรูปภาพ | Medium |
| RM-03 | กำหนด Action แต่ละปุ่ม | Medium |
| RM-04 | ตั้งเป็น Default | Medium |

### 2.10 ระบบ Video Call
| ID | Requirement | Priority |
|----|-------------|----------|
| VC-01 | สร้างห้อง Video Call | Low |
| VC-02 | ส่งลิงก์เชิญผ่าน LINE | Low |
| VC-03 | บันทึกประวัติการโทร | Low |

### 2.11 ระบบ AI Studio
| ID | Requirement | Priority |
|----|-------------|----------|
| AI-01 | AI Chatbot (Gemini API) | Medium |
| AI-02 | AI Image Generation | Low |
| AI-03 | Caption Generator | Low |
| AI-04 | Language Translator | Low |

### 2.12 ระบบ Loyalty Points
| ID | Requirement | Priority |
|----|-------------|----------|
| LP-01 | สะสมแต้มจากการซื้อ | Low |
| LP-02 | แลกแต้มเป็นส่วนลด | Low |
| LP-03 | ประวัติแต้ม | Low |

### 2.13 ระบบ Analytics
| ID | Requirement | Priority |
|----|-------------|----------|
| AN-01 | Dashboard ภาพรวม | Medium |
| AN-02 | สถิติผู้ใช้ | Medium |
| AN-03 | สถิติยอดขาย | Medium |
| AN-04 | สถิติข้อความ | Low |

### 2.14 ระบบ Admin
| ID | Requirement | Priority |
|----|-------------|----------|
| AD-01 | Login/Logout | High |
| AD-02 | จัดการ Admin Users | High |
| AD-03 | Role-based Access (Super Admin/Admin/Staff) | Medium |
| AD-04 | ตั้งค่าระบบ | High |

---

## 3. ความต้องการด้านเทคนิค (Technical Requirements)

### 3.1 Server Requirements
| Component | Requirement |
|-----------|-------------|
| Web Server | Apache 2.4+ with mod_rewrite |
| PHP | 8.0+ |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| SSL | Required (HTTPS) |
| Memory | 512MB+ RAM |
| Storage | 1GB+ |

### 3.2 PHP Extensions Required
- PDO + PDO_MySQL
- cURL
- JSON
- mbstring
- OpenSSL
- fileinfo
- GD (for image processing)

### 3.3 External Services
| Service | Purpose | Required |
|---------|---------|----------|
| LINE Messaging API | ส่ง-รับข้อความ | Yes |
| LINE LIFF | Frontend Apps | Yes |
| Google Gemini API | AI Chatbot | Optional |
| Payment Gateway | ชำระเงิน | Optional |

### 3.4 Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- LINE In-App Browser

---

## 4. ความต้องการด้านความปลอดภัย (Security Requirements)

| ID | Requirement | Priority |
|----|-------------|----------|
| SE-01 | HTTPS/SSL Certificate | High |
| SE-02 | Password Hashing (bcrypt) | High |
| SE-03 | Session Management | High |
| SE-04 | CSRF Protection | High |
| SE-05 | SQL Injection Prevention (PDO Prepared Statements) | High |
| SE-06 | XSS Prevention (htmlspecialchars) | High |
| SE-07 | File Upload Validation | High |
| SE-08 | LINE Signature Verification | High |
| SE-09 | Rate Limiting | Medium |
| SE-10 | Audit Logging | Low |

---

## 5. ความต้องการด้าน Performance (Performance Requirements)

| ID | Requirement | Target |
|----|-------------|--------|
| PF-01 | Page Load Time | < 3 seconds |
| PF-02 | API Response Time | < 500ms |
| PF-03 | Concurrent Users | 100+ |
| PF-04 | Database Queries | Optimized with indexes |
| PF-05 | Image Optimization | Lazy loading, compression |

---

## 6. โครงสร้างฐานข้อมูล (Database Schema)

### 6.1 Core Tables

```
├── admin_users          # ผู้ดูแลระบบ
├── line_accounts        # บัญชี LINE OA
├── users                # ลูกค้า/ผู้ใช้ LINE
├── user_tags            # แท็กสำหรับจัดกลุ่ม
├── user_tag_assignments # ความสัมพันธ์ user-tag
├── messages             # ประวัติข้อความ
├── business_items       # สินค้า
├── business_categories  # หมวดหมู่สินค้า (alias: product_categories)
├── cart_items           # ตะกร้าสินค้า
├── orders               # คำสั่งซื้อ
├── order_items          # รายการสินค้าในคำสั่งซื้อ
├── payment_slips        # สลิปการโอนเงิน
├── shop_settings        # ตั้งค่าร้านค้า
├── flex_templates       # Template Flex Message
├── broadcast_messages   # ประวัติ Broadcast
├── auto_replies         # ข้อความตอบกลับอัตโนมัติ
├── video_calls          # ประวัติ Video Call
├── loyalty_points       # แต้มสะสม
├── ai_settings          # ตั้งค่า AI
└── settings             # ตั้งค่าทั่วไป
```

### 6.2 Key Columns - business_items (Products)
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary Key |
| line_account_id | INT | FK to line_accounts |
| sku | VARCHAR(100) | รหัสสินค้า |
| barcode | VARCHAR(100) | บาร์โค้ด |
| name | VARCHAR(255) | ชื่อสินค้า |
| manufacturer | VARCHAR(255) | ผู้ผลิต |
| generic_name | VARCHAR(255) | ชื่อสามัญยา |
| usage_instructions | TEXT | วิธีใช้ |
| price | DECIMAL(10,2) | ราคา |
| sale_price | DECIMAL(10,2) | ราคาลด |
| unit | VARCHAR(50) | หน่วยนับ |
| stock | INT | จำนวนคงเหลือ |
| category_id | INT | FK to categories |
| description | TEXT | รายละเอียด |
| image_url | VARCHAR(500) | URL รูปภาพ |
| is_active | TINYINT | สถานะ |

---

## 7. API Endpoints

### 7.1 Webhook
```
POST /webhook.php
- รับ Events จาก LINE Platform
- Message, Follow, Unfollow, Postback
```

### 7.2 AJAX Handler
```
POST /api/ajax_handler.php
- action: get_users, get_messages, send_message, etc.
```

### 7.3 Checkout API
```
POST /api/checkout.php
- action: cart, add_to_cart, update_cart, remove_from_cart
- action: create_order, upload_slip
```

### 7.4 Broadcast API
```
POST /api/broadcast.php
- ส่ง Broadcast Message
```

---

## 8. LIFF Applications

| LIFF App | Path | Purpose |
|----------|------|---------|
| Main | liff-main.php | หน้าหลัก LIFF |
| Shop | liff-shop.php | หน้าร้านค้า |
| Checkout | liff-checkout.php | หน้าชำระเงิน |
| Video Call | liff-video-call.php | Video Call |
| Settings | liff-settings.php | ตั้งค่าผู้ใช้ |

---

## 9. การติดตั้ง (Installation)

### 9.1 ขั้นตอนการติดตั้ง
1. อัพโหลดไฟล์ไปยัง Web Server
2. สร้างฐานข้อมูล MySQL
3. แก้ไข `config/config.php` ตั้งค่า Database
4. เข้า `/install/` เพื่อติดตั้งตาราง
5. สร้าง LINE Official Account และ Messaging API Channel
6. สร้าง LIFF Apps (4 apps)
7. ตั้งค่า Webhook URL
8. Login และตั้งค่า LINE Account ในระบบ

### 9.2 Configuration Files
```
config/
├── config.php      # Main configuration
├── database.php    # Database connection class
```

---

## 10. Migrations

### 10.1 Migration Scripts
| File | Purpose |
|------|---------|
| run_product_details_migration.php | เพิ่มคอลัมน์รายละเอียดสินค้า |
| run_unify_tags_migration.php | รวมระบบแท็ก |
| run_complete_migration.php | Migration ทั้งหมด |
| run_loyalty_migration.php | ระบบแต้มสะสม |
| run_video_call_migration.php | ระบบ Video Call |

### 10.2 Fix Scripts
| File | Purpose |
|------|---------|
| fix_unify_tags.php | แก้ไขระบบแท็ก |
| fix_business_items.php | แก้ไขตารางสินค้า |
| fix_payment_slips_fk.php | แก้ไข Foreign Key |

---

## 11. Future Enhancements (Roadmap)

### Phase 1 (Current)
- [x] Multi LINE Account Support
- [x] Shop System with LIFF
- [x] Broadcast System
- [x] Tag System
- [x] AI Chatbot Integration

### Phase 2 (Planned)
- [ ] Payment Gateway Integration (PromptPay QR)
- [ ] Inventory Management
- [ ] Multi-language Support
- [ ] Mobile App (React Native)
- [ ] Advanced Analytics Dashboard

### Phase 3 (Future)
- [ ] CRM Automation (Drip Campaigns)
- [ ] Customer Segmentation AI
- [ ] Omnichannel Support (Facebook, Instagram)
- [ ] API for Third-party Integration

---

## 12. Support & Contact

**Developer:** LINE CRM Team  
**Documentation:** See README.md, INSTALL_GUIDE_V1.md  
**Issues:** Report via GitHub Issues

---

*Document generated: December 17, 2025*
