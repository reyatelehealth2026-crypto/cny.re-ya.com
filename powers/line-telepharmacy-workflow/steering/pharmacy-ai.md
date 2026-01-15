# Pharmacy & AI Workflow

## Pharmacy Module

### Features

| Tab | URL | Description |
|-----|-----|-------------|
| Dashboard | `?tab=dashboard` | สรุปยอดขาย, รอจ่ายยา, แจ้งเตือน |
| Dispense | `?tab=dispense` | จ่ายยาตามใบสั่งแพทย์ |
| Interactions | `?tab=interactions` | ตรวจสอบยาตีกัน |
| Pharmacists | `?tab=pharmacists` | จัดการเภสัชกร |

### Drug Dispensing Flow

```
1. รับใบสั่งแพทย์/คำขอจากลูกค้า
2. ตรวจสอบ Drug Interactions
3. ตรวจสอบ Allergies
4. เลือกยาจาก Stock (FEFO)
5. พิมพ์ฉลากยา
6. บันทึกประวัติการจ่ายยา
```

### Drug Interaction Check

**3 ระดับการตรวจสอบ:**

1. **Drug-Drug Interaction** (ยาตีกัน)
   - Warfarin + Aspirin → เพิ่มความเสี่ยงเลือดออก
   - Metformin + Alcohol → เพิ่มความเสี่ยง lactic acidosis

2. **Contraindication** (ข้อห้ามใช้ตามโรค)
   - เบาหวาน → ระวัง Pseudoephedrine
   - ความดันสูง → ระวัง NSAIDs
   - หอบหืด → ห้าม Aspirin, Beta-blocker

3. **Allergy** (การแพ้ยา)
   - แพ้ Penicillin → ระวัง Amoxicillin, Ampicillin
   - แพ้ Sulfa → ระวัง Celecoxib

---

## AI Pharmacy Assistant

### Files
- `modules/AIChat/Adapters/PharmacyAIAdapter.php`
- `modules/AIChat/Services/TriageEngine.php`
- `modules/AIChat/Services/RedFlagDetector.php`
- `modules/AIChat/Services/DrugInteractionChecker.php`
- `classes/PharmacyGhostDraftService.php`

### AI Flow

```
ลูกค้าส่งข้อความ
       │
       ▼
   webhook.php
       │
       ▼
PharmacyAIAdapter
       │
   ┌───┴───┐
   │       │
   ▼       ▼
Triage   Chat
 Mode    Mode
   │       │
   ▼       │
TriageEngine
   │
   ▼
DrugInteractionChecker
   │
   └───┬───┘
       │
       ▼
ส่งข้อความกลับลูกค้า
       │
       ▼ (ถ้า Escalate)
PharmacistNotifier
```

### Triage States

```
1. greeting        - ทักทาย
2. symptom         - ถามอาการ
3. duration        - ถามระยะเวลา
4. severity        - ถามความรุนแรง
5. associated      - ถามอาการร่วม
6. allergy         - ถามประวัติแพ้ยา
7. medical_history - ถามโรคประจำตัว
8. current_meds    - ถามยาที่ใช้อยู่
9. recommend       - แนะนำยา
10. confirm        - ยืนยัน
```

### Red Flag Detection

| อาการ | ระดับ | การดำเนินการ |
|-------|-------|-------------|
| เจ็บหน้าอกร้าวไปแขน | Critical | แจ้งโทร 1669 ทันที |
| หายใจลำบากเฉียบพลัน | Critical | แจ้งโทร 1669 ทันที |
| อัมพาตครึ่งซีก | Critical | แจ้งโทร 1669 ทันที |
| ไข้สูงมาก (>40°C) | High | แนะนำพบแพทย์ด่วน |
| ปวดท้องรุนแรง | High | แนะนำพบแพทย์ |
| อาเจียนเป็นเลือด | High | แนะนำพบแพทย์ด่วน |

### Pharmacist Dashboard

**URL:** `pharmacist-dashboard.php`

**Features:**
- รายการรอตรวจสอบ
- เคสเร่งด่วน (Red Flag)
- ดูรายละเอียดการซักประวัติ
- อนุมัติ/ปฏิเสธยา
- ส่งข้อความกลับลูกค้า
- Video Call

### Vibe Selling OS v2

**URL:** `inbox-v2.php`

**HUD Widgets:**
- Customer Health Profile
- Drug Recommendations
- Pricing Engine
- Ghost Draft (AI แนะนำข้อความ)

**Files:**
- `classes/DrugPricingEngineService.php`
- `classes/DrugRecommendEngineService.php`
- `classes/CustomerHealthEngineService.php`
- `classes/PharmacyGhostDraftService.php`

## API Endpoints

```php
// Pharmacy AI
POST /api/pharmacy-ai.php?action=chat
POST /api/pharmacy-ai.php?action=get_recommendations
GET /api/pharmacy-ai.php?action=get_triage_state

// Pharmacist
GET /api/pharmacist.php?action=get_pending
POST /api/pharmacist.php?action=approve
POST /api/pharmacist.php?action=reject

// Inbox v2
GET /api/inbox-v2.php?action=get_hud
POST /api/inbox-v2.php?action=send_ghost_draft
```

## Best Practices

1. **ตรวจ Red Flag ก่อนเสมอ** - อาการฉุกเฉินต้องแจ้งทันที
2. **ตรวจ Drug Interactions** - ก่อนแนะนำยาทุกครั้ง
3. **บันทึกประวัติ** - ทุกการจ่ายยาต้องมีบันทึก
4. **Escalate เมื่อไม่แน่ใจ** - ส่งต่อเภสัชกรเสมอ
5. **ใช้ FEFO** - จ่ายยาหมดอายุก่อน
