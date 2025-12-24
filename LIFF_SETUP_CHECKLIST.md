# LIFF Setup Checklist

## ✅ การแก้ไขที่ทำแล้ว

### 1. liff-shop.php
- เพิ่มการดึง `liff_checkout_id` จากฐานข้อมูล
- เพิ่มตัวแปร `LIFF_CHECKOUT_ID` ใน JavaScript
- แก้ฟังก์ชัน `checkout()` ให้ใช้ `LIFF_CHECKOUT_ID` แทน `LIFF_ID`

### 2. Flow ที่ถูกต้อง
```
Main Menu → Shop → Checkout → Upload Slip
   ↓          ↓        ↓           ↓
liff_id   liff_shop_id  liff_checkout_id  (same checkout)
```

## 📋 สิ่งที่ต้องตรวจสอบ

### 1. LINE Developers Console
ตรวจสอบ Endpoint URL ของแต่ละ LIFF App:

| LIFF App | LIFF ID | Endpoint URL |
|----------|---------|--------------|
| หน้าหลัก | 2008477880-wmRN2Aln | https://likesms.net/v1/liff-main.php |
| Shop | 2008477880-SOcaMdr0 | https://likesms.net/v1/liff-shop.php |
| Checkout | 2008477880-Qo97wjzg | https://likesms.net/v1/liff-checkout.php |
| Video | 2008477880-FDhymfKU | https://likesms.net/v1/liff-video-call-pro.php |

⚠️ **สำคัญ**: URL ต้องไม่มี `www.` ถ้า BASE_URL ไม่มี `www.`

### 2. Database (line_accounts)
ตรวจสอบว่า LIFF IDs ถูกบันทึกในฐานข้อมูล:
```sql
SELECT id, name, liff_id, liff_shop_id, liff_checkout_id, liff_video_id 
FROM line_accounts;
```

### 3. อัพโหลดไฟล์
อัพโหลดไฟล์ที่แก้ไขไปยัง server:
- `liff-shop.php` (แก้ไขฟังก์ชัน checkout)
- `test_liff_flow.php` (ไฟล์ทดสอบ)

## 🧪 การทดสอบ

### Test 1: เปิด Shop ผ่าน LIFF
1. เปิด https://liff.line.me/2008477880-SOcaMdr0
2. เพิ่มสินค้าลงตะกร้า
3. กดตะกร้า → กดสั่งซื้อ
4. ควรไปหน้า Checkout (LIFF ID: 2008477880-Qo97wjzg)

### Test 2: อัพโหลดสลิป
1. กรอกที่อยู่ → ถัดไป
2. เลือกโอนเงิน → ยืนยัน
3. กดอัพโหลดสลิป
4. เลือกรูป → ส่งสลิป

### Test 3: Debug Mode
- https://likesms.net/v1/liff-shop.php?debug=1
- https://likesms.net/v1/liff-checkout.php?debug=1
- https://likesms.net/v1/test_liff_flow.php

## 🔧 แก้ไขปัญหาที่พบบ่อย

### ปัญหา: 400 Bad Request
- ตรวจสอบ Endpoint URL ใน LINE Developers
- URL ต้องตรงเป๊ะ (ไม่มี www. ถ้า BASE_URL ไม่มี)

### ปัญหา: LIFF ID not configured
- รัน https://likesms.net/v1/update_all_liff.php?update=1
- ตรวจสอบ database ว่ามี LIFF IDs

### ปัญหา: อัพโหลดสลิปไม่ได้
- ตรวจสอบ folder `uploads/slips/` มีสิทธิ์เขียน
- ตรวจสอบ `api/checkout.php` ทำงานถูกต้อง
