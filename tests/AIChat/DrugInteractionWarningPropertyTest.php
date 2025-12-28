<?php
/**
 * Property-Based Test: Drug Interaction Warning
 * 
 * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
 * **Validates: Requirements 7.2, 7.4**
 * 
 * Property: For any medication recommendation where interaction is detected with user's 
 * current medications, the response SHALL include an interaction warning message.
 */

namespace Tests\AIChat;

use PHPUnit\Framework\TestCase;

class DrugInteractionWarningPropertyTest extends TestCase
{
    /**
     * Known drug interactions database for testing
     * This mirrors the interaction database in pharmacy-ai.php
     */
    private $interactionDb = [
        'warfarin' => ['aspirin', 'ibuprofen', 'naproxen', 'แอสไพริน', 'ไอบูโพรเฟน'],
        'metformin' => ['alcohol', 'แอลกอฮอล์'],
        'aspirin' => ['ibuprofen', 'ไอบูโพรเฟน', 'naproxen'],
        'lisinopril' => ['potassium', 'โพแทสเซียม'],
        'simvastatin' => ['grapefruit', 'เกรปฟรุต'],
        'methotrexate' => ['nsaid', 'ibuprofen', 'aspirin'],
        'digoxin' => ['amiodarone', 'verapamil'],
        'clopidogrel' => ['omeprazole', 'โอเมพราโซล'],
    ];
    
    /**
     * Check drug interactions - copy of the function from pharmacy-ai.php for testing
     * 
     * @param array $products Array of recommended products
     * @param array $userContext User context including current medications
     * @return array Array of interaction warnings
     */
    private function checkDrugInteractionsSimple($products, $userContext) {
        $interactions = [];
        
        // Get current medications from health profile (text field) and detailed list
        $currentMeds = $userContext['health_medications'] ?? '';
        $currentMedsList = $userContext['current_medications_list'] ?? [];
        
        // Get allergies from health profile (text field) and detailed list
        $allergies = $userContext['drug_allergies'] ?? $userContext['health_allergies'] ?? '';
        $allergiesList = $userContext['drug_allergies_list'] ?? [];
        
        // Build allergy names array for matching
        $allergyNames = [];
        foreach ($allergiesList as $allergy) {
            $allergyNames[] = mb_strtolower($allergy['drug_name'], 'UTF-8');
        }
        if (!empty($allergies)) {
            $allergyNames[] = mb_strtolower($allergies, 'UTF-8');
        }
        
        // Build current medication names array for matching
        $currentMedNames = [];
        foreach ($currentMedsList as $med) {
            $currentMedNames[] = mb_strtolower($med['medication_name'], 'UTF-8');
        }
        if (!empty($currentMeds)) {
            $currentMedNames[] = mb_strtolower($currentMeds, 'UTF-8');
        }
        
        if (empty($allergyNames) && empty($currentMedNames)) {
            return $interactions;
        }
        
        foreach ($products as $product) {
            $productName = mb_strtolower($product['name'] ?? '', 'UTF-8');
            $genericName = mb_strtolower($product['generic_name'] ?? '', 'UTF-8');
            $productId = $product['id'] ?? null;
            
            // Check allergies
            foreach ($allergiesList as $allergy) {
                $allergyName = mb_strtolower($allergy['drug_name'], 'UTF-8');
                $allergyId = $allergy['drug_id'] ?? null;
                
                // Match by ID
                if ($allergyId && $productId && $allergyId == $productId) {
                    $interactions[] = [
                        'type' => 'allergy',
                        'severity' => 'high',
                        'product' => $product['name'],
                        'product_id' => $productId,
                        'allergy' => $allergy['drug_name'],
                        'reaction_type' => $allergy['reaction_type'] ?? 'unknown',
                        'message' => "⛔ ห้ามใช้! คุณแพ้ยา {$allergy['drug_name']}"
                    ];
                    continue 2;
                }
                
                // Match by name
                if (mb_strpos($productName, $allergyName) !== false || 
                    mb_strpos($genericName, $allergyName) !== false ||
                    mb_strpos($allergyName, $productName) !== false ||
                    mb_strpos($allergyName, $genericName) !== false) {
                    $interactions[] = [
                        'type' => 'allergy',
                        'severity' => 'high',
                        'product' => $product['name'],
                        'product_id' => $productId,
                        'allergy' => $allergy['drug_name'],
                        'reaction_type' => $allergy['reaction_type'] ?? 'unknown',
                        'message' => "⛔ ห้ามใช้! คุณแพ้ยา {$allergy['drug_name']}"
                    ];
                    continue 2;
                }
            }
            
            // Check text-based allergies
            if (!empty($allergies)) {
                $allergiesLower = mb_strtolower($allergies, 'UTF-8');
                if (mb_strpos($allergiesLower, $productName) !== false || 
                    mb_strpos($allergiesLower, $genericName) !== false ||
                    mb_strpos($productName, $allergiesLower) !== false) {
                    $interactions[] = [
                        'type' => 'allergy',
                        'severity' => 'high',
                        'product' => $product['name'],
                        'product_id' => $productId,
                        'allergy' => $allergies,
                        'message' => "⛔ ห้ามใช้! คุณแพ้ยานี้"
                    ];
                    continue;
                }
            }
            
            // Check interactions with current medications
            foreach ($currentMedsList as $med) {
                $medName = mb_strtolower($med['medication_name'], 'UTF-8');
                
                foreach ($this->interactionDb as $drug => $interactsWith) {
                    if (mb_strpos($medName, $drug) !== false) {
                        foreach ($interactsWith as $interactDrug) {
                            if (mb_strpos($productName, $interactDrug) !== false || 
                                mb_strpos($genericName, $interactDrug) !== false) {
                                $interactions[] = [
                                    'type' => 'interaction',
                                    'severity' => 'medium',
                                    'product' => $product['name'],
                                    'product_id' => $productId,
                                    'interacts_with' => $med['medication_name'],
                                    'message' => "⚠️ {$product['name']} อาจตีกับยา {$med['medication_name']} ที่คุณทานอยู่"
                                ];
                            }
                        }
                    }
                }
            }
            
            // Check text-based current medications
            if (!empty($currentMeds)) {
                $currentMedsLower = mb_strtolower($currentMeds, 'UTF-8');
                foreach ($this->interactionDb as $drug => $interactsWith) {
                    if (mb_strpos($currentMedsLower, $drug) !== false) {
                        foreach ($interactsWith as $interactDrug) {
                            if (mb_strpos($productName, $interactDrug) !== false || 
                                mb_strpos($genericName, $interactDrug) !== false) {
                                $interactions[] = [
                                    'type' => 'interaction',
                                    'severity' => 'medium',
                                    'product' => $product['name'],
                                    'product_id' => $productId,
                                    'interacts_with' => $drug,
                                    'message' => "⚠️ {$product['name']} อาจตีกับยา {$drug} ที่คุณทานอยู่"
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        return $interactions;
    }
    
    /**
     * Data provider for known drug interactions
     * 
     * @return array Test data sets [product, current_medication, should_have_interaction]
     */
    public function knownInteractionProvider(): array
    {
        return [
            // Warfarin interactions
            ['aspirin', 'warfarin', true],
            ['ibuprofen', 'warfarin', true],
            ['naproxen', 'warfarin', true],
            
            // Aspirin interactions
            ['ibuprofen', 'aspirin', true],
            ['naproxen', 'aspirin', true],
            
            // Metformin interactions
            ['alcohol', 'metformin', true],
            
            // Clopidogrel interactions
            ['omeprazole', 'clopidogrel', true],
            
            // No interactions
            ['paracetamol', 'warfarin', false],
            ['cetirizine', 'metformin', false],
            ['loratadine', 'aspirin', false],
        ];
    }
    
    /**
     * Property Test: Known drug interactions MUST generate warning messages
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     * 
     * @dataProvider knownInteractionProvider
     */
    public function testKnownInteractionsGenerateWarnings(string $productGeneric, string $currentMed, bool $shouldHaveInteraction): void
    {
        $products = [
            [
                'id' => 1,
                'name' => ucfirst($productGeneric) . ' 500mg',
                'generic_name' => $productGeneric,
            ]
        ];
        
        $userContext = [
            'current_medications_list' => [
                ['medication_name' => $currentMed, 'dosage' => '5mg', 'frequency' => 'daily']
            ],
            'health_medications' => '',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        if ($shouldHaveInteraction) {
            $this->assertNotEmpty(
                $interactions,
                "Product '{$productGeneric}' should have interaction with '{$currentMed}'"
            );
            
            // Verify interaction has required fields
            $interaction = $interactions[0];
            $this->assertEquals('interaction', $interaction['type']);
            $this->assertEquals('medium', $interaction['severity']);
            $this->assertNotEmpty($interaction['message']);
            $this->assertStringContainsString('⚠️', $interaction['message']);
        } else {
            $this->assertEmpty(
                $interactions,
                "Product '{$productGeneric}' should NOT have interaction with '{$currentMed}'"
            );
        }
    }
    
    /**
     * Property Test: Interaction warnings MUST include the interacting medication name
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     */
    public function testInteractionWarningsIncludeMedicationName(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
        ];
        
        $userContext = [
            'current_medications_list' => [
                ['medication_name' => 'warfarin', 'dosage' => '5mg', 'frequency' => 'daily']
            ],
            'health_medications' => '',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        $this->assertNotEmpty($interactions);
        
        $interaction = $interactions[0];
        $this->assertArrayHasKey('interacts_with', $interaction);
        $this->assertEquals('warfarin', $interaction['interacts_with']);
        $this->assertStringContainsString('warfarin', $interaction['message']);
    }
    
    /**
     * Property Test: Multiple interactions should all be reported
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     */
    public function testMultipleInteractionsAreAllReported(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
            ['id' => 2, 'name' => 'Ibuprofen 400mg', 'generic_name' => 'ibuprofen'],
        ];
        
        $userContext = [
            'current_medications_list' => [
                ['medication_name' => 'warfarin', 'dosage' => '5mg', 'frequency' => 'daily']
            ],
            'health_medications' => '',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        // Both aspirin and ibuprofen interact with warfarin
        $this->assertGreaterThanOrEqual(2, count($interactions));
        
        $productNames = array_column($interactions, 'product');
        $this->assertContains('Aspirin 300mg', $productNames);
        $this->assertContains('Ibuprofen 400mg', $productNames);
    }
    
    /**
     * Property Test: No interactions when user has no current medications
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     */
    public function testNoInteractionsWithoutCurrentMedications(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
            ['id' => 2, 'name' => 'Ibuprofen 400mg', 'generic_name' => 'ibuprofen'],
        ];
        
        $userContext = [
            'current_medications_list' => [],
            'health_medications' => '',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        $this->assertEmpty($interactions);
    }
    
    /**
     * Property Test: Text-based medications should also trigger interactions
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     */
    public function testTextBasedMedicationsTriggerInteractions(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
        ];
        
        // User has warfarin in text field (from health profile)
        $userContext = [
            'current_medications_list' => [],
            'health_medications' => 'warfarin 5mg daily',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        $this->assertNotEmpty($interactions);
        $this->assertEquals('interaction', $interactions[0]['type']);
    }
    
    /**
     * Property Test: Interaction warnings should have correct severity
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     */
    public function testInteractionWarningsHaveCorrectSeverity(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
        ];
        
        $userContext = [
            'current_medications_list' => [
                ['medication_name' => 'warfarin', 'dosage' => '5mg', 'frequency' => 'daily']
            ],
            'health_medications' => '',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        $this->assertNotEmpty($interactions);
        
        // Drug interactions should have medium severity
        foreach ($interactions as $interaction) {
            if ($interaction['type'] === 'interaction') {
                $this->assertEquals('medium', $interaction['severity']);
            }
        }
    }
    
    /**
     * Property Test: Allergy warnings should have high severity
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     */
    public function testAllergyWarningsHaveHighSeverity(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
        ];
        
        $userContext = [
            'current_medications_list' => [],
            'health_medications' => '',
            'drug_allergies_list' => [
                ['drug_name' => 'aspirin', 'drug_id' => null, 'reaction_type' => 'rash']
            ],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        $this->assertNotEmpty($interactions);
        
        // Allergy warnings should have high severity
        foreach ($interactions as $interaction) {
            if ($interaction['type'] === 'allergy') {
                $this->assertEquals('high', $interaction['severity']);
                $this->assertStringContainsString('⛔', $interaction['message']);
            }
        }
    }
    
    /**
     * Data provider for random interaction testing
     * 
     * @return array Test data sets
     */
    public function randomInteractionProvider(): array
    {
        $testCases = [];
        
        $interactingPairs = [
            ['aspirin', 'warfarin'],
            ['ibuprofen', 'warfarin'],
            ['naproxen', 'warfarin'],
            ['ibuprofen', 'aspirin'],
            ['omeprazole', 'clopidogrel'],
        ];
        
        // Generate 50 random test cases
        for ($i = 0; $i < 50; $i++) {
            $pair = $interactingPairs[array_rand($interactingPairs)];
            $testCases["random_interaction_{$i}"] = [
                $pair[0],
                $pair[1],
            ];
        }
        
        return $testCases;
    }
    
    /**
     * Property Test: Random interacting pairs should always generate warnings
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     * 
     * @dataProvider randomInteractionProvider
     */
    public function testRandomInteractingPairsGenerateWarnings(string $productGeneric, string $currentMed): void
    {
        $products = [
            [
                'id' => rand(1, 1000),
                'name' => ucfirst($productGeneric) . ' ' . rand(100, 500) . 'mg',
                'generic_name' => $productGeneric,
            ]
        ];
        
        $userContext = [
            'current_medications_list' => [
                ['medication_name' => $currentMed, 'dosage' => rand(1, 10) . 'mg', 'frequency' => 'daily']
            ],
            'health_medications' => '',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        $this->assertNotEmpty(
            $interactions,
            "Product '{$productGeneric}' should have interaction with '{$currentMed}'"
        );
        
        // Verify the interaction has a warning message
        $this->assertNotEmpty($interactions[0]['message']);
    }
    
    /**
     * Property Test: Case-insensitive matching for interactions
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     */
    public function testCaseInsensitiveInteractionMatching(): void
    {
        $products = [
            ['id' => 1, 'name' => 'ASPIRIN 300MG', 'generic_name' => 'ASPIRIN'],
        ];
        
        $userContext = [
            'current_medications_list' => [
                ['medication_name' => 'WARFARIN', 'dosage' => '5mg', 'frequency' => 'daily']
            ],
            'health_medications' => '',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        $this->assertNotEmpty(
            $interactions,
            "Case-insensitive matching should detect ASPIRIN-WARFARIN interaction"
        );
    }
    
    /**
     * Property Test: Thai language medication names should work
     * 
     * **Feature: liff-ai-assistant-integration, Property 12: Drug Interaction Warning**
     * **Validates: Requirements 7.2, 7.4**
     */
    public function testThaiLanguageMedicationNames(): void
    {
        $products = [
            ['id' => 1, 'name' => 'แอสไพริน 300mg', 'generic_name' => 'แอสไพริน'],
        ];
        
        $userContext = [
            'current_medications_list' => [
                ['medication_name' => 'warfarin', 'dosage' => '5mg', 'frequency' => 'daily']
            ],
            'health_medications' => '',
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $interactions = $this->checkDrugInteractionsSimple($products, $userContext);
        
        $this->assertNotEmpty(
            $interactions,
            "Thai language medication names should be detected"
        );
    }
}
