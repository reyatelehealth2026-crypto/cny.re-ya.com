# 🏥 ระบบ AI เภสัชออนไลน์ - คู่มือการใช้งาน

## 📋 ภาพรวมระบบ

ระบบ AI เภสัชออนไลน์เป็นระบบซักประวัติอัจฉริยะที่ทำงานผ่าน LINE โดยมีความสามารถหลัก:

1. **Triage System** - ซักประวัติอาการเป็นขั้นตอน
2. **Red Flag Detection** - ตรวจจับอาการฉุกเฉิน
3. **Drug Interaction Checker** - ตรวจสอบยาตีกัน
4. **Pharmacist Dashboard** - หน้าจัดการสำหรับเภสัชกร
5. **LINE Notification** - แจ้งเตือนเภสัชกรผ่าน LINE

---

## 🔄 Flow การทำงาน

```
┌─────────────────────────────────────────────────────────────────┐
│                    ลูกค้าส่งข้อความผ่าน LINE                      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      webhook.php                                 │
│  ตรวจสอบว่าเปิดใช้ PharmacyAI หรือไม่                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  PharmacyAIAdapter.php                           │
│  - ตรวจสอบ Red Flags ก่อนเสมอ                                    │
│  - เลือก Mode: Triage หรือ Chat                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              │                               │
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│    Triage Mode          │     │     Chat Mode           │
│  (ซักประวัติเป็นขั้นตอน)   │     │  (แชทอิสระกับ AI)        │
└─────────────────────────┘     └─────────────────────────┘
              │                               │
              ▼                               │
┌─────────────────────────┐                   │
│    TriageEngine.php     │                   │
│  State Machine:         │                   │
│  1. greeting            │                   │
│  2. symptom             │                   │
│  3. duration            │                   │
│  4. severity            │                   │
│  5. associated          │                   │
│  6. allergy             │                   │
│  7. medical_history     │                   │
│  8. current_meds        │                   │
│  9. recommend           │                   │
│  10. confirm            │                   │
└─────────────────────────┘                   │
              │                               │
              ▼                               │
┌─────────────────────────┐                   │
│ DrugInteractionChecker  │                   │
│  - ตรวจยาตีกัน           │                   │
│  - ตรวจข้อห้ามใช้         │                   │
│  - ตรวจแพ้ยา            │                   │
└─────────────────────────┘                   │
              │                               │
              └───────────────┬───────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    ส่งข้อความกลับลูกค้า                           │
│  - Text Message + Quick Reply                                    │
│  - Flex Message (ถ้าแนะนำยา)                                     │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼ (ถ้า Escalate หรือพบ Red Flag)
┌─────────────────────────────────────────────────────────────────┐
│                  PharmacistNotifier.php                          │
│  - บันทึก Notification ลง DB                                     │
│  - ส่ง LINE Push ให้เภสัชกร                                      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  Pharmacist Dashboard                            │
│  - ดูรายการรอตรวจสอบ                                             │
│  - อนุมัติ/ปฏิเสธยา                                              │
│  - ส่งข้อความกลับลูกค้า                                          │
│  - Video Call                                                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📁 โครงสร้างไฟล์

```
modules/AIChat/
├── Adapters/
│   ├── PharmacyAIAdapter.php    # Adapter หลัก
│   └── GeminiChatAdapter.php    # Adapter สำหรับ Gemini
├── Services/
│   ├── TriageEngine.php         # State Machine ซักประวัติ
│   ├── RedFlagDetector.php      # ตรวจจับอาการฉุกเฉิน
│   ├── DrugInteractionChecker.php # ตรวจยาตีกัน
│   ├── PharmacistNotifier.php   # แจ้งเตือนเภสัชกร
│   ├── GeminiAPI.php            # เรียก Gemini API
│   └── PromptBuilder.php        # สร้าง Prompt
├── Models/
│   ├── AISettings.php           # ตั้งค่า AI
│   ├── ConversationHistory.php  # ประวัติสนทนา
│   ├── ConversationState.php    # State การสนทนา
│   └── MedicalHistory.php       # ประวัติการรักษา
├── Templates/
│   └── PharmacyFlexTemplates.php # Flex Templates
└── Autoloader.php               # Autoloader

api/
├── pharmacy-ai.php              # API สำหรับ LIFF
└── pharmacist.php               # API สำหรับ Dashboard

database/
├── migration_triage_system.sql  # ตาราง Triage
└── migration_pharmacist_system.sql # ตารางเพิ่มเติม

หน้า Admin:
├── pharmacist-dashboard.php     # Dashboard เภสัชกร
├── triage-analytics.php         # สถิติ Triage
├── drug-interactions.php        # จัดการยาตีกัน
└── ai-pharmacy-settings.php     # ตั้งค่า AI เภสัช

หน้า LIFF:
└── liff-pharmacy-consult.php    # หน้าปรึกษาเภสัชกร
```

---

## 🚀 การติดตั้ง

### 1. รัน Migration
```
เปิด browser ไปที่:
http://your-domain/run_triage_migration.php
http://your-domain/run_pharmacist_migration.php
```

### 2. ตั้งค่า Gemini API
- ไปที่ `ai-chat-settings.php`
- ใส่ Gemini API Key
- เปิดใช้งาน AI

### 3. ตั้งค่าเภสัชกร
- ไปที่ `admin-users.php`
- เพิ่ม user ที่มี role = 'pharmacist'
- ใส่ `line_user_id` ของเภสัชกร (เพื่อรับ notification)

### 4. ทดสอบ
- ส่งข้อความ "ปวดหัว" ผ่าน LINE
- ระบบจะเริ่มซักประวัติอัตโนมัติ

---

## 🔴 Red Flag Detection

ระบบจะตรวจจับอาการฉุกเฉินและแจ้งเตือนทันที:

| อาการ | ระดับ | การดำเนินการ |
|-------|-------|-------------|
| เจ็บหน้าอกร้าวไปแขน | Critical | แจ้งโทร 1669 ทันที |
| หายใจลำบากเฉียบพลัน | Critical | แจ้งโทร 1669 ทันที |
| อัมพาตครึ่งซีก | Critical | แจ้งโทร 1669 ทันที |
| ไข้สูงมาก (>40°C) | High | แนะนำพบแพทย์ด่วน |
| ปวดท้องรุนแรง | High | แนะนำพบแพทย์ |
| อาเจียนเป็นเลือด | High | แนะนำพบแพทย์ด่วน |

---

## 💊 Drug Interaction Checker

ระบบตรวจสอบ 3 ระดับ:

### 1. ยาตีกัน (Drug-Drug Interaction)
- Warfarin + Aspirin → เพิ่มความเสี่ยงเลือดออก
- Metformin + Alcohol → เพิ่มความเสี่ยง lactic acidosis
- Simvastatin + Grapefruit → เพิ่มระดับยาในเลือด

### 2. ข้อห้ามใช้ตามโรค (Contraindication)
- เบาหวาน → ระวัง Pseudoephedrine
- ความดันสูง → ระวัง NSAIDs
- หอบหืด → ห้าม Aspirin, Beta-blocker

### 3. การแพ้ยา (Allergy)
- ตรวจสอบการแพ้โดยตรง
- ตรวจสอบการแพ้ข้ามกลุ่ม (Cross-reactivity)
  - แพ้ Penicillin → ระวัง Amoxicillin, Ampicillin
  - แพ้ Sulfa → ระวัง Celecoxib

---

## 📊 Pharmacist Dashboard

### ฟีเจอร์หลัก:
1. **รายการรอตรวจสอบ** - ดูคำขอปรึกษาใหม่
2. **เคสเร่งด่วน** - เคสที่พบ Red Flag (แสดงสีแดง)
3. **ดูรายละเอียด** - ดูประวัติการซักประวัติทั้งหมด
4. **อนุมัติยา** - เลือกยาและส่งให้ลูกค้า
5. **ปฏิเสธ** - ปฏิเสธพร้อมเหตุผล
6. **ส่งข้อความ** - ส่งข้อความกลับลูกค้า
7. **Video Call** - โทรหาลูกค้า

### การอนุมัติยา:
1. กดปุ่ม "ดู" ที่รายการ
2. ตรวจสอบข้อมูลการซักประวัติ
3. เลือกยาที่ต้องการแนะนำ (checkbox)
4. ใส่หมายเหตุ (ถ้ามี)
5. กด "อนุมัติและส่งยา"
6. ระบบจะส่ง Flex Message ให้ลูกค้าพร้อมปุ่มสั่งซื้อ

---

## 📱 LIFF Pharmacy Consult

หน้า LIFF สำหรับลูกค้าปรึกษาเภสัชกร:

- **Quick Symptoms** - ปุ่มเลือกอาการด่วน
- **Chat Interface** - แชทกับ AI
- **Attach Menu** - ถ่ายรูป, Video Call, ดูประวัติ
- **Emergency Alert** - แจ้งเตือนเมื่อพบอาการฉุกเฉิน

---

## 🔔 LINE Notification

เมื่อมีคำขอปรึกษาใหม่หรือพบ Red Flag:

1. บันทึกลงตาราง `pharmacist_notifications`
2. ส่ง LINE Push Message ให้เภสัชกรทุกคน
3. Flex Message แสดง:
   - ชื่อลูกค้า
   - อาการ
   - ระยะเวลา
   - ความรุนแรง
   - Red Flags (ถ้ามี)
   - ปุ่มไปยัง Dashboard

### ตั้งค่าเภสัชกรรับ Notification:
1. ไปที่ `admin-users.php`
2. แก้ไข user ที่เป็นเภสัชกร
3. ใส่ `line_user_id` (ได้จาก LINE Official Account Manager)

---

## 📈 Triage Analytics

หน้ารายงานสถิติ:

- **Session ทั้งหมด** - จำนวน session ทั้งหมด
- **เสร็จสมบูรณ์** - session ที่ซักประวัติครบ
- **ส่งต่อเภสัชกร** - session ที่ escalate
- **เคสเร่งด่วน** - session ที่พบ Red Flag
- **อัตราสำเร็จ** - % ที่เสร็จสมบูรณ์
- **เวลาเฉลี่ย** - นาทีต่อเคส

### กราฟ:
- **Session รายวัน** - Line Chart
- **อาการที่พบบ่อย** - Bar Chart
- **สถานะ Session** - Doughnut Chart

---

## ⚙️ การปรับแต่ง

### เพิ่มอาการใหม่ใน Triage:
แก้ไข `TriageEngine.php` ที่ method `extractSymptoms()`:
```php
$symptomPatterns = [
    'ปวดหัว', 'ไข้', 'ไอ', ...
    'อาการใหม่',  // เพิ่มตรงนี้
];
```

### เพิ่มยาตีกันใหม่:
แก้ไข `DrugInteractionChecker.php` ที่ `INTERACTIONS`:
```php
['drug1' => 'ยา1', 'drug2' => 'ยา2', 'severity' => 'severe',
 'effect' => 'ผลกระทบ', 'recommendation' => 'คำแนะนำ'],
```

### เพิ่ม Red Flag ใหม่:
แก้ไข `RedFlagDetector.php` ที่ `RED_FLAGS`:
```php
['pattern' => 'อาการใหม่', 'severity' => 'high',
 'message' => 'ข้อความเตือน', 'action' => 'คำแนะนำ'],
```

---

## 🆘 Troubleshooting

### AI ไม่ตอบ:
1. ตรวจสอบ Gemini API Key ใน `ai-chat-settings.php`
2. ตรวจสอบว่าเปิดใช้งาน AI แล้ว
3. ดู error log ที่ `error_log`

### ไม่ได้รับ Notification:
1. ตรวจสอบ `line_user_id` ของเภสัชกร
2. ตรวจสอบ `channel_access_token` ใน `line_accounts`
3. ตรวจสอบว่า user มี role = 'pharmacist'

### ตารางไม่มี:
รัน migration อีกครั้ง:
```
http://your-domain/run_triage_migration.php
http://your-domain/run_pharmacist_migration.php
```

---

## 📞 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ:
- ดู `dev-dashboard.php` สำหรับ debug logs
- ตรวจสอบ `error_log` ของ PHP
