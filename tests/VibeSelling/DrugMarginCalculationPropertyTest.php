<?php
/**
 * Property-Based Test: Drug Margin Calculation Correctness
 * 
 * **Feature: vibe-selling-os-v2, Property 1: Drug Margin Calculation Correctness**
 * **Validates: Requirements 3.1**
 * 
 * Property: For any drug with cost and price data, the calculated margin percentage 
 * should equal ((price - cost) / price) * 100.
 */

namespace Tests\VibeSelling;

use PHPUnit\Framework\TestCase;
use PDO;

class DrugMarginCalculationPropertyTest extends TestCase
{
    private $db;
    private $lineAccountId = 1;
    private $service;
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create required tables
        $this->createTables();
        
        // Include the service class
        require_once __DIR__ . '/../../classes/DrugPricingEngineService.php';
        
        // Create service instance
        $this->service = new \DrugPricingEngineService($this->db, $this->lineAccountId);
    }
    
    private function createTables(): void
    {
        // business_items table for drug data
        $this->db->exec("
            CREATE TABLE business_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                line_account_id INTEGER,
                name VARCHAR(255),
                sku VARCHAR(100),
                price DECIMAL(10,2) DEFAULT 0,
                sale_price DECIMAL(10,2),
                cost_price DECIMAL(10,2) DEFAULT 0,
                stock INTEGER DEFAULT 0
            )
        ");
    }

    /**
     * Create a test drug with specified cost and price
     */
    private function createTestDrug(float $costPrice, float $sellingPrice, ?float $salePrice = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO business_items (line_account_id, name, sku, price, sale_price, cost_price, stock)
            VALUES (?, ?, ?, ?, ?, ?, 100)
        ");
        $stmt->execute([
            $this->lineAccountId,
            'Test Drug ' . uniqid(),
            'SKU-' . rand(1000, 9999),
            $sellingPrice,
            $salePrice,
            $costPrice
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Generate random valid cost and price values
     * Ensures price >= cost for valid margin scenarios
     */
    private function generateRandomPricing(): array
    {
        // Generate cost between 1 and 1000
        $cost = round(rand(100, 100000) / 100, 2);
        
        // Generate price that is >= cost (margin between 0% and 90%)
        $marginMultiplier = 1 + (rand(0, 900) / 1000); // 1.0 to 1.9
        $price = round($cost * $marginMultiplier, 2);
        
        return [
            'cost' => $cost,
            'price' => $price
        ];
    }
    
    /**
     * Calculate expected margin percentage using the formula from requirements
     * Formula: ((price - cost) / price) * 100
     */
    private function calculateExpectedMarginPercent(float $cost, float $price): float
    {
        if ($price <= 0) {
            return 0.0;
        }
        return (($price - $cost) / $price) * 100;
    }

    /**
     * Property Test: Margin Percentage Calculation Correctness
     * 
     * **Feature: vibe-selling-os-v2, Property 1: Drug Margin Calculation Correctness**
     * **Validates: Requirements 3.1**
     * 
     * For any drug with cost and price data, the calculated margin percentage 
     * should equal ((price - cost) / price) * 100.
     */
    public function testMarginPercentageCalculationCorrectness(): void
    {
        // Run 100 iterations with random pricing data
        for ($i = 0; $i < 100; $i++) {
            // Generate random cost and price
            $pricing = $this->generateRandomPricing();
            $cost = $pricing['cost'];
            $price = $pricing['price'];
            
            // Create test drug
            $drugId = $this->createTestDrug($cost, $price);
            
            // Act: Calculate margin using the service
            $result = $this->service->calculateMargin($drugId);
            
            // Calculate expected margin percentage
            $expectedMarginPercent = $this->calculateExpectedMarginPercent($cost, $price);
            
            // Assert: Margin percentage should match the formula
            $this->assertEqualsWithDelta(
                round($expectedMarginPercent, 2),
                $result['marginPercent'],
                0.01,
                "Margin percent should equal ((price - cost) / price) * 100 on iteration {$i}. " .
                "Cost: {$cost}, Price: {$price}, Expected: " . round($expectedMarginPercent, 2) . 
                ", Got: {$result['marginPercent']}"
            );
            
            // Assert: Margin amount should equal price - cost
            $expectedMargin = $price - $cost;
            $this->assertEqualsWithDelta(
                round($expectedMargin, 2),
                $result['margin'],
                0.01,
                "Margin should equal price - cost on iteration {$i}"
            );
            
            // Assert: Cost and price are correctly returned
            $this->assertEqualsWithDelta($cost, $result['cost'], 0.01);
            $this->assertEqualsWithDelta($price, $result['price'], 0.01);
        }
    }

    /**
     * Property Test: Custom Price Margin Calculation
     * 
     * **Feature: vibe-selling-os-v2, Property 1: Drug Margin Calculation Correctness**
     * **Validates: Requirements 3.1, 3.4**
     * 
     * For any drug with a custom price, the margin should be calculated 
     * using the custom price instead of the stored price.
     */
    public function testCustomPriceMarginCalculation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random pricing
            $pricing = $this->generateRandomPricing();
            $cost = $pricing['cost'];
            $originalPrice = $pricing['price'];
            
            // Generate a different custom price
            $customPrice = round($cost * (1 + rand(10, 200) / 100), 2);
            
            // Create test drug
            $drugId = $this->createTestDrug($cost, $originalPrice);
            
            // Act: Calculate margin with custom price
            $result = $this->service->calculateMargin($drugId, $customPrice);
            
            // Calculate expected margin with custom price
            $expectedMarginPercent = $this->calculateExpectedMarginPercent($cost, $customPrice);
            
            // Assert: Margin should be calculated using custom price
            $this->assertEqualsWithDelta(
                round($expectedMarginPercent, 2),
                $result['marginPercent'],
                0.01,
                "Custom price margin should use custom price for calculation on iteration {$i}"
            );
            
            // Assert: Returned price should be the custom price
            $this->assertEqualsWithDelta(
                $customPrice,
                $result['price'],
                0.01,
                "Returned price should be the custom price on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Zero Price Returns Zero Margin Percent
     * 
     * **Feature: vibe-selling-os-v2, Property 1: Drug Margin Calculation Correctness**
     * **Validates: Requirements 3.1**
     * 
     * Edge case: When price is zero, margin percentage should be 0 (avoid division by zero).
     */
    public function testZeroPriceReturnsZeroMarginPercent(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Generate random cost
            $cost = round(rand(100, 10000) / 100, 2);
            $price = 0.0;
            
            // Create test drug with zero price
            $drugId = $this->createTestDrug($cost, $price);
            
            // Act
            $result = $this->service->calculateMargin($drugId);
            
            // Assert: Margin percent should be 0 when price is 0
            $this->assertEquals(
                0.0,
                $result['marginPercent'],
                "Margin percent should be 0 when price is 0 on iteration {$i}"
            );
        }
    }
    
    /**
     * Property Test: Margin Percentage Range Validity
     * 
     * **Feature: vibe-selling-os-v2, Property 1: Drug Margin Calculation Correctness**
     * **Validates: Requirements 3.1**
     * 
     * For any drug where price >= cost > 0, margin percentage should be between 0 and 100.
     */
    public function testMarginPercentageRangeValidity(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate pricing where price >= cost
            $cost = round(rand(100, 50000) / 100, 2);
            $price = round($cost * (1 + rand(0, 100) / 100), 2); // 0% to 100% markup
            
            // Create test drug
            $drugId = $this->createTestDrug($cost, $price);
            
            // Act
            $result = $this->service->calculateMargin($drugId);
            
            // Assert: Margin percent should be between 0 and 100 when price >= cost
            $this->assertGreaterThanOrEqual(
                0,
                $result['marginPercent'],
                "Margin percent should be >= 0 when price >= cost on iteration {$i}"
            );
            
            $this->assertLessThanOrEqual(
                100,
                $result['marginPercent'],
                "Margin percent should be <= 100 when price >= cost on iteration {$i}"
            );
        }
    }
}
