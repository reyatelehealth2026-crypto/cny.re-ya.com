# Inventory & WMS Workflow

## Inventory Structure

```
WAREHOUSE
└── ZONE (Storage Condition)
    ├── Zone A: Ambient (15-25°C)
    ├── Zone B: Cool (8-15°C)
    ├── Zone C: Cold (2-8°C)
    └── Zone D: Controlled
        └── LOCATION (Shelf/Bin)
            └── STOCK (Product + Batch + Quantity)
```

## Inventory Features

| Tab | URL | Description |
|-----|-----|-------------|
| Stock | `?tab=stock` | ดู Stock ทั้งหมด |
| Batches | `?tab=batches` | จัดการ Lot/Batch |
| Locations | `?tab=locations` | จัดการ Location |
| Put-Away | `?tab=put-away` | จัดเก็บสินค้า |
| Low Stock | `?tab=low-stock` | สินค้าใกล้หมด |
| Movements | `?tab=movements` | ประวัติเคลื่อนไหว |
| Adjustment | `?tab=adjustment` | ปรับปรุง Stock |

## Stock Movement Types

```
IN:  purchase, return_in, adjustment_in, transfer_in
OUT: sale, return_out, adjustment_out, transfer_out, expired, damaged
```

---

## WMS: Pick-Pack-Ship

### Order Flow

```
Order Received → Pick → Pack → Ship → Delivered
```

### Step 1: PICK (หยิบสินค้า)

**URL:** `inventory/index.php?tab=wms` (Pick Tab)
**Files:** `includes/inventory/wms-pick.php`, `classes/WMSService.php`

**FEFO Algorithm (First Expired, First Out):**
```sql
SELECT location, batch, quantity
FROM stock_by_location sbl
JOIN product_batches pb ON sbl.batch_id = pb.id
WHERE product_id = ? AND quantity > 0
ORDER BY pb.expiry_date ASC  -- หมดอายุก่อน ออกก่อน
```

**ขั้นตอน:**
1. ระบบแนะนำ Location + Batch ตาม FEFO
2. พนักงานไปหยิบสินค้าตาม Pick List
3. Scan Barcode ยืนยันการหยิบ
4. ตัด Stock จาก `stock_by_location`
5. บันทึก `wms_pick_items`

### Step 2: PACK (แพ็คสินค้า)

**URL:** `inventory/index.php?tab=wms` (Pack Tab)
**Files:** `includes/inventory/wms-pack.php`

**ขั้นตอน:**
1. ดึง Orders ที่ Pick แล้ว
2. Scan ตรวจสอบสินค้าทุกชิ้น
3. เลือกขนาดกล่อง/บรรจุภัณฑ์
4. พิมพ์ Packing Slip
5. พิมพ์ Shipping Label
6. บันทึกน้ำหนักพัสดุ

### Step 3: SHIP (จัดส่ง)

**URL:** `inventory/index.php?tab=wms` (Ship Tab)
**Files:** `includes/inventory/wms-ship.php`

**ขั้นตอน:**
1. ดึง Orders ที่ Pack แล้ว
2. เลือก Shipping Method (Kerry/Flash/Thailand Post)
3. พิมพ์ใบส่งสินค้า
4. บันทึก Tracking Number
5. ส่ง Tracking ให้ลูกค้าผ่าน LINE
6. บันทึก `wms_shipments`

## Order Status Flow

```
pending → processing → picked → packed → shipped → delivered
              │           │        │
              └───────────┴────────┴──→ cancelled (any stage)
```

## API Endpoints

```php
// WMS
POST /api/wms.php?action=start_pick
POST /api/wms.php?action=confirm_pick
POST /api/wms.php?action=start_pack
POST /api/wms.php?action=confirm_pack
POST /api/wms.php?action=create_shipment
POST /api/wms.php?action=update_tracking

// Inventory
GET /api/inventory.php?action=get_stock
POST /api/inventory.php?action=adjust_stock
GET /api/inventory.php?action=get_movements
```

## Print Services

**Files:** `classes/WMSPrintService.php`

- Pick List - รายการหยิบสินค้า
- Packing Slip - ใบรายการสินค้าในกล่อง
- Shipping Label - ป้ายจัดส่ง
- Invoice - ใบเสร็จ

## Best Practices

1. **ใช้ FEFO เสมอ** - ยาหมดอายุก่อนต้องออกก่อน
2. **Scan ทุกขั้นตอน** - ลดความผิดพลาด
3. **ตรวจสอบ Expiry ก่อนส่ง** - ไม่ส่งยาใกล้หมดอายุ
4. **แจ้ง Tracking ทันที** - ลูกค้าติดตามได้
