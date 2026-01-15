# Procurement Workflow (การจัดซื้อ)

## Flow Overview

```
Low Stock Alert → Create PO → Approve → Order → Goods Receipt → Put-Away
```

## Step 1: Low Stock Detection

**ตรวจสอบสินค้าใกล้หมด:**
- URL: `inventory/index.php?tab=low-stock`
- เงื่อนไข: `stock <= reorder_point`
- แจ้งเตือน: LINE Notify / Email

## Step 2: Create Purchase Order (PO)

**สร้างใบสั่งซื้อ:**
- URL: `procurement.php?tab=po`
- Files: `includes/procurement/po.php`, `classes/PurchaseOrderService.php`

**ขั้นตอน:**
1. เลือก Supplier
2. เพิ่มรายการสินค้า + จำนวน + ราคา
3. กำหนดวันส่งสินค้า
4. บันทึก (Status: draft)

**PO Status Flow:**
```
draft → approved → ordered → partial_received → received → closed
```

## Step 3: Goods Receipt (GR)

**รับสินค้าเข้าคลัง:**
- URL: `procurement.php?tab=gr`
- Files: `includes/procurement/gr.php`, `classes/BatchService.php`

**ขั้นตอน:**
1. เลือก PO ที่จะรับสินค้า
2. ตรวจสอบจำนวนที่รับจริง
3. บันทึก **Lot Number** (สำคัญมาก!)
4. บันทึก **Expiry Date** (สำคัญมาก!)
5. บันทึก Manufacturing Date (optional)
6. ระบบสร้าง Batch Record อัตโนมัติ

**ข้อมูลที่ต้องบันทึก:**
| Field | Required | Description |
|-------|----------|-------------|
| lot_number | ✅ | เลข Lot จากผู้ผลิต |
| expiry_date | ✅ | วันหมดอายุ |
| manufacturing_date | ❌ | วันผลิต |
| quantity_received | ✅ | จำนวนที่รับจริง |
| unit_cost | ✅ | ราคาต่อหน่วย |

## Step 4: Put-Away

**จัดเก็บสินค้าเข้า Location:**
- URL: `inventory/index.php?tab=put-away`
- Files: `includes/inventory/put-away.php`, `classes/PutAwayService.php`

**ขั้นตอน:**
1. เลือก Batch ที่รับเข้า
2. Scan/เลือก Location (Zone + Shelf + Bin)
3. ระบุจำนวนที่จัดเก็บ
4. ตรวจสอบ Storage Condition (อุณหภูมิ)
5. บันทึก → `stock_by_location`

**Location Format:** `Zone-Shelf-Bin` (เช่น A-01-01)

**Zone Types:**
| Zone | Temperature | ตัวอย่างยา |
|------|-------------|-----------|
| Ambient | 15-25°C | ยาทั่วไป |
| Cool | 8-15°C | ยาบางชนิด |
| Cold | 2-8°C | วัคซีน, อินซูลิน |
| Controlled | ควบคุมพิเศษ | ยาเสพติด |

## API Endpoints

```php
// PO
POST /api/inventory.php?action=create_po
POST /api/inventory.php?action=approve_po
POST /api/inventory.php?action=receive_po

// GR
POST /api/inventory.php?action=create_gr
POST /api/inventory.php?action=complete_gr

// Put-Away
POST /api/put-away.php?action=put_away
GET /api/put-away.php?action=pending_batches
```

## Best Practices

1. **ตรวจสอบ Lot/Expiry ทุกครั้ง** - ห้ามรับสินค้าที่ไม่มี Lot
2. **ตั้ง Reorder Point** - สำหรับยาสำคัญ
3. **ใช้ Zone ที่เหมาะสม** - ตาม Storage Condition ของยา
4. **FIFO/FEFO** - จัดเก็บให้หยิบของเก่าก่อน
