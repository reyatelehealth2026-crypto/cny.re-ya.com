# Requirements Document

## Introduction

ระบบ E-commerce สำหรับร้านขายยาออนไลน์ (Pharmacy E-commerce Platform) ที่ทำงานผ่าน LINE Official Account โดยมีฟีเจอร์หลักคือการขายยาและเวชภัณฑ์ทั่วไป รวมถึงยาที่ต้องมีใบสั่งแพทย์ (Prescription Required) ระบบรองรับการปรึกษาเภสัชกรผ่าน Video Call, ระบบสมาชิก Loyalty Points, การจัดการสต็อกสินค้า และการชำระเงินออนไลน์

## Glossary

- **Pharmacy_System**: ระบบ E-commerce ร้านขายยาออนไลน์ที่ทำงานผ่าน LINE
- **OTC_Product**: สินค้าประเภทยาและเวชภัณฑ์ที่ซื้อขายได้ทั่วไปโดยไม่ต้องมีใบสั่งแพทย์ (Over-The-Counter)
- **Prescription_Product**: สินค้าประเภทยาที่ต้องมีใบสั่งแพทย์หรือการอนุมัติจากเภสัชกรก่อนจำหน่าย
- **Pharmacist**: เภสัชกรที่มีสิทธิ์อนุมัติการจ่ายยาประเภท Prescription_Product
- **LINE_User**: ผู้ใช้งานที่เข้าถึงระบบผ่าน LINE Official Account
- **Admin**: ผู้ดูแลระบบที่มีสิทธิ์จัดการ LINE OA, สินค้า, คำสั่งซื้อ และผู้ใช้
- **LIFF**: LINE Front-end Framework สำหรับสร้างหน้าเว็บภายใน LINE App
- **Video_Call_Session**: การสนทนาผ่านวิดีโอระหว่าง LINE_User และ Pharmacist
- **Stock_Movement**: การเคลื่อนไหวของสต็อกสินค้า (รับเข้า, ขายออก, ปรับปรุง)
- **Purchase_Order**: ใบสั่งซื้อสินค้าจาก Supplier
- **Loyalty_Points**: แต้มสะสมที่ได้รับจากการซื้อสินค้า

## Requirements

### Requirement 1: Product Classification and Access Control

**User Story:** As a pharmacy owner, I want to classify products into OTC and prescription categories, so that prescription drugs are only dispensed with proper authorization.

#### Acceptance Criteria

1. WHEN an Admin creates a product THEN the Pharmacy_System SHALL require selection of product type as either OTC_Product or Prescription_Product
2. WHEN a LINE_User adds an OTC_Product to cart THEN the Pharmacy_System SHALL allow immediate addition without additional verification
3. WHEN a LINE_User attempts to purchase a Prescription_Product THEN the Pharmacy_System SHALL require Pharmacist approval before completing the order
4. WHEN a Pharmacist reviews a Prescription_Product order THEN the Pharmacy_System SHALL display patient information and allow approve or reject actions
5. IF a Prescription_Product order is rejected THEN the Pharmacy_System SHALL notify the LINE_User with the rejection reason and remove the item from the order

### Requirement 2: Video Call Consultation

**User Story:** As a customer, I want to consult with a pharmacist via video call, so that I can get professional advice before purchasing prescription drugs.

#### Acceptance Criteria

1. WHEN a LINE_User requests a pharmacist consultation THEN the Pharmacy_System SHALL create a Video_Call_Session and notify available Pharmacists
2. WHEN a Pharmacist accepts a consultation request THEN the Pharmacy_System SHALL establish a peer-to-peer video connection between both parties
3. WHILE a Video_Call_Session is active THEN the Pharmacy_System SHALL display patient medical history and current cart items to the Pharmacist
4. WHEN a Video_Call_Session ends THEN the Pharmacy_System SHALL record the session duration and consultation notes
5. IF no Pharmacist is available within 5 minutes THEN the Pharmacy_System SHALL notify the LINE_User and offer to schedule an appointment

### Requirement 3: Shopping Cart and Checkout

**User Story:** As a customer, I want to browse products, add them to cart, and complete checkout, so that I can purchase pharmacy products online.

#### Acceptance Criteria

1. WHEN a LINE_User browses the LIFF shop THEN the Pharmacy_System SHALL display products with images, prices, and stock availability
2. WHEN a LINE_User adds a product to cart THEN the Pharmacy_System SHALL update the cart total and persist the cart state
3. WHEN a LINE_User proceeds to checkout THEN the Pharmacy_System SHALL validate stock availability for all cart items
4. IF any cart item has insufficient stock THEN the Pharmacy_System SHALL notify the LINE_User and prevent checkout completion
5. WHEN a LINE_User completes payment THEN the Pharmacy_System SHALL create an order record and reduce stock quantities accordingly
6. WHEN an order is created THEN the Pharmacy_System SHALL send order confirmation via LINE message

### Requirement 4: Payment Processing

**User Story:** As a customer, I want to pay for my orders using bank transfer with slip upload, so that I can complete purchases conveniently.

#### Acceptance Criteria

1. WHEN a LINE_User selects payment method THEN the Pharmacy_System SHALL display bank account details for transfer
2. WHEN a LINE_User uploads a payment slip THEN the Pharmacy_System SHALL store the slip image and mark the order as pending verification
3. WHEN an Admin verifies a payment slip THEN the Pharmacy_System SHALL update order status to confirmed and notify the LINE_User
4. IF a payment slip is rejected THEN the Pharmacy_System SHALL notify the LINE_User with the rejection reason and allow re-upload
5. WHEN serializing order data for storage THEN the Pharmacy_System SHALL encode using JSON format
6. WHEN retrieving order data from storage THEN the Pharmacy_System SHALL decode JSON and reconstruct the order object

### Requirement 5: Inventory Management

**User Story:** As a pharmacy manager, I want to track stock levels and manage inventory, so that I can ensure product availability and prevent stockouts.

#### Acceptance Criteria

1. WHEN a product stock falls below the reorder threshold THEN the Pharmacy_System SHALL generate a low stock alert for Admin
2. WHEN an Admin creates a Purchase_Order THEN the Pharmacy_System SHALL record supplier, products, quantities, and expected delivery date
3. WHEN goods are received against a Purchase_Order THEN the Pharmacy_System SHALL increase stock quantities and create Stock_Movement records
4. WHEN an Admin performs stock adjustment THEN the Pharmacy_System SHALL record the adjustment reason and update stock quantities
5. WHEN querying stock movements THEN the Pharmacy_System SHALL return movements filtered by date range and movement type

### Requirement 6: Member and Loyalty System

**User Story:** As a customer, I want to earn and redeem loyalty points, so that I can get rewards for my purchases.

#### Acceptance Criteria

1. WHEN a LINE_User completes a purchase THEN the Pharmacy_System SHALL calculate and award Loyalty_Points based on order total
2. WHEN a LINE_User views their member card THEN the Pharmacy_System SHALL display current point balance and membership tier
3. WHEN a LINE_User redeems points THEN the Pharmacy_System SHALL validate sufficient balance and apply discount to order
4. IF redemption amount exceeds available points THEN the Pharmacy_System SHALL reject the redemption and display available balance
5. WHEN points are earned or redeemed THEN the Pharmacy_System SHALL create a points transaction record with timestamp

### Requirement 7: Multi-Account LINE OA Management

**User Story:** As a platform administrator, I want to manage multiple LINE OA accounts, so that I can operate multiple pharmacy branches from one system.

#### Acceptance Criteria

1. WHEN an Admin adds a LINE OA account THEN the Pharmacy_System SHALL store channel credentials and generate a unique webhook URL
2. WHEN a webhook receives a message THEN the Pharmacy_System SHALL identify the target LINE OA account and route to appropriate handler
3. WHEN an Admin configures Rich Menu THEN the Pharmacy_System SHALL apply the menu to the selected LINE OA account
4. WHEN broadcasting messages THEN the Pharmacy_System SHALL send to users of the selected LINE OA account only
5. WHEN viewing analytics THEN the Pharmacy_System SHALL aggregate data per LINE OA account

### Requirement 8: Auto Reply and AI Chatbot

**User Story:** As a pharmacy owner, I want automated responses and AI assistance, so that customers can get help outside business hours.

#### Acceptance Criteria

1. WHEN a LINE_User sends a message matching auto-reply keywords THEN the Pharmacy_System SHALL respond with the configured reply message
2. WHEN a LINE_User asks about symptoms or medications THEN the Pharmacy_System SHALL invoke AI chatbot for intelligent response
3. WHEN AI detects emergency symptoms (red flags) THEN the Pharmacy_System SHALL immediately alert the Pharmacist and advise the user to seek emergency care
4. WHEN AI recommends products THEN the Pharmacy_System SHALL display product cards with add-to-cart functionality
5. WHEN AI conversation history is requested THEN the Pharmacy_System SHALL retrieve previous messages for context continuity

### Requirement 9: Broadcast and Campaign Management

**User Story:** As a marketing manager, I want to send targeted broadcasts and run drip campaigns, so that I can engage customers effectively.

#### Acceptance Criteria

1. WHEN an Admin creates a broadcast THEN the Pharmacy_System SHALL allow selection of target audience by tags or segments
2. WHEN a broadcast is scheduled THEN the Pharmacy_System SHALL queue the messages and send at the specified time
3. WHEN a drip campaign is triggered THEN the Pharmacy_System SHALL send sequential messages based on configured delays
4. WHEN tracking broadcast performance THEN the Pharmacy_System SHALL record delivery status and engagement metrics
5. WHEN a user unsubscribes THEN the Pharmacy_System SHALL exclude them from future broadcasts

### Requirement 10: Reporting and Analytics

**User Story:** As a business owner, I want comprehensive reports and analytics, so that I can make data-driven decisions.

#### Acceptance Criteria

1. WHEN an Admin views the dashboard THEN the Pharmacy_System SHALL display key metrics including sales, orders, and active users
2. WHEN generating sales reports THEN the Pharmacy_System SHALL aggregate data by date range, product category, and payment status
3. WHEN analyzing customer behavior THEN the Pharmacy_System SHALL show purchase frequency, average order value, and retention rates
4. WHEN exporting reports THEN the Pharmacy_System SHALL generate downloadable files in CSV or PDF format
5. WHEN scheduling reports THEN the Pharmacy_System SHALL automatically generate and email reports at configured intervals
