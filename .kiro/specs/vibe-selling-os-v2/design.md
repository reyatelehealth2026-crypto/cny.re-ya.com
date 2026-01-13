# Design Document: Vibe Selling OS v2 (Pharmacy Edition)

## Overview

Vibe Selling OS v2 เป็นระบบ AI-Powered Pharmacy Assistant สำหรับร้านยาโดยเฉพาะ ช่วยเภสัชกรให้คำปรึกษาลูกค้าได้อย่างมีประสิทธิภาพ โดยใช้ Multi-Modal AI วิเคราะห์รูปอาการ รูปยา ใบสั่งยา พร้อม Drug Interaction Check, Customer Health Profiling, และ Ghost Drafting

ระบบนี้จะแยกเป็นไฟล์ใหม่ (`inbox-v2.php`, `api/inbox-v2.php`) ไม่กระทบระบบเดิม

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    Frontend (inbox-v2.php) - Pharmacy Edition                │
├─────────────────────────────────────────────────────────────────────────────┤
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────────────────────┐ │
│  │  Conversation  │  │   Chat Panel   │  │     Pharmacy HUD Dashboard     │ │
│  │     List       │  │                │  │                                │ │
│  │                │  │ - Messages     │  │ ┌──────────┐ ┌──────────────┐  │ │
│  │ - Health Type  │  │ - Ghost Draft  │  │ │ Drug Info│ │ Interaction  │  │ │
│  │ - Allergy Flag │  │ - Voice Input  │  │ │  Widget  │ │   Checker    │  │ │
│  │ - Stage Badge  │  │ - Quick Actions│  │ └──────────┘ └──────────────┘  │ │
│  └────────────────┘  └────────────────┘  │ ┌──────────┐ ┌──────────────┐  │ │
│                                          │ │ Symptom  │ │  Allergy     │  │ │
│                                          │ │ Analyzer │ │   Warning    │  │ │
│                                          │ └──────────┘ └──────────────┘  │ │
│                                          │ ┌──────────┐ ┌──────────────┐  │ │
│                                          │ │ Pricing  │ │  Medical     │  │ │
│                                          │ │  Engine  │ │   History    │  │ │
│                                          │ └──────────┘ └──────────────┘  │ │
│                                          └────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

## API Layer

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      API Layer (api/inbox-v2.php)                            │
├─────────────────────────────────────────────────────────────────────────────┤
│  - POST /analyze-symptom    - Analyze symptom images (rash, wound)          │
│  - POST /analyze-drug       - Identify drug from photo                      │
│  - POST /analyze-prescription - OCR prescription and extract drugs          │
│  - GET  /customer-health    - Health profile with allergies & medications   │
│  - POST /ghost-draft        - Generate pharmacy consultation response       │
│  - GET  /drug-info          - Drug details, dosage, contraindications       │
│  - POST /check-interactions - Check drug-drug interactions                  │
│  - GET  /recommendations    - Smart drug suggestions based on symptoms      │
│  - GET  /context-widgets    - HUD widgets based on conversation             │
│  - GET  /consultation-stage - Detect current consultation stage             │
│  - GET  /analytics          - Pharmacy consultation analytics               │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Service Layer

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      Service Layer (classes/)                                │
├─────────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐  │
│  │ PharmacyImageAnalyzer│ │ CustomerHealthEngine│  │  DrugPricingEngine  │  │
│  │                     │  │                     │  │                     │  │
│  │ - analyzeSymptom()  │  │ - getHealthProfile()│  │ - calculateMargin() │  │
│  │ - identifyDrug()    │  │ - getAllergies()    │  │ - getMaxDiscount()  │  │
│  │ - ocrPrescription() │  │ - getMedications()  │  │ - suggestAlternative│  │
│  └─────────────────────┘  └─────────────────────┘  └─────────────────────┘  │
│  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐  │
│  │ PharmacyGhostDraft  │  │ DrugRecommendEngine │  │ ConsultationAnalyzer│  │
│  │                     │  │                     │  │                     │  │
│  │ - generateDraft()   │  │ - getForSymptoms()  │  │ - detectStage()     │  │
│  │ - learnFromEdit()   │  │ - checkInteractions()│ │ - getContextWidgets()│ │
│  │ - addDisclaimer()   │  │ - getRefillReminder()│ │ - getQuickActions() │  │
│  └─────────────────────┘  └─────────────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. PharmacyImageAnalyzerService

```php
class PharmacyImageAnalyzerService {
    /**
     * Analyze symptom image (rash, wound, skin condition)
     * @param string $imageUrl URL of the symptom image
     * @return array ['condition' => string, 'severity' => string, 'urgency' => bool, 'recommendations' => array]
     */
    public function analyzeSymptom(string $imageUrl): array;
    
    /**
     * Identify drug from photo
     * @param string $imageUrl URL of the drug/medicine photo
     * @return array ['drugName' => string, 'genericName' => string, 'dosage' => string, 'usage' => string, 'contraindications' => array]
     */
    public function identifyDrug(string $imageUrl): array;
    
    /**
     * OCR prescription and extract drug list
     * @param string $imageUrl URL of the prescription image
     * @return array ['drugs' => array, 'doctor' => string, 'date' => string, 'hospital' => string]
     */
    public function ocrPrescription(string $imageUrl): array;
    
    /**
     * Check if symptom requires urgent hospital visit
     * @param array $analysisResult Result from analyzeSymptom
     * @return array ['isUrgent' => bool, 'reason' => string, 'recommendation' => string]
     */
    public function checkUrgency(array $analysisResult): array;
}
```


### 2. CustomerHealthEngineService

```php
class CustomerHealthEngineService {
    const TYPE_DIRECT = 'A';      // Direct, wants quick answers
    const TYPE_CONCERNED = 'B';   // Worried, needs reassurance
    const TYPE_DETAILED = 'C';    // Wants detailed information
    
    /**
     * Get customer health profile
     * @param int $userId Customer user ID
     * @return array ['allergies' => array, 'medications' => array, 'conditions' => array, 'communicationType' => string]
     */
    public function getHealthProfile(int $userId): array;
    
    /**
     * Get customer's drug allergies
     * @param int $userId Customer user ID
     * @return array List of allergies with severity
     */
    public function getAllergies(int $userId): array;
    
    /**
     * Get customer's current medications
     * @param int $userId Customer user ID
     * @return array List of current medications
     */
    public function getMedications(int $userId): array;
    
    /**
     * Classify customer communication style
     * @param int $userId Customer user ID
     * @param int $minMessages Minimum messages required (default 5)
     * @return array ['type' => string, 'confidence' => float, 'tips' => array]
     */
    public function classifyCustomer(int $userId, int $minMessages = 5): array;
    
    /**
     * Get recommended draft style for customer type
     * @param string $type Communication type (A, B, C)
     * @return array ['maxWords' => int, 'useEmoji' => bool, 'includeDetails' => bool, 'tone' => string]
     */
    public function getDraftStyle(string $type): array;
}
```

### 3. DrugPricingEngineService

```php
class DrugPricingEngineService {
    /**
     * Calculate margin for a drug
     * @param int $drugId Drug/Product ID
     * @param float|null $customPrice Optional custom selling price
     * @return array ['cost' => float, 'price' => float, 'margin' => float, 'marginPercent' => float]
     */
    public function calculateMargin(int $drugId, ?float $customPrice = null): array;
    
    /**
     * Get maximum allowable discount
     * @param int $drugId Drug ID
     * @param float $minMarginPercent Minimum margin to maintain (default 10%)
     * @return array ['maxDiscount' => float, 'maxDiscountPercent' => float, 'floorPrice' => float]
     */
    public function getMaxDiscount(int $drugId, float $minMarginPercent = 10.0): array;
    
    /**
     * Suggest alternatives when discount exceeds threshold
     * @param int $drugId Drug ID
     * @param float $requestedDiscount Requested discount amount
     * @return array ['alternatives' => array] e.g., free delivery, bonus vitamins
     */
    public function suggestAlternatives(int $drugId, float $requestedDiscount): array;
    
    /**
     * Get customer's loyalty status and discount history
     * @param int $userId Customer user ID
     * @return array ['loyaltyTier' => string, 'avgDiscount' => float, 'totalPurchases' => float]
     */
    public function getCustomerLoyalty(int $userId): array;
}
```


### 4. PharmacyGhostDraftService

```php
class PharmacyGhostDraftService {
    /**
     * Generate predictive pharmacy consultation response
     * @param int $userId Customer user ID
     * @param string $lastMessage Last customer message
     * @param array $context Additional context (health profile, stage, etc.)
     * @return array ['draft' => string, 'confidence' => float, 'alternatives' => array, 'disclaimer' => string|null]
     */
    public function generateDraft(int $userId, string $lastMessage, array $context = []): array;
    
    /**
     * Learn from pharmacist's edit to improve future predictions
     * @param int $userId Customer user ID
     * @param string $originalDraft AI-generated draft
     * @param string $finalMessage Pharmacist's final message
     * @return bool Success
     */
    public function learnFromEdit(int $userId, string $originalDraft, string $finalMessage): bool;
    
    /**
     * Add appropriate disclaimer for prescription drugs
     * @param string $draft Draft message
     * @param array $mentionedDrugs Drugs mentioned in draft
     * @return string Draft with disclaimer if needed
     */
    public function addDisclaimer(string $draft, array $mentionedDrugs): string;
    
    /**
     * Get prediction confidence based on learning history
     * @param int $userId Customer user ID
     * @return float Confidence score 0-1
     */
    public function getPredictionConfidence(int $userId): float;
}
```

### 5. DrugRecommendEngineService

```php
class DrugRecommendEngineService {
    /**
     * Get drug recommendations for symptoms
     * @param array $symptoms List of symptoms
     * @param int $userId Customer user ID (for allergy check)
     * @param int $limit Max recommendations
     * @return array Drugs with dosage, price, and safety info
     */
    public function getForSymptoms(array $symptoms, int $userId, int $limit = 5): array;
    
    /**
     * Check drug-drug interactions
     * @param array $drugIds List of drug IDs to check
     * @param int $userId Customer user ID (for current medications)
     * @return array ['hasInteractions' => bool, 'interactions' => array, 'severity' => string]
     */
    public function checkInteractions(array $drugIds, int $userId): array;
    
    /**
     * Get refill reminders based on purchase history
     * @param int $userId Customer user ID
     * @return array Drugs due for refill with estimated dates
     */
    public function getRefillReminders(int $userId): array;
    
    /**
     * Generate drug card message for LINE
     * @param int $drugId Drug ID
     * @return array Flex message structure
     */
    public function generateDrugCard(int $drugId): array;
    
    /**
     * Get safe alternatives for customers with allergies/conditions
     * @param int $drugId Original drug ID
     * @param int $userId Customer user ID
     * @return array Safe alternative drugs
     */
    public function getSafeAlternatives(int $drugId, int $userId): array;
}
```


### 6. ConsultationAnalyzerService

```php
class ConsultationAnalyzerService {
    const STAGE_SYMPTOM = 'symptom_assessment';
    const STAGE_RECOMMENDATION = 'drug_recommendation';
    const STAGE_PURCHASE = 'purchase';
    const STAGE_FOLLOWUP = 'follow_up';
    
    /**
     * Detect current consultation stage
     * @param int $userId Customer user ID
     * @return array ['stage' => string, 'confidence' => float, 'signals' => array]
     */
    public function detectStage(int $userId): array;
    
    /**
     * Get context-aware widgets based on message keywords
     * @param string $message Customer message
     * @param int $userId Customer user ID
     * @return array Widgets to display with their data
     */
    public function getContextWidgets(string $message, int $userId): array;
    
    /**
     * Get quick actions for current consultation stage
     * @param string $stage Current consultation stage
     * @param bool $hasUrgentSymptoms Whether urgent symptoms detected
     * @return array Available actions with labels and handlers
     */
    public function getQuickActions(string $stage, bool $hasUrgentSymptoms = false): array;
    
    /**
     * Detect if symptoms require hospital referral
     * @param int $userId Customer user ID
     * @return array ['needsReferral' => bool, 'reason' => string, 'urgency' => string]
     */
    public function detectUrgency(int $userId): array;
}
```

## Data Models

### New Tables

```sql
-- Customer Health Profiles
CREATE TABLE customer_health_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    communication_type ENUM('A', 'B', 'C') COMMENT 'A=Direct, B=Concerned, C=Detailed',
    confidence DECIMAL(3,2) DEFAULT 0.00,
    chronic_conditions JSON COMMENT 'List of chronic conditions',
    communication_tips TEXT,
    last_analyzed_at DATETIME,
    message_count_analyzed INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (communication_type)
);

-- Symptom Analysis Cache
CREATE TABLE symptom_analysis_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_hash VARCHAR(64) NOT NULL UNIQUE,
    image_url TEXT,
    analysis_result JSON COMMENT 'Condition, severity, recommendations',
    is_urgent TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    INDEX idx_hash (image_hash),
    INDEX idx_urgent (is_urgent)
);

-- Drug Recognition Cache
CREATE TABLE drug_recognition_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_hash VARCHAR(64) NOT NULL UNIQUE,
    image_url TEXT,
    drug_name VARCHAR(255),
    generic_name VARCHAR(255),
    matched_product_id INT,
    recognition_result JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    INDEX idx_hash (image_hash),
    INDEX idx_product (matched_product_id)
);
```


```sql
-- Prescription OCR Results
CREATE TABLE prescription_ocr_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_hash VARCHAR(64) NOT NULL,
    image_url TEXT,
    extracted_drugs JSON COMMENT 'List of drugs from prescription',
    doctor_name VARCHAR(255),
    hospital_name VARCHAR(255),
    prescription_date DATE,
    ocr_confidence DECIMAL(3,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_hash (image_hash)
);

-- Pharmacy Ghost Draft Learning
CREATE TABLE pharmacy_ghost_learning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_message TEXT NOT NULL,
    ai_draft TEXT NOT NULL,
    pharmacist_final TEXT NOT NULL,
    edit_distance INT COMMENT 'Levenshtein distance',
    was_accepted TINYINT(1) DEFAULT 0,
    context JSON COMMENT 'Stage, health profile, symptoms, etc.',
    mentioned_drugs JSON COMMENT 'Drugs mentioned in conversation',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_accepted (was_accepted)
);

-- Consultation Stage Tracking
CREATE TABLE consultation_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stage ENUM('symptom_assessment', 'drug_recommendation', 'purchase', 'follow_up') NOT NULL,
    confidence DECIMAL(3,2) DEFAULT 0.00,
    signals JSON COMMENT 'Detected signals',
    has_urgent_symptoms TINYINT(1) DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user (user_id),
    INDEX idx_stage (stage),
    INDEX idx_urgent (has_urgent_symptoms)
);

-- Pharmacy Context Keywords
CREATE TABLE pharmacy_context_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100) NOT NULL,
    keyword_type ENUM('symptom', 'drug', 'condition', 'action') NOT NULL,
    widget_type ENUM('drug_info', 'interaction', 'symptom', 'allergy', 'pricing', 'pregnancy') NOT NULL,
    related_data JSON COMMENT 'Related drug IDs, condition info, etc.',
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_keyword (keyword),
    INDEX idx_widget (widget_type)
);

-- Consultation Analytics
CREATE TABLE consultation_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pharmacist_id INT,
    communication_type ENUM('A', 'B', 'C'),
    stage_at_close VARCHAR(50),
    response_time_avg INT COMMENT 'Average response time in seconds',
    message_count INT,
    ai_suggestions_shown INT DEFAULT 0,
    ai_suggestions_accepted INT DEFAULT 0,
    resulted_in_purchase TINYINT(1) DEFAULT 0,
    purchase_amount DECIMAL(12,2),
    symptom_categories JSON COMMENT 'Categories of symptoms discussed',
    drugs_recommended JSON COMMENT 'Drugs recommended in consultation',
    successful_patterns JSON COMMENT 'Patterns that led to purchase',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_pharmacist (pharmacist_id),
    INDEX idx_purchase (resulted_in_purchase)
);
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Drug Margin Calculation Correctness
*For any* drug with cost and price data, the calculated margin percentage should equal `((price - cost) / price) * 100`.
**Validates: Requirements 3.1**

### Property 2: Maximum Discount Preserves Minimum Margin
*For any* drug and minimum margin threshold, the calculated maximum discount should result in a final price that maintains at least the minimum margin percentage.
**Validates: Requirements 3.2**

### Property 3: Discount Threshold Triggers Alternatives
*For any* discount request exceeding the maximum allowable discount, the system should return alternative suggestions instead of approving the discount.
**Validates: Requirements 3.3**

### Property 4: Health Profile Classification Completeness
*For any* customer with 5 or more messages, the classification should return exactly one of the valid types (A, B, C) with a confidence score between 0 and 1.
**Validates: Requirements 2.1**

### Property 5: Draft Style Matches Communication Type
*For any* Type A customer, generated drafts should be concise (under 50 words). *For any* Type B customer, drafts should include empathetic language. *For any* Type C customer, drafts should include detailed drug information.
**Validates: Requirements 2.2, 2.3, 2.4**

### Property 6: Symptom Keyword Triggers Drug Widget
*For any* message containing a registered symptom keyword, the system should return the drug recommendation widget with appropriate OTC medications.
**Validates: Requirements 4.1**

### Property 7: Drug Name Triggers Info Widget
*For any* message containing a registered drug name, the system should return the drug info widget with dosage, side effects, and contraindications.
**Validates: Requirements 4.2**

### Property 8: Allergy Check Before Recommendation
*For any* drug recommendation, the system should check against customer's allergy list and exclude drugs that match any allergy.
**Validates: Requirements 2.6, 7.2**

### Property 9: Drug Interaction Detection
*For any* set of drugs being recommended, the system should check for interactions with customer's current medications and flag any dangerous combinations.
**Validates: Requirements 1.4, 7.2**

### Property 10: Recommendations Exclude Out-of-Stock Drugs
*For any* drug recommendation, the recommended drugs should have stock quantity greater than zero.
**Validates: Requirements 10.2**

### Property 11: Ghost Draft Learning Stores Edit Data
*For any* pharmacist edit of an AI draft, the system should store the original draft, final message, and edit distance for learning.
**Validates: Requirements 6.5**

### Property 12: Prescription Drug Disclaimer
*For any* draft mentioning prescription-only drugs, the system should automatically add a disclaimer about consulting a doctor.
**Validates: Requirements 6.6**

### Property 13: Urgent Symptom Detection
*For any* symptom analysis with severity level "severe" or "urgent", the system should flag the conversation and recommend hospital visit.
**Validates: Requirements 1.5**

## Error Handling

1. **AI Vision API Timeout**: Cache previous results, show "กำลังวิเคราะห์..." state, retry with exponential backoff
2. **Drug Not Found in Database**: Show generic information with disclaimer, suggest consulting pharmacist
3. **Insufficient Health Data**: Return "Unknown" type with 0 confidence, prompt to collect allergy/medication info
4. **Prescription OCR Failure**: Ask customer to send clearer image or type drug names manually
5. **Drug Interaction API Error**: Show warning that interaction check unavailable, recommend caution
6. **Voice Recognition Failure**: Ask for text input fallback

## Testing Strategy

### Unit Tests
- Test margin calculations with various cost/price combinations
- Test health profile classification with mock chat histories
- Test symptom keyword detection with various message patterns
- Test drug interaction checking with known interaction pairs

### Property-Based Tests
- Use PHPUnit with data providers for property-based testing
- Generate random drugs and verify margin calculations
- Generate random discount requests and verify threshold logic
- Generate random drug combinations and verify interaction detection

### Integration Tests
- Test full flow: symptom image → analysis → drug recommendation → purchase
- Test prescription OCR → drug list extraction → interaction check
- Test allergy warning display when recommending contraindicated drugs

### Performance Tests
- Ghost draft generation under 2 seconds
- Image analysis under 5 seconds
- Widget transition under 300ms
- Handle 50+ concurrent consultations
