# 📱 LIFF Pages Roadmap

> อัพเดทล่าสุด: 19 ธันวาคม 2567

## 📊 สถานะรวม

```
LIFF Pages:    12 หน้า ████████████░░░░░░░░ 55%
Admin Pages:    4 หน้า ████░░░░░░░░░░░░░░░░ 100% (สำหรับ LIFF)
Unified App:    1 หน้า ████████████████████ 100%
```

---

## 🚀 Unified LIFF App (แนะนำ!)

ใช้ **LIFF ID เดียว** สำหรับทุกฟังก์ชัน ง่ายต่อการจัดการ!

| ไฟล์ | URL | คำอธิบาย |
|------|-----|----------|
| `liff-app.php` | `?page=home` | หน้าหลัก + บัตรสมาชิก |
| | `?page=shop` | ร้านค้า |
| | `?page=checkout` | ตะกร้า/ชำระเงิน |
| | `?page=orders` | ออเดอร์ของฉัน |
| | `?page=points` | ประวัติแต้ม |
| | `?page=redeem` | แลกแต้ม |
| | `?page=appointments` | จองนัดหมาย |
| | `?page=my-appointments` | นัดหมายของฉัน |
| | `?page=profile` | โปรไฟล์/แก้ไขข้อมูล |

### วิธีตั้งค่า Unified LIFF:
1. สร้าง LIFF App ใน LINE Developers Console
2. ตั้ง Endpoint URL: `https://yourdomain.com/v2/liff-app.php`
3. คัดลอก LIFF ID ไปใส่ในหน้า `liff-settings.php`
4. เสร็จ! ใช้ได้ทุกฟังก์ชัน

---

## 🔧 Admin Backend (สำหรับจัดการ LIFF)

| # | หน้า | ไฟล์ | คำอธิบาย |
|---|------|------|----------|
| 1✅ | จัดการสมาชิก | `members.php` | ดูสมาชิก, เพิ่ม/หักแต้ม, กรองตามระดับ |
| 2✅ | ของรางวัลแลกแต้ม | `rewards.php` | CRUD ของรางวัล, ตั้งค่าแต้มที่ใช้ |
| 3✅ | จัดการนัดหมาย | `appointments-admin.php` | ดู/ยืนยัน/ยกเลิกนัดหมาย, กรองตามสถานะ |
| 4✅ | จัดการเภสัชกร | `pharmacists.php` | CRUD เภสัชกร, ตารางเวลา, วันหยุด |

---

## ✅ เสร็จแล้ว (Completed)

| # | หน้า | ไฟล์ | API | คำอธิบาย |
|---|------|------|-----|----------|
| 1✅ | หน้าหลัก/บัตรสมาชิก | `liff-member-card.php` | `api/member.php` | แสดงบัตรสมาชิก, เมนู 6 รายการ, รายชื่อเภสัชกร |
| 2✅| ลงทะเบียนสมาชิก | `liff-register.php` | `api/member.php` | ฟอร์มสมัครสมาชิก, กรอกข้อมูลส่วนตัว |
| 3✅ | ยินยอม PDPA | `liff-consent.php` | `api/consent.php` | หน้ายินยอมข้อมูลส่วนบุคคล | 
| 4 | ประวัติแต้ม| `liff-points-history.php` | `api/points.php` | ดูประวัติการได้รับ/ใช้แต้ม, กรองตามประเภท |
| 5 | แลกแต้ม | `liff-redeem-points.php` | `api/points.php` | แลกแต้มเป็นของรางวัล/คูปอง |
| 6 | ออเดอร์ของฉัน | `liff-my-orders.php` | `api/orders.php` | รายการออเดอร์ทั้งหมด, กรองตามสถานะ |
| 7 | นัดหมายเภสัชกร | `liff-appointment.php` | `api/appointments.php` | จองนัดหมายพบเภสัชกร 3 ขั้นตอน |
| 8 | นัดหมายของฉัน | `liff-my-appointments.php` | `api/appointments.php` | ดู/ยกเลิก/ให้คะแนนนัดหมาย |
| 9✅| ร้านค้า | `liff-shop.php` | `api/products.php` | แสดงสินค้า, หมวดหมู่ |
|10✅| ตะกร้า/Checkout | `liff-checkout.php` | `api/checkout.php` | ตะกร้าสินค้า, ชำระเงิน |
|11✅ | Video Call | `liff-video-call.php` | - | วิดีโอคอลกับเภสัชกร |
|12✅| แชร์ | `liff-share.php` | - | แชร์ลิงก์ไปยัง LINE |

---

## 🔄 ควรพัฒนาเพิ่ม (Recommended)

### 🔴 High Priority

| # | หน้า | ไฟล์ | API | คำอธิบาย |
|---|------|------|-----|----------|
| 1 | รายละเอียดออเดอร์ | `liff-order-detail.php` | `api/orders.php` | ดูรายละเอียดออเดอร์, สถานะ, รายการสินค้า |
| 2 | โปรไฟล์/แก้ไขข้อมูล | `liff-profile.php` | `api/member.php` | ดู/แก้ไขข้อมูลส่วนตัว, รูปโปรไฟล์ |

### 🟡 Medium Priority

| # | หน้า | ไฟล์ | API | คำอธิบาย |
|---|------|------|-----|----------|
| 3 | คูปองของฉัน | `liff-my-coupons.php` | `api/coupons.php` | รายการคูปองที่มี, ใช้คูปอง |
| 4 | ประวัติการซื้อ | `liff-purchase-history.php` | `api/orders.php` | สรุปประวัติการซื้อ, สถิติ |
| 5 | แจ้งเตือน | `liff-notifications.php` | `api/notifications.php` | รายการแจ้งเตือน, อ่าน/ลบ |
| 6 | ติดตามพัสดุ | `liff-tracking.php` | `api/tracking.php` | ติดตามสถานะจัดส่ง, เลขพัสดุ |

### 🟢 Low Priority

| # | หน้า | ไฟล์ | API | คำอธิบาย |
|---|------|------|-----|----------|
| 7 | รายละเอียดสินค้า | `liff-product-detail.php` | `api/products.php` | ดูรายละเอียดสินค้า, เพิ่มตะกร้า |
| 8 | ค้นหาสินค้า | `liff-search.php` | `api/products.php` | ค้นหาสินค้า, กรอง |
| 9 | รีวิวสินค้า | `liff-reviews.php` | `api/reviews.php` | ดู/เขียนรีวิวสินค้า |
| 10 | ที่อยู่จัดส่ง | `liff-addresses.php` | `api/addresses.php` | จัดการที่อยู่จัดส่ง |

---

## 🗂️ โครงสร้างไฟล์

```
📁 LIFF Pages
├── 📄 liff-member-card.php      ✅ หน้าหลัก
├── 📄 liff-register.php         ✅ ลงทะเบียน
├── 📄 liff-consent.php          ✅ PDPA
├── 📄 liff-points-history.php   ✅ ประวัติแต้ม
├── 📄 liff-redeem-points.php    ✅ แลกแต้ม
├── 📄 liff-my-orders.php        ✅ ออเดอร์ของฉัน
├── 📄 liff-order-detail.php     🔄 รายละเอียดออเดอร์
├── 📄 liff-appointment.php      ✅ จองนัดหมาย
├── 📄 liff-my-appointments.php  ✅ นัดหมายของฉัน
├── 📄 liff-shop.php             ✅ ร้านค้า
├── 📄 liff-checkout.php         ✅ ตะกร้า/ชำระเงิน
├── 📄 liff-profile.php          🔄 โปรไฟล์
├── 📄 liff-my-coupons.php       🔄 คูปอง
├── 📄 liff-notifications.php    🔄 แจ้งเตือน
├── 📄 liff-tracking.php         🔄 ติดตามพัสดุ
├── 📄 liff-video-call.php       ✅ Video Call
└── 📄 liff-share.php            ✅ แชร์

📁 API Endpoints
├── 📄 api/member.php            ✅ สมาชิก
├── 📄 api/consent.php           ✅ ยินยอม
├── 📄 api/points.php            ✅ แต้มสะสม
├── 📄 api/orders.php            ✅ ออเดอร์
├── 📄 api/appointments.php      ✅ นัดหมาย
├── 📄 api/products.php          ✅ สินค้า
├── 📄 api/checkout.php          ✅ ชำระเงิน
├── 📄 api/coupons.php           🔄 คูปอง
├── 📄 api/notifications.php     🔄 แจ้งเตือน
├── 📄 api/tracking.php          🔄 ติดตามพัสดุ
├── 📄 api/reviews.php           🔄 รีวิว
└── 📄 api/addresses.php         🔄 ที่อยู่
```

---

## 🔗 Flow การใช้งาน

```
┌─────────────────────────────────────────────────────────────┐
│                    เปิด LIFF App                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                  ตรวจสอบ Consent (PDPA)                     │
│                    liff-consent.php                         │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
      ยังไม่ยินยอม                        ยินยอมแล้ว
              │                               │
              ▼                               ▼
┌─────────────────────┐       ┌─────────────────────────────────┐
│  แสดงหน้ายินยอม     │       │     ตรวจสอบการลงทะเบียน         │
└─────────────────────┘       └─────────────────────────────────┘
                                              │
                              ┌───────────────┴───────────────┐
                              ▼                               ▼
                      ยังไม่ลงทะเบียน                   ลงทะเบียนแล้ว
                              │                               │
                              ▼                               ▼
              ┌─────────────────────┐       ┌─────────────────────────────────┐
              │  liff-register.php  │       │      liff-member-card.php       │
              │   หน้าลงทะเบียน     │       │         หน้าหลัก                │
              └─────────────────────┘       └─────────────────────────────────┘
                                                              │
                    ┌─────────────────────────────────────────┼─────────────────────────────────────────┐
                    │                    │                    │                    │                    │
                    ▼                    ▼                    ▼                    ▼                    ▼
            ┌───────────┐        ┌───────────┐        ┌───────────┐        ┌───────────┐        ┌───────────┐
            │  ร้านค้า   │        │  ตะกร้า   │        │  ออเดอร์  │        │ ประวัติแต้ม│        │  นัดหมาย  │
            │liff-shop  │        │liff-checkout│      │liff-my-orders│     │liff-points │        │liff-appt  │
            └───────────┘        └───────────┘        └───────────┘        └───────────┘        └───────────┘
```

---

## 📝 Database Tables ที่เกี่ยวข้อง

| ตาราง | Migration | คำอธิบาย |
|-------|-----------|----------|
| `users` | `migration_member_registration.sql` | ข้อมูลสมาชิก |
| `member_tiers` | `migration_member_registration.sql` | ระดับสมาชิก |
| `points_history` | `migration_member_registration.sql` | ประวัติแต้ม |
| `user_consents` | `migration_consent.sql` | การยินยอม PDPA |
| `orders` / `transactions` | `complete_install.sql` | ออเดอร์ |
| `pharmacists` | `migration_appointments.sql` | เภสัชกร |
| `appointments` | `migration_appointments.sql` | นัดหมาย |
| `point_rewards` | - | ของรางวัลแลกแต้ม |
| `coupons` | - | คูปอง |

---

## 🚀 ขั้นตอนการพัฒนาต่อ

### Phase 1: Core Features (เสร็จแล้ว ✅)
- [x] Member Card & Registration
- [x] Points System
- [x] Orders
- [x] Appointments

### Phase 2: Enhanced Features (กำลังดำเนินการ 🔄)
- [ ] Order Detail Page
- [ ] Profile Management
- [ ] Coupon System
- [ ] Notifications

### Phase 3: Advanced Features (วางแผน 📋)
- [ ] Product Reviews
- [ ] Address Management
- [ ] Advanced Search
- [ ] Tracking Integration

---

## 📌 หมายเหตุ

- ✅ = เสร็จแล้ว
- 🔄 = กำลังพัฒนา / ควรพัฒนา
- 📋 = วางแผนไว้

**Tech Stack:**
- Frontend: Tailwind CSS, Font Awesome, SweetAlert2
- LIFF SDK: LINE Front-end Framework v2
- Backend: PHP, MySQL
- API: RESTful JSON
