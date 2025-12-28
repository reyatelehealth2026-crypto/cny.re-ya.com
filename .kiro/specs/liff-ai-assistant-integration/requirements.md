# Requirements Document

## Introduction

ระบบ LIFF AI Assistant Integration เป็นการเชื่อมต่อหน้า `/liff/#/ai-assistant` เข้ากับระบบ AI ที่มีอยู่แล้วในระบบ ได้แก่:
- **MIMSPharmacistAI** - AI เภสัชกรที่ใช้ข้อมูลจาก MIMS Pharmacy Thailand 2023
- **PharmacyAIAdapter** - Adapter สำหรับ AI เภสัชกรพร้อม RAG และ Function Calling
- **TriageEngine** - State Machine สำหรับซักประวัติอาการเป็นขั้นตอน
- **RedFlagDetector** - ตรวจจับอาการฉุกเฉินที่ต้องพบแพทย์
- **pharmacist-dashboard** - แดชบอร์ดสำหรับเภสัชกรดูเคสที่ต้องตรวจสอบ
- **triage-analytics** - รายงานสถิติการซักประวัติ

เป้าหมายคือให้ผู้ใช้สามารถปรึกษาอาการผ่าน LIFF App และได้รับคำแนะนำจาก AI เภสัชกรที่มีความรู้จาก MIMS พร้อมระบบตรวจจับอาการฉุกเฉินและส่งต่อเภสัชกรจริงเมื่อจำเป็น

## Glossary

- **LIFF_AI_Assistant**: หน้า AI Chat ใน LIFF App ที่ route `/ai-assistant`
- **MIMSPharmacistAI**: Class ที่ใช้ข้อมูล MIMS เป็นฐานความรู้สำหรับ AI เภสัชกร
- **PharmacyAIAdapter**: Adapter class ที่รวม RAG, Function Calling และ Triage
- **TriageEngine**: State Machine สำหรับซักประวัติอาการเป็นขั้นตอน (greeting → symptom → duration → severity → associated → allergy → medical_history → current_meds → recommend)
- **RedFlagDetector**: Class ตรวจจับอาการฉุกเฉิน (Critical/Warning)
- **Pharmacist_Dashboard**: หน้าแดชบอร์ดสำหรับเภสัชกรดูเคสที่ต้องตรวจสอบ
- **Triage_Analytics**: หน้ารายงานสถิติการซักประวัติ
- **Triage_Session**: Session การซักประวัติที่บันทึกใน database
- **Red_Flag**: อาการฉุกเฉินที่ต้องพบแพทย์ทันที (Critical) หรือควรพบแพทย์เร็ว (Warning)

## Requirements

### Requirement 1

**User Story:** As a LIFF user, I want to chat with AI pharmacist through the AI Assistant page, so that I can get medication advice based on MIMS knowledge.

#### Acceptance Criteria

1. WHEN a user navigates to `/ai-assistant` route THEN the LIFF_AI_Assistant SHALL initialize connection to PharmacyAIAdapter with user context
2. WHEN a user sends a message THEN the LIFF_AI_Assistant SHALL call the pharmacy-ai.php API with user_id and message
3. WHEN the API receives a message THEN the PharmacyAIAdapter SHALL process the message using MIMSPharmacistAI knowledge base
4. WHEN the AI responds THEN the LIFF_AI_Assistant SHALL display the response in chat bubble format with smooth animation
5. WHEN the AI recommends products THEN the LIFF_AI_Assistant SHALL display product cards with name, price, and add-to-cart action

### Requirement 2

**User Story:** As a LIFF user, I want the AI to assess my symptoms step-by-step, so that I can get accurate medication recommendations.

#### Acceptance Criteria

1. WHEN a user starts symptom consultation THEN the TriageEngine SHALL initialize a new triage session with state 'greeting'
2. WHEN the TriageEngine is in 'symptom' state THEN the system SHALL ask for main symptoms and extract symptom keywords
3. WHEN the TriageEngine is in 'duration' state THEN the system SHALL ask how long symptoms have persisted
4. WHEN the TriageEngine is in 'severity' state THEN the system SHALL ask for severity level (1-10 scale)
5. WHEN the TriageEngine is in 'allergy' state THEN the system SHALL ask about drug allergies and save to user profile
6. WHEN triage data is complete THEN the TriageEngine SHALL generate medication recommendations based on symptoms and user profile

### Requirement 3

**User Story:** As a LIFF user, I want the system to detect emergency symptoms, so that I can get immediate help when needed.

#### Acceptance Criteria

1. WHEN a user message contains critical red flag patterns (chest pain, difficulty breathing, seizure, stroke symptoms) THEN the RedFlagDetector SHALL return critical severity flag
2. WHEN critical red flags are detected THEN the LIFF_AI_Assistant SHALL display emergency alert with emergency contact numbers (1669, 1323, 1367)
3. WHEN warning red flags are detected (high fever, severe headache, bloody stool) THEN the LIFF_AI_Assistant SHALL display warning message with recommendation to see doctor
4. WHEN red flags are detected THEN the system SHALL log the emergency alert to database for pharmacist review

### Requirement 4

**User Story:** As a LIFF user, I want to escalate to a real pharmacist, so that I can get professional consultation when AI cannot help.

#### Acceptance Criteria

1. WHEN a user requests pharmacist consultation THEN the system SHALL create a notification in pharmacist_notifications table
2. WHEN triage severity is high or critical THEN the system SHALL automatically notify pharmacist via pharmacist_notifications
3. WHEN pharmacist notification is created THEN the Pharmacist_Dashboard SHALL display the notification with user info and triage data
4. WHEN a user clicks "Video Call" button THEN the LIFF_AI_Assistant SHALL navigate to video-call page with pharmacist context

### Requirement 5

**User Story:** As a pharmacist, I want to see triage analytics, so that I can monitor AI consultation patterns and improve service.

#### Acceptance Criteria

1. WHEN a triage session is completed THEN the system SHALL update triage_sessions table with status and completion time
2. WHEN pharmacist views Triage_Analytics page THEN the system SHALL display total sessions, completion rate, and urgent cases count
3. WHEN pharmacist views Triage_Analytics page THEN the system SHALL display top symptoms chart and daily session trends
4. WHEN pharmacist filters by date range THEN the Triage_Analytics SHALL update statistics for the selected period

### Requirement 6

**User Story:** As a LIFF user, I want my conversation history to be saved per user, so that I can continue consultation later and AI remembers my context.

#### Acceptance Criteria

1. WHEN a user sends a message THEN the system SHALL save the message to ai_conversation_history table with user_id, role, and session_id
2. WHEN a user opens AI Assistant page THEN the system SHALL load previous conversation history for that specific user and display in chat
3. WHEN a user clears chat history THEN the system SHALL delete conversation history for that user only from database
4. WHEN conversation history is loaded THEN the LIFF_AI_Assistant SHALL display messages in chronological order with timestamps
5. WHEN AI processes a message THEN the system SHALL include the last 10 conversation messages as context for continuity

### Requirement 9

**User Story:** As a LIFF user, I want the AI to maintain conversation continuity within a session, so that I don't have to repeat information.

#### Acceptance Criteria

1. WHEN a triage session is active THEN the AI SHALL NOT restart with greeting message until session is completed or explicitly reset
2. WHEN user provides an answer to AI's question THEN the AI SHALL acknowledge the answer and proceed to the next question in sequence
3. WHEN user sends a numeric response (e.g., "9", "7") THEN the AI SHALL interpret it as answer to the previous severity question
4. WHEN user sends a duration response (e.g., "7 วัน", "2 สัปดาห์") THEN the AI SHALL interpret it as answer to the duration question
5. WHEN triage session state is not 'complete' or 'escalate' THEN the AI SHALL continue from current state without resetting

### Requirement 10

**User Story:** As a LIFF user, I want the AI to understand its pharmacist role consistently, so that I get professional pharmacy advice throughout the conversation.

#### Acceptance Criteria

1. WHEN AI responds to any message THEN the AI SHALL maintain pharmacist persona with MIMS knowledge base context
2. WHEN AI provides medication advice THEN the AI SHALL reference MIMS Pharmacy Thailand 2023 guidelines
3. WHEN AI asks follow-up questions THEN the AI SHALL follow the triage protocol (Symptom → Duration → Severity → Associated → Allergy → Medical History → Current Meds → Recommendation)
4. WHEN user asks off-topic questions THEN the AI SHALL politely redirect to pharmacy-related consultation
5. WHEN AI generates response THEN the system SHALL include system prompt with pharmacist role and MIMS context in every API call

### Requirement 7

**User Story:** As a LIFF user, I want the AI to check drug interactions, so that I can avoid dangerous medication combinations.

#### Acceptance Criteria

1. WHEN AI recommends medications THEN the system SHALL check interactions with user's current medications from health profile
2. WHEN drug interaction is detected THEN the LIFF_AI_Assistant SHALL display warning message with interaction details
3. WHEN user has drug allergies THEN the system SHALL filter out allergic medications from recommendations
4. WHEN interaction check is complete THEN the system SHALL include interaction warnings in AI response

### Requirement 8

**User Story:** As a LIFF user, I want quick symptom selection buttons, so that I can start consultation faster.

#### Acceptance Criteria

1. WHEN AI Assistant page loads THEN the LIFF_AI_Assistant SHALL display quick symptom buttons (headache, cold, cough, stomach pain, allergy, muscle pain)
2. WHEN a user clicks a quick symptom button THEN the system SHALL send the symptom text to AI and start triage process
3. WHEN quick symptom is selected THEN the LIFF_AI_Assistant SHALL hide the quick symptom section and show chat interface
4. WHEN AI responds to quick symptom THEN the system SHALL display follow-up questions based on TriageEngine state
