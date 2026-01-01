# Requirements Document

## Introduction

ปรับโครงสร้างเมนู Admin Dashboard (Sidebar) ใหม่ โดยจัดกลุ่มเมนูที่มีอยู่แล้วให้เป็น 5 กลุ่มหลักตามหน้าที่การใช้งาน พร้อมระบบ Role-Based Access Control

**หมายเหตุ:** งานนี้เป็นการจัดกลุ่มเมนูใหม่เท่านั้น ไม่มีการสร้างหน้าหรือฟีเจอร์ใหม่

## Glossary

- **Sidebar**: ส่วนเมนูนำทางด้านข้างของระบบ Admin
- **Menu Group**: กลุ่มเมนูหลัก (5 กลุ่ม)
- **Sub-Menu**: เมนูย่อยภายในกลุ่ม
- **Role**: บทบาทของผู้ใช้ (Owner, Admin, Pharmacist, Staff, Marketing, Tech)

---

## Requirements

### Requirement 1: โครงสร้างกลุ่มเมนูใหม่

**User Story:** As an admin user, I want to see menus organized into 5 logical groups, so that I can find features quickly.

#### Acceptance Criteria

1. WHEN the admin dashboard loads THEN the system SHALL display 5 main menu groups in sidebar
2. WHEN a menu group is clicked THEN the system SHALL expand to show all sub-menus within that group
3. WHEN a menu group is collapsed THEN the system SHALL hide all sub-menus

---

### Requirement 2: กลุ่ม 1 - Insights & Overview

**User Story:** As an admin/owner, I want to access dashboard and analytics features in one place.

#### Menu Structure

| New Menu (EN) | ชื่อภาษาไทย (UI) | Features (ฟีเจอร์ย่อย) | Role Access |
|---------------|------------------|------------------------|-------------|
| Executive Dashboard | แดชบอร์ดผู้บริหาร | Dashboard, เลขสรุป, Performance รวม, ลิงค์ข้ามหน้า | Owner, Admin |
| Clinical Analytics | สถิติการรักษา | สถิติคนไข้ไทรอาจ, การจ่ายยา, Drug Interaction Stats | Pharmacist, Owner |
| Audit Logs | ประวัติการใช้งาน | ตรวจสอบการกระทำที่เกิดขึ้น, Login History, การแก้ไขออเดอร์ | Owner Only |

#### Acceptance Criteria

1. WHEN viewing Insights & Overview group THEN the system SHALL display: Executive Dashboard, Clinical Analytics, Audit Logs
2. WHEN a user with Owner role accesses this group THEN the system SHALL grant full access to all menus
3. WHEN a user with Pharmacist role accesses this group THEN the system SHALL grant access only to Clinical Analytics

---

### Requirement 3: กลุ่ม 2 - Clinical Station

**User Story:** As a pharmacist/staff, I want to access patient care tools in one location.

#### Menu Structure

| New Menu (EN) | ชื่อภาษาไทย (UI) | Features (ฟีเจอร์ย่อย) | Role Access |
|---------------|------------------|------------------------|-------------|
| Unified Care Chat | พื้นที่ตรวจสอบแชทรวม | แชท (Text), Video Consult, ตอบไลน์อัตโน, ตรวจไลน์ | Pharmacist, Staff |
| Roster & Shifts | ตารางเวรเภสัชกร | จัดการเวรเภสัชกร, สถานะ Online/Offline, Booking Slots | All Staff |
| Medical Copilot (AI) | ผู้ช่วย AI | AI Tools, เลขสรุป & AI, AI สรุปประวัติ, ตรวจสอบประวัติยา, ค้นหาข้อมูลยา | Pharmacist Only |

#### Acceptance Criteria

1. WHEN viewing Clinical Station group THEN the system SHALL display: Unified Care Chat, Roster & Shifts, Medical Copilot (AI)
2. WHEN a user with Pharmacist role accesses this group THEN the system SHALL grant full access to all menus
3. WHEN a user with Staff role accesses this group THEN the system SHALL grant access to Unified Care Chat and Roster & Shifts only

---

### Requirement 4: กลุ่ม 3 - Patient & Journey

**User Story:** As an admin/staff, I want to manage patient information and engagement in one place.

#### Menu Structure

| New Menu (EN) | ชื่อภาษาไทย (UI) | Features (ฟีเจอร์ย่อย) | Role Access |
|---------------|------------------|------------------------|-------------|
| EHR (Health Records) | ระบบเวชระเบียนไฟ | รายชื่อลูกค้า, ประวัติสั่งยา, ใบสั่งยาจ่าง, Note ลงบันทึก | Pharmacist Only |
| Membership | สมาชิก & สิทธิพิเศษ | ระดับสมาชิก (Tier), ระบบแต้ม, ประวัติการแลก | All Staff |
| Care Journey | ติดตามผลลัพธ์ไลน์ | บรอดแคสต์, Drip, Automation Flows (เช่อนทักทายใหม่/ติดตาม), ติดตามผลการรักษา | Admin, Marketing |
| Digital Front Door | จัดการหน้าร้าน LINE | ตั้งค่าแชท Rich Menu, จัดการหน้าต่างร้านค้า, LIFF Settings | Admin, Marketing |

#### Acceptance Criteria

1. WHEN viewing Patient & Journey group THEN the system SHALL display: EHR, Membership, Care Journey, Digital Front Door
2. WHEN a user with Pharmacist role accesses EHR THEN the system SHALL grant full access to health records
3. WHEN a user with Admin role accesses this group THEN the system SHALL grant access to all menus except EHR medical details
4. WHEN a user with Marketing role accesses this group THEN the system SHALL grant access to Care Journey and Digital Front Door only

---

### Requirement 5: กลุ่ม 4 - Supply & Revenue

**User Story:** As an admin/pharmacist, I want to manage inventory and orders in one location.

#### Menu Structure

| New Menu (EN) | ชื่อภาษาไทย (UI) | Features (ฟีเจอร์ย่อย) | Role Access |
|---------------|------------------|------------------------|-------------|
| Billing & Orders | บิล & ออเดอร์ | ออเดอร์, ตรวจสลิป, ออกใบกำกับภาษี, สถานะจัดส่ง | Admin, Staff |
| Inventory | คลังยา & สินค้า | รายการยา (SKU), ตัดสต็อก, หน่วยสินค้าไทย/หน่วยขายหลายๆ | Admin, Pharmacist |
| Services Catalog | รายการค่าบริการ | (New) รายการ Consult, ค่าบริการ, หน่วยการตรวจสุขภาพ | Admin, Pharmacist |
| Procurement | จัดซื้อ & รับสินค้า | ใบสั่งซื้อ (PO), สั่งซื้อจาก Supplier, รับสินค้าจากสุขภาพ (GR) | Admin, Owner |

#### Acceptance Criteria

1. WHEN viewing Supply & Revenue group THEN the system SHALL display: Billing & Orders, Inventory, Services Catalog, Procurement
2. WHEN a user with Admin role accesses this group THEN the system SHALL grant full access to all menus
3. WHEN a user with Pharmacist role accesses this group THEN the system SHALL grant access to Inventory only
4. WHEN a user with Staff role accesses this group THEN the system SHALL grant access to Billing & Orders only

---

### Requirement 6: กลุ่ม 5 - Facility Setup

**User Story:** As an owner/admin, I want to configure system settings in one place.

#### Menu Structure

| New Menu (EN) | ชื่อภาษาไทย (UI) | Features (ฟีเจอร์ย่อย) | Role Access |
|---------------|------------------|------------------------|-------------|
| Facility Profile | ข้อมูลสถานพยาบาล | ตั้งค่าร้าน/คลินิก, โลโก้, ที่อยู่, เลขใบอนุญาต | Admin, Owner |
| Staff & Roles | บุคลากร & สิทธิ์ | ผู้ใช้ระบบ, เพิ่ม/ลบบุคลากร, กำหนดสิทธิ์ (Role-based Access) | Owner, Admin |
| Integrations | เชื่อมต่อระบบ | API Key, Telegram, เชื่อมต่อ LINE OA, Payment Gateway, ระบบ HIS ภายนอก | Admin (Tech) |
| Consent & PDPA | ความยินยอม & PDPA | Consent/PDPA, แบบฟอร์มขอความยินยอม, นโยบายความเป็นส่วนตัว | Admin |

#### Acceptance Criteria

1. WHEN viewing Facility Setup group THEN the system SHALL display: Facility Profile, Staff & Roles, Integrations, Consent & PDPA
2. WHEN a user with Owner role accesses this group THEN the system SHALL grant full access to all menus
3. WHEN a user with Admin role accesses Staff & Roles THEN the system SHALL restrict access to role configuration
4. WHEN a user with Tech role accesses this group THEN the system SHALL grant access to Integrations only

---

### Requirement 7: Role-Based Menu Visibility

**User Story:** As a system administrator, I want menus to be visible based on user roles.

#### Acceptance Criteria

1. WHEN a user logs in THEN the system SHALL filter menu items based on the user's assigned role
2. WHEN a menu item requires specific role access THEN the system SHALL hide the menu from unauthorized users
3. WHEN role permissions are updated THEN the system SHALL reflect changes immediately in the menu display

---

### Requirement 8: Menu State Persistence

**User Story:** As an admin user, I want my menu expansion state to be remembered.

#### Acceptance Criteria

1. WHEN a user expands or collapses a menu group THEN the system SHALL save the state to local storage
2. WHEN the page reloads THEN the system SHALL restore the previous menu expansion state
3. WHEN a user navigates to a page within a group THEN the system SHALL automatically expand that group

---

### Requirement 9: Quick Access Section

**User Story:** As an admin user, I want to keep the Quick Access section at the top.

#### Acceptance Criteria

1. WHEN the sidebar loads THEN the system SHALL display Quick Access section at the top
2. WHEN Quick Access is displayed THEN the system SHALL show user's customized quick access menus
3. WHEN a user customizes Quick Access THEN the system SHALL save preferences to database
