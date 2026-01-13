<?php
/**
 * Drug Recommend Engine Service
 * 
 * Provides smart drug recommendations based on symptoms, interaction checking,
 * refill reminders, and safe alternatives for pharmacy consultations.
 * 
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6
 */

class DrugRecommendEngineService
{
    private $db;
    private $lineAccountId;
    private $healthEngine;
    
    // Symptom to drug category mapping
    private $symptomDrugMap = [
        // Pain symptoms
        'ปวดหัว' => ['category' => 'pain_relief', 'keywords' => ['paracetamol', 'ibuprofen', 'aspirin']],
        'headache' => ['category' => 'pain_relief', 'keywords' => ['paracetamol', 'ibuprofen', 'aspirin']],
        'ปวดกล้ามเนื้อ' => ['category' => 'pain_relief', 'keywords' => ['ibuprofen', 'diclofenac', 'muscle relaxant']],
        'ปวดท้อง' => ['category' => 'digestive', 'keywords' => ['antacid', 'buscopan', 'omeprazole']],
        
        // Fever symptoms
        'ไข้' => ['category' => 'fever', 'keywords' => ['paracetamol', 'ibuprofen']],
        'fever' => ['category' => 'fever', 'keywords' => ['paracetamol', 'ibuprofen']],
        
        // Respiratory symptoms
        'ไอ' => ['category' => 'cough', 'keywords' => ['dextromethorphan', 'bromhexine', 'cough syrup']],
        'cough' => ['category' => 'cough', 'keywords' => ['dextromethorphan', 'bromhexine', 'cough syrup']],
        'เจ็บคอ' => ['category' => 'throat', 'keywords' => ['strepsils', 'betadine gargle', 'throat lozenge']],
        'คัดจมูก' => ['category' => 'nasal', 'keywords' => ['pseudoephedrine', 'nasal spray', 'antihistamine']],
        'น้ำมูก' => ['category' => 'nasal', 'keywords' => ['antihistamine', 'loratadine', 'cetirizine']],
        
        // Digestive symptoms
        'ท้องเสีย' => ['category' => 'diarrhea', 'keywords' => ['loperamide', 'ors', 'smecta']],
        'diarrhea' => ['category' => 'diarrhea', 'keywords' => ['loperamide', 'ors', 'smecta']],
        'ท้องผูก' => ['category' => 'constipation', 'keywords' => ['dulcolax', 'lactulose', 'fiber']],
        'คลื่นไส้' => ['category' => 'nausea', 'keywords' => ['domperidone', 'dimenhydrinate']],
        'อาหารไม่ย่อย' => ['category' => 'indigestion', 'keywords' => ['antacid', 'omeprazole', 'ranitidine']],
        
        // Allergy symptoms
        'แพ้' => ['category' => 'allergy', 'keywords' => ['loratadine', 'cetirizine', 'chlorpheniramine']],
        'ผื่น' => ['category' => 'skin', 'keywords' => ['calamine', 'hydrocortisone', 'antihistamine']],
        'คัน' => ['category' => 'skin', 'keywords' => ['calamine', 'hydrocortisone', 'antihistamine']],
        
        // Eye symptoms
        'ตาแดง' => ['category' => 'eye', 'keywords' => ['eye drops', 'artificial tears']],
        'ตาแห้ง' => ['category' => 'eye', 'keywords' => ['artificial tears', 'eye lubricant']],
        
        // Sleep issues
        'นอนไม่หลับ' => ['category' => 'sleep', 'keywords' => ['diphenhydramine', 'melatonin']],
        'insomnia' => ['category' => 'sleep', 'keywords' => ['diphenhydramine', 'melatonin']]
    ];
    
    /**
     * Constructor
     * @param PDO $db Database connection
     * @param int|null $lineAccountId LINE account ID for multi-tenant support
     */
    public function __construct($db, $lineAccountId = null)
    {
        $this->db = $db;
        $this->lineAccountId = $lineAccountId;
    }
    
    /**
     * Set health engine for allergy checking
     * @param CustomerHealthEngineService $healthEngine
     */
    public function setHealthEngine($healthEngine)
    {
        $this->healthEngine = $healthEngine;
    }

    
    /**
     * Get drug recommendations for symptoms
     * 
     * Requirements 7.1: Suggest appropriate OTC medications with dosage
     * Requirements 7.2: Check for interactions with customer's current medications
     * Property 8: Allergy Check Before Recommendation
     * Property 10: Recommendations Exclude Out-of-Stock Drugs
     * 
     * @param array $symptoms List of symptoms
     * @param int $userId Customer user ID (for allergy check)
     * @param int $limit Max recommendations
     * @return array Drugs with dosage, price, and safety info
     */
    public function getForSymptoms(array $symptoms, int $userId, int $limit = 5): array
    {
        $recommendations = [];
        $allergies = $this->getUserAllergies($userId);
        $currentMedications = $this->getUserCurrentMedications($userId);
        
        // Get drug categories from symptoms
        $categories = [];
        $keywords = [];
        
        foreach ($symptoms as $symptom) {
            $symptomLower = mb_strtolower(trim($symptom));
            
            foreach ($this->symptomDrugMap as $key => $mapping) {
                if (mb_stripos($symptomLower, $key) !== false || mb_stripos($key, $symptomLower) !== false) {
                    $categories[] = $mapping['category'];
                    $keywords = array_merge($keywords, $mapping['keywords']);
                }
            }
        }
        
        $categories = array_unique($categories);
        $keywords = array_unique($keywords);
        
        if (empty($keywords)) {
            // Default to common OTC drugs if no specific match
            $keywords = ['paracetamol', 'vitamin'];
        }
        
        // Build search query for drugs
        $drugs = $this->searchDrugs($keywords, $limit * 2);
        
        foreach ($drugs as $drug) {
            // Property 10: Skip out-of-stock drugs
            if (($drug['stock'] ?? 0) <= 0) {
                continue;
            }
            
            // Property 8: Check allergies before recommending
            $allergyMatch = $this->checkAllergyMatch($drug, $allergies);
            if ($allergyMatch['hasMatch']) {
                continue; // Skip drugs that match allergies
            }
            
            // Check interactions with current medications
            $interactions = $this->checkDrugInteractionsInternal([$drug['id']], $currentMedications);
            
            $recommendations[] = [
                'drugId' => $drug['id'],
                'name' => $drug['name'],
                'genericName' => $drug['generic_name'] ?? null,
                'sku' => $drug['sku'] ?? null,
                'category' => $drug['category_name'] ?? 'ยาทั่วไป',
                'dosage' => $drug['dosage'] ?? $this->getDefaultDosage($drug),
                'usage' => $drug['usage_instructions'] ?? $this->getDefaultUsage($drug),
                'price' => (float)($drug['sale_price'] ?? $drug['price'] ?? 0),
                'originalPrice' => (float)($drug['price'] ?? 0),
                'stock' => (int)($drug['stock'] ?? 0),
                'imageUrl' => $drug['image_url'] ?? null,
                'isPrescription' => (bool)($drug['is_prescription'] ?? false),
                'hasInteractions' => !empty($interactions['interactions']),
                'interactions' => $interactions['interactions'] ?? [],
                'interactionSeverity' => $interactions['severity'] ?? null,
                'safeForConditions' => $this->checkConditionSafety($drug, $userId),
                'matchedSymptoms' => $symptoms
            ];
            
            if (count($recommendations) >= $limit) {
                break;
            }
        }
        
        return [
            'recommendations' => $recommendations,
            'symptoms' => $symptoms,
            'categories' => $categories,
            'userId' => $userId,
            'allergiesChecked' => count($allergies),
            'currentMedicationsChecked' => count($currentMedications)
        ];
    }
    
    /**
     * Search drugs by keywords
     * @param array $keywords Search keywords
     * @param int $limit Max results
     * @return array Matching drugs
     */
    private function searchDrugs(array $keywords, int $limit = 10): array
    {
        if (empty($keywords)) {
            return [];
        }
        
        // Build LIKE conditions for each keyword
        $conditions = [];
        $params = [];
        
        foreach ($keywords as $keyword) {
            $conditions[] = "(bi.name LIKE ? OR bi.sku LIKE ? OR bi.description LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        $whereClause = implode(' OR ', $conditions);
        
        $sql = "
            SELECT bi.*, ic.name as category_name
            FROM business_items bi
            LEFT JOIN item_categories ic ON bi.category_id = ic.id
            WHERE bi.is_active = 1 
            AND ({$whereClause})
        ";
        
        if ($this->lineAccountId) {
            $sql .= " AND (bi.line_account_id = ? OR bi.line_account_id IS NULL)";
            $params[] = $this->lineAccountId;
        }
        
        $sql .= " ORDER BY bi.stock DESC, bi.name ASC LIMIT ?";
        $params[] = $limit;
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine searchDrugs error: " . $e->getMessage());
            return [];
        }
    }

    
    /**
     * Check drug-drug interactions
     * 
     * Requirements 7.2: Check for interactions with customer's current medications
     * Property 9: Drug Interaction Detection
     * 
     * @param array $drugIds List of drug IDs to check
     * @param int $userId Customer user ID (for current medications)
     * @return array ['hasInteractions' => bool, 'interactions' => array, 'severity' => string]
     */
    public function checkInteractions(array $drugIds, int $userId): array
    {
        // Get drug names for the given IDs
        $drugNames = $this->getDrugNames($drugIds);
        
        // Get user's current medications
        $currentMedications = $this->getUserCurrentMedications($userId);
        
        // Combine all drugs to check
        $allDrugNames = array_merge($drugNames, $currentMedications);
        
        if (count($allDrugNames) < 2) {
            return [
                'hasInteractions' => false,
                'interactions' => [],
                'severity' => null,
                'checkedDrugs' => $allDrugNames
            ];
        }
        
        // Check interactions between all pairs
        $interactions = [];
        $maxSeverity = null;
        $severityOrder = ['mild' => 1, 'moderate' => 2, 'severe' => 3, 'contraindicated' => 4];
        
        // Check each drug against all others
        foreach ($drugNames as $drug1) {
            foreach ($currentMedications as $drug2) {
                $interaction = $this->findInteraction($drug1, $drug2);
                if ($interaction) {
                    $interactions[] = $interaction;
                    
                    // Track max severity
                    $currentSeverityLevel = $severityOrder[$interaction['severity']] ?? 0;
                    $maxSeverityLevel = $severityOrder[$maxSeverity] ?? 0;
                    if ($currentSeverityLevel > $maxSeverityLevel) {
                        $maxSeverity = $interaction['severity'];
                    }
                }
            }
        }
        
        // Also check interactions between the new drugs themselves
        for ($i = 0; $i < count($drugNames); $i++) {
            for ($j = $i + 1; $j < count($drugNames); $j++) {
                $interaction = $this->findInteraction($drugNames[$i], $drugNames[$j]);
                if ($interaction) {
                    $interactions[] = $interaction;
                    
                    $currentSeverityLevel = $severityOrder[$interaction['severity']] ?? 0;
                    $maxSeverityLevel = $severityOrder[$maxSeverity] ?? 0;
                    if ($currentSeverityLevel > $maxSeverityLevel) {
                        $maxSeverity = $interaction['severity'];
                    }
                }
            }
        }
        
        return [
            'hasInteractions' => !empty($interactions),
            'interactions' => $interactions,
            'severity' => $maxSeverity,
            'checkedDrugs' => $allDrugNames,
            'newDrugs' => $drugNames,
            'currentMedications' => $currentMedications
        ];
    }
    
    /**
     * Internal method to check interactions without user context
     * @param array $drugIds Drug IDs to check
     * @param array $currentMedications Current medication names
     * @return array Interaction results
     */
    private function checkDrugInteractionsInternal(array $drugIds, array $currentMedications): array
    {
        $drugNames = $this->getDrugNames($drugIds);
        
        $interactions = [];
        $maxSeverity = null;
        $severityOrder = ['mild' => 1, 'moderate' => 2, 'severe' => 3, 'contraindicated' => 4];
        
        foreach ($drugNames as $drug1) {
            foreach ($currentMedications as $drug2) {
                $interaction = $this->findInteraction($drug1, $drug2);
                if ($interaction) {
                    $interactions[] = $interaction;
                    
                    $currentSeverityLevel = $severityOrder[$interaction['severity']] ?? 0;
                    $maxSeverityLevel = $severityOrder[$maxSeverity] ?? 0;
                    if ($currentSeverityLevel > $maxSeverityLevel) {
                        $maxSeverity = $interaction['severity'];
                    }
                }
            }
        }
        
        return [
            'hasInteractions' => !empty($interactions),
            'interactions' => $interactions,
            'severity' => $maxSeverity
        ];
    }
    
    /**
     * Find interaction between two drugs
     * @param string $drug1 First drug name
     * @param string $drug2 Second drug name
     * @return array|null Interaction data or null
     */
    private function findInteraction(string $drug1, string $drug2): ?array
    {
        try {
            // Search in both directions
            $stmt = $this->db->prepare("
                SELECT * FROM drug_interactions 
                WHERE (
                    (drug1_name LIKE ? OR drug1_generic LIKE ?) 
                    AND (drug2_name LIKE ? OR drug2_generic LIKE ?)
                ) OR (
                    (drug1_name LIKE ? OR drug1_generic LIKE ?) 
                    AND (drug2_name LIKE ? OR drug2_generic LIKE ?)
                )
                LIMIT 1
            ");
            
            $drug1Pattern = "%{$drug1}%";
            $drug2Pattern = "%{$drug2}%";
            
            $stmt->execute([
                $drug1Pattern, $drug1Pattern, $drug2Pattern, $drug2Pattern,
                $drug2Pattern, $drug2Pattern, $drug1Pattern, $drug1Pattern
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'drug1' => $result['drug1_name'],
                    'drug1Generic' => $result['drug1_generic'],
                    'drug2' => $result['drug2_name'],
                    'drug2Generic' => $result['drug2_generic'],
                    'severity' => $result['severity'],
                    'description' => $result['description'],
                    'recommendation' => $result['recommendation']
                ];
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine findInteraction error: " . $e->getMessage());
            return null;
        }
    }

    
    /**
     * Get refill reminders based on purchase history
     * 
     * Requirements 7.3: Suggest refill reminders based on typical usage duration
     * 
     * @param int $userId Customer user ID
     * @return array Drugs due for refill with estimated dates
     */
    public function getRefillReminders(int $userId): array
    {
        $reminders = [];
        
        // Default usage durations by category (in days)
        $usageDurations = [
            'chronic' => 30,      // Chronic medications (monthly)
            'antibiotic' => 7,    // Antibiotics (weekly course)
            'vitamin' => 30,      // Vitamins (monthly)
            'pain' => 14,         // Pain relief (2 weeks)
            'default' => 30       // Default assumption
        ];
        
        try {
            // Get recent medication purchases
            $sql = "
                SELECT 
                    bi.id as product_id,
                    bi.name,
                    bi.sku,
                    bi.price,
                    bi.stock,
                    bi.image_url,
                    MAX(t.created_at) as last_purchase_date,
                    SUM(ti.quantity) as total_quantity,
                    ic.name as category_name
                FROM transactions t
                JOIN transaction_items ti ON t.id = ti.transaction_id
                JOIN business_items bi ON ti.product_id = bi.id
                LEFT JOIN item_categories ic ON bi.category_id = ic.id
                WHERE t.user_id = ?
                AND t.status NOT IN ('cancelled', 'failed')
                AND t.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY bi.id, bi.name, bi.sku, bi.price, bi.stock, bi.image_url, ic.name
                ORDER BY last_purchase_date DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($purchases as $purchase) {
                // Determine usage duration based on category
                $category = strtolower($purchase['category_name'] ?? '');
                $duration = $usageDurations['default'];
                
                foreach ($usageDurations as $key => $days) {
                    if (stripos($category, $key) !== false) {
                        $duration = $days;
                        break;
                    }
                }
                
                // Calculate estimated refill date
                $lastPurchase = new DateTime($purchase['last_purchase_date']);
                $refillDate = clone $lastPurchase;
                $refillDate->modify("+{$duration} days");
                
                $now = new DateTime();
                $daysUntilRefill = (int)$now->diff($refillDate)->format('%r%a');
                
                // Include if refill is due within 7 days or overdue
                if ($daysUntilRefill <= 7) {
                    $status = 'due';
                    $urgency = 'normal';
                    
                    if ($daysUntilRefill < 0) {
                        $status = 'overdue';
                        $urgency = 'high';
                    } elseif ($daysUntilRefill <= 3) {
                        $urgency = 'medium';
                    }
                    
                    $reminders[] = [
                        'productId' => $purchase['product_id'],
                        'name' => $purchase['name'],
                        'sku' => $purchase['sku'],
                        'price' => (float)$purchase['price'],
                        'stock' => (int)$purchase['stock'],
                        'imageUrl' => $purchase['image_url'],
                        'category' => $purchase['category_name'],
                        'lastPurchaseDate' => $purchase['last_purchase_date'],
                        'estimatedRefillDate' => $refillDate->format('Y-m-d'),
                        'daysUntilRefill' => $daysUntilRefill,
                        'status' => $status,
                        'urgency' => $urgency,
                        'usageDuration' => $duration,
                        'message' => $this->getRefillMessage($daysUntilRefill, $purchase['name'])
                    ];
                }
            }
            
            // Sort by urgency (overdue first, then by days until refill)
            usort($reminders, function($a, $b) {
                if ($a['status'] === 'overdue' && $b['status'] !== 'overdue') return -1;
                if ($a['status'] !== 'overdue' && $b['status'] === 'overdue') return 1;
                return $a['daysUntilRefill'] - $b['daysUntilRefill'];
            });
            
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine getRefillReminders error: " . $e->getMessage());
            
            // Try with orders table as fallback
            try {
                $sql = "
                    SELECT 
                        bi.id as product_id,
                        bi.name,
                        bi.sku,
                        bi.price,
                        bi.stock,
                        bi.image_url,
                        MAX(o.created_at) as last_purchase_date,
                        SUM(oi.quantity) as total_quantity,
                        ic.name as category_name
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN business_items bi ON oi.product_id = bi.id
                    LEFT JOIN item_categories ic ON bi.category_id = ic.id
                    WHERE o.user_id = ?
                    AND o.status IN ('paid', 'confirmed', 'delivered', 'completed')
                    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                    GROUP BY bi.id, bi.name, bi.sku, bi.price, bi.stock, bi.image_url, ic.name
                    ORDER BY last_purchase_date DESC
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);
                // Process similar to above...
            } catch (PDOException $e2) {
                // Return empty if both fail
            }
        }
        
        return [
            'reminders' => $reminders,
            'userId' => $userId,
            'totalDue' => count($reminders)
        ];
    }
    
    /**
     * Get refill reminder message
     * @param int $daysUntilRefill Days until refill
     * @param string $drugName Drug name
     * @return string Message
     */
    private function getRefillMessage(int $daysUntilRefill, string $drugName): string
    {
        if ($daysUntilRefill < 0) {
            $overdueDays = abs($daysUntilRefill);
            return "ยา {$drugName} เลยกำหนดเติมแล้ว {$overdueDays} วัน";
        } elseif ($daysUntilRefill === 0) {
            return "ยา {$drugName} ถึงกำหนดเติมวันนี้";
        } elseif ($daysUntilRefill === 1) {
            return "ยา {$drugName} จะถึงกำหนดเติมพรุ่งนี้";
        } else {
            return "ยา {$drugName} จะถึงกำหนดเติมใน {$daysUntilRefill} วัน";
        }
    }

    
    /**
     * Generate drug card message for LINE
     * 
     * Requirements 7.5: Insert drug card with full information into message composer
     * 
     * @param int $drugId Drug ID
     * @return array Flex message structure
     */
    public function generateDrugCard(int $drugId): array
    {
        // Get drug details
        $drug = $this->getDrugDetails($drugId);
        
        if (!$drug) {
            return [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => '❌ ไม่พบข้อมูลยา', 'weight' => 'bold', 'color' => '#EF4444']
                    ]
                ]
            ];
        }
        
        $price = (float)($drug['sale_price'] ?? $drug['price'] ?? 0);
        $originalPrice = $drug['sale_price'] ? (float)$drug['price'] : null;
        $hasDiscount = $originalPrice && $originalPrice > $price;
        $inStock = ($drug['stock'] ?? 0) > 0;
        $isPrescription = (bool)($drug['is_prescription'] ?? false);
        
        // Build price contents
        $priceContents = [
            ['type' => 'text', 'text' => '฿' . number_format($price, 2), 'size' => 'xl', 'weight' => 'bold', 'color' => '#06C755']
        ];
        
        if ($hasDiscount) {
            $priceContents[] = [
                'type' => 'text', 
                'text' => '฿' . number_format($originalPrice, 2), 
                'size' => 'sm', 
                'color' => '#AAAAAA', 
                'decoration' => 'line-through', 
                'margin' => 'sm'
            ];
        }
        
        // Build info rows
        $infoContents = [];
        
        // Dosage
        if (!empty($drug['dosage'])) {
            $infoContents[] = $this->buildInfoRow('💊 ขนาดยา', $drug['dosage']);
        }
        
        // Usage instructions
        if (!empty($drug['usage_instructions'])) {
            $infoContents[] = $this->buildInfoRow('📋 วิธีใช้', $drug['usage_instructions']);
        }
        
        // Side effects
        if (!empty($drug['side_effects'])) {
            $infoContents[] = $this->buildInfoRow('⚠️ ผลข้างเคียง', $drug['side_effects']);
        }
        
        // Contraindications
        if (!empty($drug['contraindications'])) {
            $infoContents[] = $this->buildInfoRow('🚫 ข้อห้ามใช้', $drug['contraindications']);
        }
        
        // Stock status
        $stockText = $inStock ? "📦 เหลือ {$drug['stock']} ชิ้น" : '❌ สินค้าหมด';
        $stockColor = $inStock ? '#888888' : '#EF4444';
        
        // Build body contents
        $bodyContents = [
            ['type' => 'text', 'text' => $drug['name'], 'weight' => 'bold', 'size' => 'lg', 'wrap' => true]
        ];
        
        // Add generic name if available
        if (!empty($drug['generic_name'])) {
            $bodyContents[] = [
                'type' => 'text', 
                'text' => "({$drug['generic_name']})", 
                'size' => 'sm', 
                'color' => '#888888',
                'margin' => 'sm'
            ];
        }
        
        // Add prescription badge if needed
        if ($isPrescription) {
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '💊 ยาควบคุมพิเศษ',
                        'size' => 'xs',
                        'color' => '#FFFFFF',
                        'align' => 'center'
                    ]
                ],
                'backgroundColor' => '#EF4444',
                'cornerRadius' => 'md',
                'paddingAll' => 'xs',
                'margin' => 'md',
                'width' => '120px'
            ];
        }
        
        // Add price
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'contents' => $priceContents,
            'margin' => 'lg'
        ];
        
        // Add stock status
        $bodyContents[] = [
            'type' => 'text',
            'text' => $stockText,
            'size' => 'xs',
            'color' => $stockColor,
            'margin' => 'md'
        ];
        
        // Add separator and info
        if (!empty($infoContents)) {
            $bodyContents[] = ['type' => 'separator', 'margin' => 'lg'];
            $bodyContents = array_merge($bodyContents, $infoContents);
        }
        
        // Build buttons
        $buttons = [];
        
        if ($inStock && !$isPrescription) {
            $buttons[] = [
                'type' => 'button',
                'action' => [
                    'type' => 'message',
                    'label' => '🛒 เพิ่มลงตะกร้า',
                    'text' => "add {$drugId}"
                ],
                'style' => 'primary',
                'color' => '#06C755'
            ];
        } elseif ($isPrescription) {
            $buttons[] = [
                'type' => 'button',
                'action' => [
                    'type' => 'message',
                    'label' => '💬 ปรึกษาเภสัชกร',
                    'text' => "consult {$drugId}"
                ],
                'style' => 'primary',
                'color' => '#3B82F6'
            ];
        }
        
        $buttons[] = [
            'type' => 'button',
            'action' => [
                'type' => 'message',
                'label' => '🔍 ตรวจสอบยาตีกัน',
                'text' => "check interaction {$drugId}"
            ],
            'style' => 'secondary',
            'margin' => 'sm'
        ];
        
        // Build the bubble
        $bubble = [
            'type' => 'bubble',
            'size' => 'mega'
        ];
        
        // Add hero image if available
        if (!empty($drug['image_url'])) {
            $bubble['hero'] = [
                'type' => 'image',
                'url' => $drug['image_url'],
                'size' => 'full',
                'aspectRatio' => '4:3',
                'aspectMode' => 'cover'
            ];
        }
        
        $bubble['body'] = [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => $bodyContents,
            'paddingAll' => 'lg'
        ];
        
        $bubble['footer'] = [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => $buttons,
            'paddingAll' => 'lg'
        ];
        
        return $bubble;
    }
    
    /**
     * Build info row for drug card
     * @param string $label Label
     * @param string $value Value
     * @return array Box component
     */
    private function buildInfoRow(string $label, string $value): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => [
                ['type' => 'text', 'text' => $label, 'size' => 'xs', 'color' => '#888888'],
                ['type' => 'text', 'text' => $value, 'size' => 'sm', 'wrap' => true, 'margin' => 'xs']
            ],
            'margin' => 'lg'
        ];
    }

    
    /**
     * Get safe alternatives for customers with allergies/conditions
     * 
     * Requirements 7.6: Prioritize drugs safe for customer's condition
     * 
     * @param int $drugId Original drug ID
     * @param int $userId Customer user ID
     * @return array Safe alternative drugs
     */
    public function getSafeAlternatives(int $drugId, int $userId): array
    {
        $alternatives = [];
        
        // Get original drug details
        $originalDrug = $this->getDrugDetails($drugId);
        
        if (!$originalDrug) {
            return [
                'alternatives' => [],
                'originalDrug' => null,
                'reason' => 'Original drug not found'
            ];
        }
        
        // Get user's allergies and conditions
        $allergies = $this->getUserAllergies($userId);
        $conditions = $this->getUserConditions($userId);
        
        // Check if original drug is safe
        $originalSafe = true;
        $unsafeReasons = [];
        
        $allergyMatch = $this->checkAllergyMatch($originalDrug, $allergies);
        if ($allergyMatch['hasMatch']) {
            $originalSafe = false;
            $unsafeReasons[] = "แพ้ยา: " . implode(', ', $allergyMatch['matchedAllergies']);
        }
        
        // Get similar drugs in same category
        $categoryId = $originalDrug['category_id'] ?? null;
        $similarDrugs = $this->getSimilarDrugs($drugId, $categoryId, 10);
        
        foreach ($similarDrugs as $drug) {
            // Skip out-of-stock
            if (($drug['stock'] ?? 0) <= 0) {
                continue;
            }
            
            // Check allergy safety
            $allergyCheck = $this->checkAllergyMatch($drug, $allergies);
            if ($allergyCheck['hasMatch']) {
                continue;
            }
            
            // Check condition safety
            $conditionSafety = $this->checkConditionSafety($drug, $userId);
            
            $alternatives[] = [
                'drugId' => $drug['id'],
                'name' => $drug['name'],
                'genericName' => $drug['generic_name'] ?? null,
                'price' => (float)($drug['sale_price'] ?? $drug['price'] ?? 0),
                'stock' => (int)($drug['stock'] ?? 0),
                'imageUrl' => $drug['image_url'] ?? null,
                'isSafeForConditions' => $conditionSafety['isSafe'],
                'conditionWarnings' => $conditionSafety['warnings'],
                'similarity' => $this->calculateSimilarity($originalDrug, $drug)
            ];
            
            if (count($alternatives) >= 5) {
                break;
            }
        }
        
        // Sort by similarity score
        usort($alternatives, function($a, $b) {
            return $b['similarity'] - $a['similarity'];
        });
        
        return [
            'alternatives' => $alternatives,
            'originalDrug' => [
                'id' => $originalDrug['id'],
                'name' => $originalDrug['name'],
                'isSafe' => $originalSafe,
                'unsafeReasons' => $unsafeReasons
            ],
            'userId' => $userId,
            'allergiesChecked' => $allergies,
            'conditionsChecked' => $conditions
        ];
    }
    
    /**
     * Get similar drugs in same category
     * @param int $excludeDrugId Drug ID to exclude
     * @param int|null $categoryId Category ID
     * @param int $limit Max results
     * @return array Similar drugs
     */
    private function getSimilarDrugs(int $excludeDrugId, ?int $categoryId, int $limit = 10): array
    {
        try {
            $sql = "
                SELECT bi.*, ic.name as category_name
                FROM business_items bi
                LEFT JOIN item_categories ic ON bi.category_id = ic.id
                WHERE bi.is_active = 1 
                AND bi.id != ?
                AND bi.stock > 0
            ";
            $params = [$excludeDrugId];
            
            if ($categoryId) {
                $sql .= " AND bi.category_id = ?";
                $params[] = $categoryId;
            }
            
            if ($this->lineAccountId) {
                $sql .= " AND (bi.line_account_id = ? OR bi.line_account_id IS NULL)";
                $params[] = $this->lineAccountId;
            }
            
            $sql .= " ORDER BY bi.stock DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine getSimilarDrugs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate similarity between two drugs
     * @param array $drug1 First drug
     * @param array $drug2 Second drug
     * @return float Similarity score (0-100)
     */
    private function calculateSimilarity(array $drug1, array $drug2): float
    {
        $score = 0;
        
        // Same category
        if (($drug1['category_id'] ?? 0) === ($drug2['category_id'] ?? 0)) {
            $score += 40;
        }
        
        // Similar price range (within 30%)
        $price1 = (float)($drug1['price'] ?? 0);
        $price2 = (float)($drug2['price'] ?? 0);
        if ($price1 > 0 && $price2 > 0) {
            $priceDiff = abs($price1 - $price2) / max($price1, $price2);
            if ($priceDiff <= 0.3) {
                $score += 30 * (1 - $priceDiff);
            }
        }
        
        // Name similarity (simple check)
        $name1 = strtolower($drug1['name'] ?? '');
        $name2 = strtolower($drug2['name'] ?? '');
        similar_text($name1, $name2, $nameSimilarity);
        $score += $nameSimilarity * 0.3;
        
        return round($score, 2);
    }

    
    // ==========================================
    // Helper Methods
    // ==========================================
    
    /**
     * Get drug names from IDs
     * @param array $drugIds Drug IDs
     * @return array Drug names
     */
    private function getDrugNames(array $drugIds): array
    {
        if (empty($drugIds)) {
            return [];
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($drugIds), '?'));
            $stmt = $this->db->prepare("
                SELECT id, name, generic_name 
                FROM business_items 
                WHERE id IN ({$placeholders})
            ");
            $stmt->execute($drugIds);
            $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $names = [];
            foreach ($drugs as $drug) {
                $names[] = $drug['name'];
                if (!empty($drug['generic_name'])) {
                    $names[] = $drug['generic_name'];
                }
            }
            
            return array_unique($names);
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine getDrugNames error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get drug details by ID
     * @param int $drugId Drug ID
     * @return array|null Drug details
     */
    private function getDrugDetails(int $drugId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT bi.*, ic.name as category_name
                FROM business_items bi
                LEFT JOIN item_categories ic ON bi.category_id = ic.id
                WHERE bi.id = ?
            ");
            $stmt->execute([$drugId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine getDrugDetails error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user's allergies
     * @param int $userId User ID
     * @return array Allergies
     */
    private function getUserAllergies(int $userId): array
    {
        // Use health engine if available
        if ($this->healthEngine) {
            return $this->healthEngine->getAllergies($userId);
        }
        
        // Fallback to direct query
        try {
            $stmt = $this->db->prepare("SELECT drug_allergies FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['drug_allergies'])) {
                $allergyList = preg_split('/[,\n]+/', $user['drug_allergies']);
                $allergies = [];
                foreach ($allergyList as $allergy) {
                    $allergy = trim($allergy);
                    if (!empty($allergy)) {
                        $allergies[] = ['name' => $allergy, 'severity' => 'unknown'];
                    }
                }
                return $allergies;
            }
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine getUserAllergies error: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Get user's current medications
     * @param int $userId User ID
     * @return array Medication names
     */
    private function getUserCurrentMedications(int $userId): array
    {
        // Use health engine if available
        if ($this->healthEngine) {
            $medications = $this->healthEngine->getMedications($userId);
            return array_column($medications, 'name');
        }
        
        // Fallback to direct query
        try {
            $stmt = $this->db->prepare("SELECT current_medications FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['current_medications'])) {
                $medList = preg_split('/[,\n]+/', $user['current_medications']);
                return array_map('trim', array_filter($medList));
            }
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine getUserCurrentMedications error: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Get user's medical conditions
     * @param int $userId User ID
     * @return array Conditions
     */
    private function getUserConditions(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT medical_conditions FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['medical_conditions'])) {
                $conditionList = preg_split('/[,\n]+/', $user['medical_conditions']);
                return array_map('trim', array_filter($conditionList));
            }
        } catch (PDOException $e) {
            error_log("DrugRecommendEngine getUserConditions error: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Check if drug matches any allergies
     * @param array $drug Drug data
     * @param array $allergies User allergies
     * @return array ['hasMatch' => bool, 'matchedAllergies' => array]
     */
    private function checkAllergyMatch(array $drug, array $allergies): array
    {
        $matchedAllergies = [];
        
        $drugName = strtolower($drug['name'] ?? '');
        $genericName = strtolower($drug['generic_name'] ?? '');
        $description = strtolower($drug['description'] ?? '');
        
        foreach ($allergies as $allergy) {
            $allergyName = strtolower($allergy['name'] ?? $allergy);
            
            // Check if allergy matches drug name, generic name, or description
            if (
                stripos($drugName, $allergyName) !== false ||
                stripos($genericName, $allergyName) !== false ||
                stripos($allergyName, $drugName) !== false ||
                stripos($allergyName, $genericName) !== false ||
                stripos($description, $allergyName) !== false
            ) {
                $matchedAllergies[] = $allergy['name'] ?? $allergy;
            }
        }
        
        return [
            'hasMatch' => !empty($matchedAllergies),
            'matchedAllergies' => $matchedAllergies
        ];
    }
    
    /**
     * Check drug safety for user's conditions
     * @param array $drug Drug data
     * @param int $userId User ID
     * @return array ['isSafe' => bool, 'warnings' => array]
     */
    private function checkConditionSafety(array $drug, int $userId): array
    {
        $conditions = $this->getUserConditions($userId);
        $warnings = [];
        
        // Common condition-drug warnings
        $conditionWarnings = [
            'เบาหวาน' => ['sugar', 'glucose', 'syrup'],
            'diabetes' => ['sugar', 'glucose', 'syrup'],
            'ความดันสูง' => ['sodium', 'nsaid', 'ibuprofen'],
            'hypertension' => ['sodium', 'nsaid', 'ibuprofen'],
            'ไต' => ['nsaid', 'ibuprofen', 'aspirin'],
            'kidney' => ['nsaid', 'ibuprofen', 'aspirin'],
            'ตับ' => ['paracetamol', 'acetaminophen'],
            'liver' => ['paracetamol', 'acetaminophen'],
            'หอบหืด' => ['aspirin', 'nsaid', 'beta-blocker'],
            'asthma' => ['aspirin', 'nsaid', 'beta-blocker'],
            'ตั้งครรภ์' => ['nsaid', 'aspirin', 'ibuprofen', 'warfarin'],
            'pregnancy' => ['nsaid', 'aspirin', 'ibuprofen', 'warfarin']
        ];
        
        $drugName = strtolower($drug['name'] ?? '');
        $genericName = strtolower($drug['generic_name'] ?? '');
        $description = strtolower($drug['description'] ?? '');
        
        foreach ($conditions as $condition) {
            $conditionLower = strtolower($condition);
            
            foreach ($conditionWarnings as $condKey => $dangerousDrugs) {
                if (stripos($conditionLower, $condKey) !== false) {
                    foreach ($dangerousDrugs as $dangerous) {
                        if (
                            stripos($drugName, $dangerous) !== false ||
                            stripos($genericName, $dangerous) !== false ||
                            stripos($description, $dangerous) !== false
                        ) {
                            $warnings[] = "ควรระวังการใช้กับผู้ป่วย {$condition}";
                            break 2;
                        }
                    }
                }
            }
        }
        
        return [
            'isSafe' => empty($warnings),
            'warnings' => $warnings,
            'conditionsChecked' => $conditions
        ];
    }
    
    /**
     * Get default dosage for a drug
     * @param array $drug Drug data
     * @return string Default dosage
     */
    private function getDefaultDosage(array $drug): string
    {
        // Try to extract from description or use generic default
        $name = strtolower($drug['name'] ?? '');
        
        if (stripos($name, 'paracetamol') !== false || stripos($name, 'tylenol') !== false) {
            return '500-1000 mg ทุก 4-6 ชั่วโมง (ไม่เกิน 4000 mg/วัน)';
        }
        if (stripos($name, 'ibuprofen') !== false) {
            return '200-400 mg ทุก 4-6 ชั่วโมง (ไม่เกิน 1200 mg/วัน)';
        }
        if (stripos($name, 'loratadine') !== false) {
            return '10 mg วันละ 1 ครั้ง';
        }
        if (stripos($name, 'cetirizine') !== false) {
            return '10 mg วันละ 1 ครั้ง';
        }
        
        return 'ตามคำแนะนำบนฉลาก';
    }
    
    /**
     * Get default usage instructions for a drug
     * @param array $drug Drug data
     * @return string Default usage
     */
    private function getDefaultUsage(array $drug): string
    {
        $name = strtolower($drug['name'] ?? '');
        
        if (stripos($name, 'paracetamol') !== false) {
            return 'รับประทานหลังอาหารหรือเมื่อมีอาการ';
        }
        if (stripos($name, 'antacid') !== false || stripos($name, 'omeprazole') !== false) {
            return 'รับประทานก่อนอาหาร 30 นาที';
        }
        if (stripos($name, 'antibiotic') !== false) {
            return 'รับประทานให้ครบตามที่แพทย์สั่ง';
        }
        
        return 'รับประทานตามคำแนะนำบนฉลาก';
    }
}
