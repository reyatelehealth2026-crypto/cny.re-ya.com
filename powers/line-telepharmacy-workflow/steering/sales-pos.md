# Sales & POS Workflow

## Sales Channels

### Channel 1: LINE LIFF Shop

**Entry:** `liff/index.php` (Vue.js SPA)

**Flow:**
1. ลูกค้าเปิด LIFF จาก Rich Menu
2. เลือกสินค้า → เพิ่มตะกร้า
3. Checkout → กรอกที่อยู่
4. เลือกวิธีชำระเงิน
5. ยืนยันคำสั่งซื้อ
6. `api/checkout.php` → สร้าง Order

### Channel 2: LINE Chat

**Entry:** `webhook.php`

**Commands:**
| Command | Action |
|---------|--------|
| "shop" / "สินค้า" | แสดงหมวดหมู่ |
| "หมวด [id]" | แสดงสินค้าในหมวด |
| "เพิ่ม [id]" | เพิ่มตะกร้า |
| "ตะกร้า" | แสดงตะกร้า |
| "สั่งซื้อ" | Checkout |
| "คำสั่งซื้อ" | ดูประวัติ |

### Channel 3: Admin Panel

**Entry:** `shop/orders.php`

**Features:**
- สร้าง Order ให้ลูกค้า
- จัดการ Order Status
- พิมพ์ใบเสร็จ
- ดูประวัติการสั่งซื้อ

### Channel 4: Walk-in POS

**Entry:** `pos.php`

**Features:**
- ขายหน้าร้าน
- Scan Barcode
- จ่ายยาตามใบสั่งแพทย์
- พิมพ์ใบเสร็จ

---

## POS System

### Files
- `pos.php` - หน้าหลัก POS
- `classes/POSService.php` - Business Logic
- `classes/POSPaymentService.php` - การชำระเงิน
- `classes/POSShiftService.php` - จัดการกะ
- `classes/POSReturnService.php` - คืนสินค้า
- `classes/POSReceiptService.php` - ใบเสร็จ
- `api/pos.php` - API Endpoints

### Shift Management

**เปิดกะ:**
```php
POST /api/pos.php?action=open_shift
{
    "opening_cash": 1000,
    "notes": "เปิดร้านเช้า"
}
```

**ปิดกะ:**
```php
POST /api/pos.php?action=close_shift
{
    "closing_cash": 5000,
    "notes": "ปิดร้านเย็น"
}
```

### Payment Methods

| Method | Description |
|--------|-------------|
| cash | เงินสด |
| transfer | โอนเงิน |
| promptpay | พร้อมเพย์ QR |
| credit_card | บัตรเครดิต |
| cod | เก็บเงินปลายทาง |

### POS Transaction Flow

```
1. เปิดกะ (Open Shift)
2. สแกน/เลือกสินค้า
3. ใส่จำนวน
4. เลือกวิธีชำระเงิน
5. รับเงิน/ทอนเงิน
6. พิมพ์ใบเสร็จ
7. ปิดกะ (Close Shift)
```

### Returns

**คืนสินค้า:**
```php
POST /api/pos.php?action=create_return
{
    "original_sale_id": 123,
    "items": [
        {"product_id": 1, "quantity": 1, "reason": "สินค้าชำรุด"}
    ],
    "refund_method": "cash"
}
```

## API Endpoints

```php
// POS
POST /api/pos.php?action=create_sale
POST /api/pos.php?action=open_shift
POST /api/pos.php?action=close_shift
POST /api/pos.php?action=create_return
GET /api/pos.php?action=get_shift_summary

// Checkout (LIFF)
POST /api/checkout.php?action=create_order
POST /api/checkout.php?action=add_to_cart
POST /api/checkout.php?action=update_cart
GET /api/checkout.php?action=get_cart
```

## Reports

**URL:** `pos.php?tab=reports`
**Files:** `includes/pos/reports.php`, `classes/POSReportService.php`

- ยอดขายรายวัน
- ยอดขายรายเดือน
- สรุปกะ
- สินค้าขายดี
- วิธีชำระเงิน

## Best Practices

1. **เปิด/ปิดกะทุกครั้ง** - ตรวจสอบเงินสด
2. **Scan Barcode** - ลดความผิดพลาด
3. **พิมพ์ใบเสร็จทุกครั้ง** - หลักฐานการขาย
4. **ตรวจสอบ Stock ก่อนขาย** - ไม่ขายสินค้าหมด
