# Requirements Document: Vibe Selling OS v2 (Pharmacy Edition)

## Introduction

ระบบ Vibe Selling OS v2 เป็นการอัพเกรดระบบ Inbox Chat สำหรับร้านยาโดยเฉพาะ ให้เป็น AI-Powered Pharmacy Assistant ที่ช่วยเภสัชกรให้คำปรึกษาลูกค้าได้อย่างมีประสิทธิภาพ วิเคราะห์รูปอาการ รูปยา และใบสั่งยา พร้อมแนะนำยาที่เหมาะสม โดยแยกเป็นไฟล์ใหม่ (inbox-v2.php) ไม่กระทบระบบเดิม

## Glossary

- **Vibe Selling OS**: ระบบช่วยเภสัชกรอัจฉริยะที่ใช้ AI วิเคราะห์และแนะนำการตอบลูกค้า
- **Hyper-Brain**: แกนสมอง AI ที่วิเคราะห์ข้อมูลหลายมิติ (อาการ, ยา, ใบสั่งยา)
- **Multi-Modal RAG**: ระบบค้นหาข้อมูลที่รองรับทั้ง Text, Image (รูปอาการ/ยา/ใบสั่งยา), Audio
- **Symptom Analysis**: การวิเคราะห์อาการจากรูปภาพ (ผื่น, บาดแผล, อาการผิดปกติ)
- **Drug Recognition**: การระบุยาจากรูปภาพ (ชื่อยา, สรรพคุณ, ข้อควรระวัง)
- **Prescription OCR**: การอ่านใบสั่งยาด้วย AI และแปลงเป็นรายการยา
- **Drug Interaction Check**: ตรวจสอบปฏิกิริยาระหว่างยา
- **Psychographic Profile**: โปรไฟล์จิตวิทยาการตัดสินใจของลูกค้า
- **Dynamic Pricing Engine**: ระบบคำนวณราคาและกำไรแบบ Real-time
- **HUD Dashboard**: หน้าจอแสดงข้อมูลแบบ Context-Aware (ข้อมูลยา, อาการ, ประวัติ)
- **Ghost Drafting**: ระบบเดาใจและพิมพ์คำตอบรอไว้ล่วงหน้า
- **Medical History Panel**: แผงแสดงประวัติทางการแพทย์ของลูกค้า

## Requirements

### Requirement 1: Pharmacy Multi-Modal Analysis System

**User Story:** As a pharmacist, I want AI to analyze symptom images, drug photos, and prescriptions from customers, so that I can provide accurate pharmaceutical advice.

#### Acceptance Criteria

1. WHEN a customer sends a symptom image (rash, wound, skin condition) THEN the system SHALL analyze and identify possible conditions with severity level
2. WHEN a customer sends a drug/medicine photo THEN the system SHALL identify the drug name, dosage, usage instructions, and contraindications
3. WHEN a customer sends a prescription image THEN the system SHALL OCR extract drug names, dosages, and create a structured drug list
4. WHEN analyzing prescription THEN the system SHALL check for drug interactions and allergies against customer's medical history
5. WHEN symptom analysis suggests urgent care needed THEN the system SHALL display warning and recommend hospital visit
6. WHEN image analysis completes THEN the system SHALL display results in a floating widget within 3 seconds

### Requirement 2: Customer Health Profiling

**User Story:** As a pharmacist, I want to know customer's health background and communication style, so that I can provide personalized pharmaceutical care.

#### Acceptance Criteria

1. WHEN a customer has sufficient chat history (minimum 5 messages) THEN the system SHALL classify their communication style (Type A/B/C)
2. WHEN customer is Type A (Direct) THEN the system SHALL draft concise responses with clear drug recommendations
3. WHEN customer is Type B (Concerned) THEN the system SHALL draft empathetic responses with detailed explanations and reassurance
4. WHEN customer is Type C (Detail-oriented) THEN the system SHALL prepare drug comparison tables, dosage charts, and scientific information
5. WHEN displaying customer profile THEN the system SHALL show medical history, allergies, current medications, and communication tips
6. WHEN customer has drug allergies on record THEN the system SHALL prominently display allergy warnings during consultation

### Requirement 3: Drug Pricing & Margin Engine

**User Story:** As a pharmacist, I want to know the profit margin in real-time during consultation, so that I can offer appropriate discounts while maintaining profitability.

#### Acceptance Criteria

1. WHEN viewing a drug in chat context THEN the system SHALL display cost, selling price, and current margin percentage
2. WHEN a customer requests a discount THEN the system SHALL calculate maximum allowable discount while maintaining minimum margin
3. WHEN discount exceeds safe threshold THEN the system SHALL suggest alternative offers (free delivery, bonus vitamins) instead
4. WHEN pharmacist enters a custom price THEN the system SHALL show real-time margin impact before confirming
5. WHEN customer has purchase history THEN the system SHALL display their typical discount expectation and loyalty status

### Requirement 4: Pharmacy HUD (Heads-Up Display) Dashboard

**User Story:** As a pharmacist, I want relevant drug and health information to appear automatically based on conversation context, so that I can provide accurate advice quickly.

#### Acceptance Criteria

1. WHEN customer mentions symptoms (headache, fever, cough) THEN the system SHALL display recommended OTC drugs widget
2. WHEN customer mentions a drug name THEN the system SHALL display drug info widget (dosage, side effects, contraindications)
3. WHEN customer asks about "drug interactions" THEN the system SHALL display interaction checker widget
4. WHEN customer mentions "pregnancy" or "breastfeeding" THEN the system SHALL display pregnancy-safe drugs widget
5. WHEN customer has allergies on record THEN the system SHALL display allergy warning widget prominently
6. WHEN context changes THEN the system SHALL smoothly transition widgets with fade animation within 300ms

### Requirement 5: Voice-First Pharmacy Assistant

**User Story:** As a pharmacist multitasking at the counter, I want to use voice commands to query customer and drug information, so that I can work hands-free.

#### Acceptance Criteria

1. WHEN pharmacist activates voice mode THEN the system SHALL listen for wake word "Vibe" followed by command
2. WHEN pharmacist asks about customer history THEN the system SHALL respond with audio summary of medications and allergies
3. WHEN pharmacist asks about drug interactions THEN the system SHALL verbally warn of any dangerous combinations
4. WHEN voice command is unclear THEN the system SHALL ask for clarification without interrupting workflow
5. WHEN in voice mode THEN the system SHALL display visual confirmation of recognized command

### Requirement 6: Ghost Drafting for Pharmacy Consultation

**User Story:** As a pharmacist, I want AI to predict and pre-type my responses based on pharmaceutical knowledge, so that I can reply faster with accurate drug information.

#### Acceptance Criteria

1. WHEN customer asks about symptoms THEN the system SHALL generate a ghost draft with appropriate drug recommendations within 2 seconds
2. WHEN ghost draft is ready THEN the system SHALL display it as faded text in the input field
3. WHEN pharmacist presses Tab THEN the system SHALL accept the ghost draft and allow editing
4. WHEN pharmacist starts typing THEN the system SHALL replace ghost draft with pharmacist's text
5. WHEN pharmacist sends a modified draft THEN the system SHALL learn from the edit to improve future pharmaceutical recommendations
6. WHEN draft includes prescription drugs THEN the system SHALL add disclaimer about consulting a doctor

### Requirement 7: Smart Drug Recommendation Engine

**User Story:** As a pharmacist, I want AI to suggest relevant drugs and supplements based on customer's symptoms and history, so that I can provide comprehensive care.

#### Acceptance Criteria

1. WHEN customer describes symptoms THEN the system SHALL suggest appropriate OTC medications with dosage
2. WHEN recommending a drug THEN the system SHALL check for interactions with customer's current medications
3. WHEN customer purchased medication before THEN the system SHALL suggest refill reminders based on typical usage duration
4. WHEN displaying recommendations THEN the system SHALL show drug category, dosage, price, and stock availability
5. WHEN pharmacist selects a recommendation THEN the system SHALL insert drug card with full information into message composer
6. WHEN customer has chronic conditions THEN the system SHALL prioritize drugs safe for their condition

### Requirement 8: Pharmacy Consultation Analytics

**User Story:** As a pharmacy manager, I want to analyze consultation patterns and outcomes, so that I can improve service quality and pharmacist training.

#### Acceptance Criteria

1. WHEN viewing analytics THEN the system SHALL display consultation success rate by symptom category
2. WHEN viewing analytics THEN the system SHALL show average response time impact on customer satisfaction
3. WHEN viewing analytics THEN the system SHALL identify most common symptoms and recommended treatments
4. WHEN a consultation leads to purchase THEN the system SHALL tag successful consultation patterns for training
5. WHEN viewing individual pharmacist performance THEN the system SHALL show AI suggestion acceptance rate and consultation volume

### Requirement 9: Context-Aware Pharmacy Quick Actions

**User Story:** As a pharmacist, I want quick action buttons that change based on consultation stage, so that I can take appropriate actions faster.

#### Acceptance Criteria

1. WHEN conversation is in symptom assessment stage THEN the system SHALL show actions: Ask Follow-up, Check History, Suggest OTC
2. WHEN conversation is in drug recommendation stage THEN the system SHALL show actions: Send Drug Info, Check Interactions, Apply Discount
3. WHEN conversation is in purchase stage THEN the system SHALL show actions: Create Order, Send Payment Link, Schedule Delivery
4. WHEN customer shows urgency signals THEN the system SHALL highlight "Recommend Hospital Visit" action if symptoms are severe
5. WHEN action is selected THEN the system SHALL pre-fill relevant drug/customer data and await confirmation

### Requirement 10: Integration with Pharmacy Systems

**User Story:** As a system administrator, I want Vibe Selling OS v2 to integrate with existing pharmacy data, so that AI has complete context for drug recommendations.

#### Acceptance Criteria

1. WHEN loading customer profile THEN the system SHALL fetch medical history, allergies, and current medications from users table
2. WHEN recommending drugs THEN the system SHALL check real-time inventory from stock tables
3. WHEN calculating pricing THEN the system SHALL use cost data from business_items table
4. WHEN analyzing customer THEN the system SHALL incorporate existing tags, notes, and prescription history
5. WHEN checking drug interactions THEN the system SHALL use the existing drug_interactions table
6. WHEN v2 is disabled THEN the system SHALL gracefully fallback to v1 inbox without data loss

