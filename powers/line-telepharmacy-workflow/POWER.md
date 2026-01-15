---
name: "line-telepharmacy-workflow"
displayName: "LINE Telepharmacy Workflow"
description: "คู่มือ workflow ครบวงจรสำหรับระบบ LINE Telepharmacy Platform ครอบคลุมการจัดซื้อ คลังสินค้า WMS การขาย เภสัชกรรม CRM และบัญชี"
keywords: ["telepharmacy", "line", "pharmacy", "workflow", "wms", "pos", "inventory"]
author: "Clinicya Team"
---

# LINE Telepharmacy Workflow Guide

## Overview

คู่มือนี้อธิบาย workflow ทั้งหมดของระบบ LINE Telepharmacy Platform ซึ่งเป็นระบบจัดการร้านขายยาครบวงจรที่เชื่อมต่อกับ LINE Official Account

## Available Steering Files

- **procurement** - Workflow การจัดซื้อ (PO, GR, Put-Away)
- **inventory-wms** - Workflow คลังสินค้าและ Pick-Pack-Ship
- **sales-pos** - Workflow การขายและ POS
- **pharmacy-ai** - Workflow เภสัชกรรมและ AI Assistant

## System Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  SUPPLIER   │────▶│ PROCUREMENT │────▶│  INVENTORY  │────▶│    SALES    │
│  ผู้จำหน่าย   │     │   จัดซื้อ     │     │   คลังสินค้า  │     │    ขาย      │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                          │                   │                   │
                          ▼                   ▼                   ▼
                    ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
                    │     GR      │     │   BATCH +   │     │    WMS      │
                    │  รับสินค้า    │     │  LOCATION   │     │ Pick/Pack/  │
                    └─────────────┘     └─────────────┘     │   Ship      │
                                                           └─────────────┘
```

## Sales Channels

| Channel | Description | Entry Point |
|---------|-------------|-------------|
| LINE LIFF | ร้านค้าออนไลน์ผ่าน LINE | `liff/index.php` |
| LINE Chat | สั่งซื้อผ่านแชท | `webhook.php` |
| Admin Panel | สร้างออเดอร์โดย Admin | `shop/orders.php` |
| Walk-in POS | ขายหน้าร้าน | `pos.php` |

## Key Modules

### 1. Procurement (การจัดซื้อ)
- สร้าง Purchase Order (PO)
- รับสินค้า (Goods Receipt) พร้อม Lot/Expiry
- จัดเก็บเข้า Location (Put-Away)

### 2. Inventory (คลังสินค้า)
- จัดการ Stock, Batch, Location
- FIFO/FEFO สำหรับยา
- Low Stock Alert

### 3. WMS (Pick-Pack-Ship)
- Pick: หยิบสินค้าตาม FEFO
- Pack: แพ็คและพิมพ์ Label
- Ship: จัดส่งและ Tracking

### 4. Sales & POS
- ขายผ่าน LINE LIFF
- ขายหน้าร้าน (POS)
- จัดการ Shift และ Returns

### 5. Pharmacy
- จ่ายยาตามใบสั่งแพทย์
- ตรวจสอบ Drug Interactions
- AI Triage และ Red Flag Detection

### 6. CRM & Communication
- LINE Messaging & Rich Menu
- Broadcast & Campaigns
- Inbox Chat Management

### 7. Accounting
- AP/AR Management
- Expenses Tracking
- Payment/Receipt Vouchers

## Quick Reference

### Status Flows

**PO Status:**
```
draft → approved → ordered → partial_received → received → closed
```

**Order Status:**
```
pending → processing → picked → packed → shipped → delivered
```

**Stock Movement Types:**
```
IN:  purchase, return_in, adjustment_in, transfer_in
OUT: sale, return_out, adjustment_out, transfer_out, expired, damaged
```

## Best Practices

- ใช้ FEFO (First Expired, First Out) สำหรับยาเสมอ
- ตรวจสอบ Drug Interactions ก่อนจ่ายยา
- บันทึก Lot/Expiry ทุกครั้งที่รับสินค้า
- ตั้ง Reorder Point สำหรับยาสำคัญ
- ใช้ AI Triage เพื่อคัดกรองอาการฉุกเฉิน
