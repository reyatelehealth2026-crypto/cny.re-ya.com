# Quick Testing Guide

**Quick Reference** | สำหรับทดสอบระบบอย่างรวดเร็ว

---

## 🚀 Quick Start

### ก่อนเริ่มทดสอบ
```bash
✓ เปิด Browser DevTools (F12)
✓ Login ด้วย Admin account
✓ เตรียม LINE test account
✓ ตรวจสอบ Database connection
```

---

## 📋 Critical Path Testing (ทดสอบเส้นทางหลัก)

### 1. User Journey - ลูกค้าซื้อสินค้า
```
1. เปิด LIFF Shop → ดูสินค้า
2. เพิ่มสินค้าลงตะกร้า
3. Checkout → กรอกข้อมูล
4. ยืนยันคำสั่งซื้อ
5. ตรวจสอบ Order ใน My Orders
```
**Files**: `liff-shop.php`, `liff-checkout.php`, `liff-my-orders.php`

### 2. Admin Journey - จัดการคำสั่งซื้อ
```
1. Login → Dashboard
2. ดู Orders ใหม่
3. WMS → Pick → Pack → Ship
4. อัพเดท Tracking number
5. ตรวจสอบ Inventory ลดลง
```
**Files**: `shop/orders.php`, `includes/inventory/wms-*.php`

### 3. Pharmacy Journey - ให้คำปรึกษา
```
1. User ขอคำปรึกษาผ่าน LIFF
2. Pharmacist รับ notification
3. Review ข้อมูลผู้ป่วย
4. Video call หรือ chat
5. จ่ายยา → อัพเดท inventory
```
**Files**: `liff-pharmacy-consult.php`, `pharmacist-dashboard.php`

---

## ⚡ Quick Tests by Module

### 🔐 Authentication (2 min)
- [ ] Login สำเร็จ → เห็น Dashboard
- [ ] Login ผิด → เห็น Error
- [ ] Logout → กลับ Login page

### 💬 LINE Integration (3 min)
- [ ] ส่งข้อความ → Bot ตอบกลับ
- [ ] กด Rich Menu → เปิดหน้าถูกต้อง
- [ ] เปิด LIFF → โหลดสำเร็จ

### 🛒 E-commerce (5 min)
- [ ] ดูสินค้า → แสดงราคาถูกต้อง
- [ ] เพิ่มตะกร้า → Cart count เพิ่ม
- [ ] Checkout → สร้าง Order สำเร็จ
- [ ] Admin เห็น Order ใหม่

### 📦 Inventory & WMS (5 min)
- [ ] สร้าง PO → Status pending
- [ ] รับของ → Stock เพิ่ม
- [ ] Order → สร้าง Pick task
- [ ] Pick → Pack → Ship

### 💰 Accounting (3 min)
- [ ] สร้าง Receipt → AR เพิ่ม
- [ ] สร้าง Payment → AP เพิ่ม
- [ ] บันทึก Expense → แสดงใน Dashboard

### 💊 Pharmacy (4 min)
- [ ] ขอคำปรึกษา → สร้าง Session
- [ ] Check drug interaction → แสดง Warning
- [ ] จ่ายยา → บันทึกและลด Stock

### 🤖 AI & Chatbot (2 min)
- [ ] ถาม Chatbot → ได้คำตอบ
- [ ] Symptom assessment → ได้ Recommendation

### 🎁 Loyalty Points (2 min)
- [ ] ซื้อของ → ได้แต้ม
- [ ] แลกของรางวัล → แต้มลด

### 📊 Analytics (2 min)
- [ ] Dashboard → แสดงข้อมูล
- [ ] เลือกวันที่ → Filter ทำงาน

### 🔔 Notifications (2 min)
- [ ] Event เกิด → สร้าง Notification
- [ ] Scheduled message → ส่งตามเวลา

---

## 🐛 Common Issues & Quick Fixes

### Issue: LIFF ไม่โหลด
```
✓ ตรวจสอบ LIFF ID ใน config
✓ เช็ค Console errors
✓ ลอง Clear cache
```

### Issue: Webhook ไม่ทำงาน
```
✓ ตรวจสอบ Webhook URL ใน LINE Developers
✓ เช็ค SSL certificate
✓ ดู error_log
```

### Issue: Database connection error
```
✓ ตรวจสอบ config/database.php
✓ เช็ค MySQL service running
✓ ทดสอบ connection string
```

### Issue: Session หมดอายุเร็ว
```
✓ เช็ค session timeout setting
✓ ตรวจสอบ cookie settings
✓ ดู php.ini configuration
```

### Issue: Stock ไม่อัพเดท
```
✓ เช็ค transaction rollback
✓ ดู inventory_movements table
✓ ตรวจสอบ trigger events
```

---

## 🔍 Debugging Tools

### Browser DevTools
```javascript
// Check LIFF status
console.log(liff.isLoggedIn());
console.log(liff.getProfile());

// Check API responses
// Network tab → Filter: XHR
```

### Database Queries
```sql
-- Check recent orders
SELECT * FROM orders ORDER BY created_at DESC LIMIT 10;

-- Check stock levels
SELECT product_name, quantity FROM inventory WHERE quantity < 10;

-- Check notifications
SELECT * FROM notifications WHERE is_read = 0;
```

### Log Files
```bash
# PHP errors
tail -f error_log

# Webhook logs
tail -f webhook.log

# Cron job logs
tail -f cron/logs/*.log
```

---

## 📱 Mobile Testing Checklist

### iOS
- [ ] Safari - LIFF apps
- [ ] LINE app - Rich Menu
- [ ] PWA installation
- [ ] Touch gestures

### Android
- [ ] Chrome - LIFF apps
- [ ] LINE app - Rich Menu
- [ ] PWA installation
- [ ] Back button behavior

---

## 🎯 Smoke Test (5 นาที)

ทดสอบด่วนหลัง Deploy:

```
1. ✓ เว็บเปิดได้
2. ✓ Login ได้
3. ✓ Dashboard โหลด
4. ✓ ส่งข้อความ LINE → Bot ตอบ
5. ✓ เปิด LIFF Shop → เห็นสินค้า
6. ✓ สร้าง Order ทดสอบ
7. ✓ เช็ค Database มี Order
8. ✓ Logout ได้
```

---

## 📞 Emergency Contacts

### Critical Issues
```
Database down → Check hosting panel
LINE webhook error → Check LINE Developers Console
Payment gateway error → Contact payment provider
```

### Rollback Procedure
```bash
1. Backup current database
2. Restore previous version
3. Clear cache
4. Test critical paths
5. Notify team
```

---

## 📚 Related Documentation

- **Full Checklist**: `TESTING_CHECKLIST.md` (127 test cases)
- **Requirements**: `.kiro/specs/system-testing-checklist/requirements.md`
- **Design**: `.kiro/specs/system-testing-checklist/design.md`
- **User Manual**: `USER_MANUAL.md`
- **Install Guide**: `INSTALL_GUIDE_V1.md`

---

## 💡 Testing Tips

### ทำอย่างไรให้ทดสอบเร็ว
1. ใช้ Browser profiles แยกสำหรับ Admin/User
2. เตรียม Test data scripts
3. ใช้ Keyboard shortcuts
4. Screenshot tools สำหรับบันทึกผล
5. Automate repetitive tests

### ทำอย่างไรให้จับ Bug ได้
1. ทดสอบ Edge cases (empty, null, max)
2. ทดสอบ Error scenarios
3. ทดสอบ Concurrent users
4. ทดสอบ Different browsers
5. ทดสอบ Mobile devices

### Best Practices
- ✅ ทดสอบบน Staging ก่อน Production
- ✅ บันทึกผลทุกครั้ง
- ✅ Report bugs ทันที
- ✅ Retest หลังแก้ไข
- ✅ Document workarounds

---

**Last Updated**: January 4, 2026  
**Version**: 1.0
