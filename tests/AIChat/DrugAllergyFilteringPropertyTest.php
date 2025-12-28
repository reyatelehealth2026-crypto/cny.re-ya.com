<?php
/**
 * Property-Based Test: Drug Allergy Filtering
 * 
 * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
 * **Validates: Requirements 7.3**
 * 
 * Property: For any medication recommendation, if the user has recorded drug allergies,
 * the recommended medications SHALL NOT include any medication matching the allergy list.
 */

namespace Tests\AIChat;

use PHPUnit\Framework\TestCase;

class DrugAllergyFilteringPropertyTest extends TestCase
{
    /**
     * The filterAllergicMedications function to test
     * We'll include it directly since it's a standalone function
     */
    
    /**
     * Filter out allergic medications from product recommendations
     * This is a copy of the function from pharmacy-ai.php for testing
     * 
     * @param array $products Array of recommended products
     * @param array $userContext User context including allergies
     * @return array Filtered array of products (without allergic medications)
     */
    private function filterAllergicMedications($products, $userContext) {
        if (empty($products)) return $products;
        
        // Get allergies from detailed list and text field
        $allergiesList = $userContext['drug_allergies_list'] ?? [];
        $allergiesText = $userContext['drug_allergies'] ?? $userContext['health_allergies'] ?? '';
        
        if (empty($allergiesList) && empty($allergiesText)) {
            return $products;
        }
        
        // Build allergy names array for matching
        $allergyNames = [];
        $allergyIds = [];
        foreach ($allergiesList as $allergy) {
            $allergyNames[] = mb_strtolower($allergy['drug_name'], 'UTF-8');
            if (!empty($allergy['drug_id'])) {
                $allergyIds[] = $allergy['drug_id'];
            }
        }
        
        $allergiesTextLower = mb_strtolower($allergiesText, 'UTF-8');
        
        // Filter products
        $filteredProducts = [];
        foreach ($products as $product) {
            $productId = $product['id'] ?? null;
            $productName = mb_strtolower($product['name'] ?? '', 'UTF-8');
            $genericName = mb_strtolower($product['generic_name'] ?? '', 'UTF-8');
            
            $isAllergic = false;
            
            // Check by ID
            if ($productId && in_array($productId, $allergyIds)) {
                $isAllergic = true;
            }
            
            // Check by name against allergy list
            if (!$isAllergic) {
                foreach ($allergyNames as $allergyName) {
                    if (mb_strpos($productName, $allergyName) !== false || 
                        mb_strpos($genericName, $allergyName) !== false ||
                        mb_strpos($allergyName, $productName) !== false ||
                        mb_strpos($allergyName, $genericName) !== false) {
                        $isAllergic = true;
                        break;
                    }
                }
            }
            
            // Check against text-based allergies
            if (!$isAllergic && !empty($allergiesTextLower)) {
                if (mb_strpos($allergiesTextLower, $productName) !== false || 
                    mb_strpos($allergiesTextLower, $genericName) !== false ||
                    mb_strpos($productName, $allergiesTextLower) !== false) {
                    $isAllergic = true;
                }
            }
            
            if (!$isAllergic) {
                $filteredProducts[] = $product;
            }
        }
        
        return $filteredProducts;
    }
    
    /**
     * Generate random products for testing
     * 
     * @param int $count Number of products to generate
     * @return array Array of product objects
     */
    private function generateRandomProducts($count = 5) {
        $drugNames = [
            ['name' => 'Paracetamol 500mg', 'generic_name' => 'paracetamol'],
            ['name' => 'Ibuprofen 400mg', 'generic_name' => 'ibuprofen'],
            ['name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
            ['name' => 'Amoxicillin 500mg', 'generic_name' => 'amoxicillin'],
            ['name' => 'Cetirizine 10mg', 'generic_name' => 'cetirizine'],
            ['name' => 'Omeprazole 20mg', 'generic_name' => 'omeprazole'],
            ['name' => 'Metformin 500mg', 'generic_name' => 'metformin'],
            ['name' => 'Loratadine 10mg', 'generic_name' => 'loratadine'],
            ['name' => 'Diclofenac 50mg', 'generic_name' => 'diclofenac'],
            ['name' => 'Naproxen 250mg', 'generic_name' => 'naproxen'],
        ];
        
        $products = [];
        $selectedIndices = array_rand($drugNames, min($count, count($drugNames)));
        if (!is_array($selectedIndices)) {
            $selectedIndices = [$selectedIndices];
        }
        
        foreach ($selectedIndices as $idx => $drugIdx) {
            $products[] = [
                'id' => $idx + 1,
                'name' => $drugNames[$drugIdx]['name'],
                'generic_name' => $drugNames[$drugIdx]['generic_name'],
                'price' => rand(50, 500),
            ];
        }
        
        return $products;
    }
    
    /**
     * Generate random allergies for testing
     * 
     * @param array $products Products to potentially be allergic to
     * @param int $count Number of allergies to generate
     * @return array Array of allergy records
     */
    private function generateRandomAllergies($products, $count = 2) {
        if (empty($products) || $count <= 0) return [];
        
        $allergies = [];
        $selectedIndices = array_rand($products, min($count, count($products)));
        if (!is_array($selectedIndices)) {
            $selectedIndices = [$selectedIndices];
        }
        
        foreach ($selectedIndices as $idx) {
            $product = $products[$idx];
            $allergies[] = [
                'id' => $idx + 100,
                'drug_name' => $product['generic_name'],
                'drug_id' => $product['id'],
                'reaction_type' => ['rash', 'breathing', 'swelling', 'other'][array_rand(['rash', 'breathing', 'swelling', 'other'])],
                'severity' => ['mild', 'moderate', 'severe'][array_rand(['mild', 'moderate', 'severe'])],
            ];
        }
        
        return $allergies;
    }
    
    /**
     * Data provider for property testing with various product and allergy combinations
     * 
     * @return array Test data sets
     */
    public function allergyFilteringProvider(): array
    {
        $testCases = [];
        
        // Generate 100 random test cases for property-based testing
        for ($i = 0; $i < 100; $i++) {
            $productCount = rand(1, 10);
            $allergyCount = rand(0, min(3, $productCount));
            
            $testCases["random_case_{$i}"] = [
                $productCount,
                $allergyCount,
            ];
        }
        
        return $testCases;
    }
    
    /**
     * Property Test: Filtered products SHALL NOT contain any allergic medications
     * 
     * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
     * **Validates: Requirements 7.3**
     * 
     * @dataProvider allergyFilteringProvider
     */
    public function testFilteredProductsDoNotContainAllergicMedications(int $productCount, int $allergyCount): void
    {
        // Generate random products
        $products = $this->generateRandomProducts($productCount);
        
        // Generate random allergies from the products
        $allergies = $this->generateRandomAllergies($products, $allergyCount);
        
        // Create user context with allergies
        $userContext = [
            'drug_allergies_list' => $allergies,
            'drug_allergies' => '',
        ];
        
        // Filter products
        $filteredProducts = $this->filterAllergicMedications($products, $userContext);
        
        // Always assert that filtered products is an array (ensures at least one assertion)
        $this->assertIsArray($filteredProducts, "Filtered products should be an array");
        
        // If no allergies, all products should be preserved
        if (empty($allergies)) {
            $this->assertCount(
                count($products),
                $filteredProducts,
                "With no allergies, all products should be preserved"
            );
            return;
        }
        
        // Property: No filtered product should match any allergy
        foreach ($filteredProducts as $product) {
            $productName = mb_strtolower($product['name'] ?? '', 'UTF-8');
            $genericName = mb_strtolower($product['generic_name'] ?? '', 'UTF-8');
            $productId = $product['id'] ?? null;
            
            foreach ($allergies as $allergy) {
                $allergyName = mb_strtolower($allergy['drug_name'], 'UTF-8');
                $allergyId = $allergy['drug_id'] ?? null;
                
                // Check ID match
                if ($productId && $allergyId) {
                    $this->assertNotEquals(
                        $productId,
                        $allergyId,
                        "Filtered product ID {$productId} should not match allergy ID {$allergyId}"
                    );
                }
                
                // Check name match
                $this->assertFalse(
                    mb_strpos($productName, $allergyName) !== false,
                    "Filtered product '{$product['name']}' should not contain allergy name '{$allergy['drug_name']}'"
                );
                
                $this->assertFalse(
                    mb_strpos($genericName, $allergyName) !== false,
                    "Filtered product generic name '{$genericName}' should not contain allergy name '{$allergyName}'"
                );
            }
        }
        
        // Additional property: If there are allergies, filtered count should be less than or equal to original
        $this->assertLessThanOrEqual(
            count($products),
            count($filteredProducts),
            "Filtered products count should be less than or equal to original products count"
        );
    }
    
    /**
     * Property Test: Products with no matching allergies should be preserved
     * 
     * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
     * **Validates: Requirements 7.3**
     */
    public function testNonAllergicProductsArePreserved(): void
    {
        // Products that user is NOT allergic to
        $products = [
            ['id' => 1, 'name' => 'Paracetamol 500mg', 'generic_name' => 'paracetamol'],
            ['id' => 2, 'name' => 'Cetirizine 10mg', 'generic_name' => 'cetirizine'],
            ['id' => 3, 'name' => 'Omeprazole 20mg', 'generic_name' => 'omeprazole'],
        ];
        
        // User is allergic to completely different drugs
        $userContext = [
            'drug_allergies_list' => [
                ['drug_name' => 'penicillin', 'drug_id' => 100, 'reaction_type' => 'rash'],
                ['drug_name' => 'sulfa', 'drug_id' => 101, 'reaction_type' => 'breathing'],
            ],
            'drug_allergies' => '',
        ];
        
        $filteredProducts = $this->filterAllergicMedications($products, $userContext);
        
        // All products should be preserved since none match allergies
        $this->assertCount(
            count($products),
            $filteredProducts,
            "All non-allergic products should be preserved"
        );
        
        // Verify each product is in the filtered list
        foreach ($products as $product) {
            $found = false;
            foreach ($filteredProducts as $filtered) {
                if ($filtered['id'] === $product['id']) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Product '{$product['name']}' should be in filtered list");
        }
    }
    
    /**
     * Property Test: All allergic products should be removed
     * 
     * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
     * **Validates: Requirements 7.3**
     */
    public function testAllAllergicProductsAreRemoved(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Paracetamol 500mg', 'generic_name' => 'paracetamol'],
            ['id' => 2, 'name' => 'Ibuprofen 400mg', 'generic_name' => 'ibuprofen'],
            ['id' => 3, 'name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
        ];
        
        // User is allergic to all products
        $userContext = [
            'drug_allergies_list' => [
                ['drug_name' => 'paracetamol', 'drug_id' => 1, 'reaction_type' => 'rash'],
                ['drug_name' => 'ibuprofen', 'drug_id' => 2, 'reaction_type' => 'breathing'],
                ['drug_name' => 'aspirin', 'drug_id' => 3, 'reaction_type' => 'swelling'],
            ],
            'drug_allergies' => '',
        ];
        
        $filteredProducts = $this->filterAllergicMedications($products, $userContext);
        
        // All products should be removed
        $this->assertEmpty(
            $filteredProducts,
            "All allergic products should be removed"
        );
    }
    
    /**
     * Property Test: Empty allergy list should preserve all products
     * 
     * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
     * **Validates: Requirements 7.3**
     */
    public function testEmptyAllergyListPreservesAllProducts(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Paracetamol 500mg', 'generic_name' => 'paracetamol'],
            ['id' => 2, 'name' => 'Ibuprofen 400mg', 'generic_name' => 'ibuprofen'],
        ];
        
        $userContext = [
            'drug_allergies_list' => [],
            'drug_allergies' => '',
        ];
        
        $filteredProducts = $this->filterAllergicMedications($products, $userContext);
        
        $this->assertCount(
            count($products),
            $filteredProducts,
            "Empty allergy list should preserve all products"
        );
    }
    
    /**
     * Property Test: Text-based allergies should also filter products
     * 
     * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
     * **Validates: Requirements 7.3**
     */
    public function testTextBasedAllergiesFilterProducts(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Paracetamol 500mg', 'generic_name' => 'paracetamol'],
            ['id' => 2, 'name' => 'Ibuprofen 400mg', 'generic_name' => 'ibuprofen'],
            ['id' => 3, 'name' => 'Aspirin 300mg', 'generic_name' => 'aspirin'],
        ];
        
        // User has text-based allergy (from health profile text field)
        $userContext = [
            'drug_allergies_list' => [],
            'drug_allergies' => 'aspirin',
        ];
        
        $filteredProducts = $this->filterAllergicMedications($products, $userContext);
        
        // Aspirin should be filtered out
        $this->assertCount(2, $filteredProducts, "Aspirin should be filtered out");
        
        foreach ($filteredProducts as $product) {
            $this->assertNotEquals(
                'aspirin',
                mb_strtolower($product['generic_name'], 'UTF-8'),
                "Aspirin should not be in filtered products"
            );
        }
    }
    
    /**
     * Property Test: Case-insensitive matching should work
     * 
     * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
     * **Validates: Requirements 7.3**
     */
    public function testCaseInsensitiveMatching(): void
    {
        $products = [
            ['id' => 1, 'name' => 'PARACETAMOL 500mg', 'generic_name' => 'PARACETAMOL'],
            ['id' => 2, 'name' => 'Ibuprofen 400mg', 'generic_name' => 'ibuprofen'],
        ];
        
        // Allergy in lowercase
        $userContext = [
            'drug_allergies_list' => [
                ['drug_name' => 'paracetamol', 'drug_id' => null, 'reaction_type' => 'rash'],
            ],
            'drug_allergies' => '',
        ];
        
        $filteredProducts = $this->filterAllergicMedications($products, $userContext);
        
        // Paracetamol should be filtered out despite case difference
        $this->assertCount(1, $filteredProducts, "Paracetamol should be filtered out (case-insensitive)");
        $this->assertEquals('ibuprofen', mb_strtolower($filteredProducts[0]['generic_name'], 'UTF-8'));
    }
    
    /**
     * Property Test: Partial name matching should work
     * 
     * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
     * **Validates: Requirements 7.3**
     */
    public function testPartialNameMatching(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Amoxicillin 500mg', 'generic_name' => 'amoxicillin'],
            ['id' => 2, 'name' => 'Ampicillin 250mg', 'generic_name' => 'ampicillin'],
            ['id' => 3, 'name' => 'Cetirizine 10mg', 'generic_name' => 'cetirizine'],
        ];
        
        // User is allergic to "amox" (partial match)
        $userContext = [
            'drug_allergies_list' => [
                ['drug_name' => 'amox', 'drug_id' => null, 'reaction_type' => 'rash'],
            ],
            'drug_allergies' => '',
        ];
        
        $filteredProducts = $this->filterAllergicMedications($products, $userContext);
        
        // Amoxicillin should be filtered out due to partial match
        $this->assertCount(2, $filteredProducts, "Amoxicillin should be filtered out (partial match)");
        
        foreach ($filteredProducts as $product) {
            $this->assertFalse(
                mb_strpos(mb_strtolower($product['generic_name'], 'UTF-8'), 'amox') !== false,
                "Products containing 'amox' should be filtered out"
            );
        }
    }
    
    /**
     * Property Test: ID-based matching should work
     * 
     * **Feature: liff-ai-assistant-integration, Property 11: Drug Allergy Filtering**
     * **Validates: Requirements 7.3**
     */
    public function testIdBasedMatching(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Drug A', 'generic_name' => 'drug_a'],
            ['id' => 2, 'name' => 'Drug B', 'generic_name' => 'drug_b'],
            ['id' => 3, 'name' => 'Drug C', 'generic_name' => 'drug_c'],
        ];
        
        // User is allergic to product with ID 2
        $userContext = [
            'drug_allergies_list' => [
                ['drug_name' => 'some_other_name', 'drug_id' => 2, 'reaction_type' => 'rash'],
            ],
            'drug_allergies' => '',
        ];
        
        $filteredProducts = $this->filterAllergicMedications($products, $userContext);
        
        // Product with ID 2 should be filtered out
        $this->assertCount(2, $filteredProducts, "Product with ID 2 should be filtered out");
        
        foreach ($filteredProducts as $product) {
            $this->assertNotEquals(2, $product['id'], "Product with ID 2 should not be in filtered list");
        }
    }
}
