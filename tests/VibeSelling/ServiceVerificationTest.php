<?php
/**
 * Service Verification Test for Vibe Selling OS v2
 * 
 * This test verifies that DrugPricingEngineService, CustomerHealthEngineService,
 * and PharmacyGhostDraftService are properly implemented and can be instantiated.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/DrugPricingEngineService.php';
require_once __DIR__ . '/../../classes/CustomerHealthEngineService.php';
require_once __DIR__ . '/../../classes/PharmacyGhostDraftService.php';

class ServiceVerificationTest extends TestCase
{
    /**
     * Test DrugPricingEngineService can be instantiated
     */
    public function testDrugPricingEngineServiceCanBeInstantiated(): void
    {
        // Create a mock PDO
        $pdo = $this->createMock(PDO::class);
        
        $service = new DrugPricingEngineService($pdo, 1);
        
        $this->assertInstanceOf(DrugPricingEngineService::class, $service);
    }
    
    /**
     * Test CustomerHealthEngineService can be instantiated
     */
    public function testCustomerHealthEngineServiceCanBeInstantiated(): void
    {
        // Create a mock PDO
        $pdo = $this->createMock(PDO::class);
        
        $service = new CustomerHealthEngineService($pdo, 1);
        
        $this->assertInstanceOf(CustomerHealthEngineService::class, $service);
    }
    
    /**
     * Test DrugPricingEngineService constants are defined
     */
    public function testDrugPricingEngineServiceConstantsAreDefined(): void
    {
        $this->assertEquals(10.0, DrugPricingEngineService::DEFAULT_MIN_MARGIN);
        $this->assertEquals('free_delivery', DrugPricingEngineService::ALT_FREE_DELIVERY);
        $this->assertEquals('bonus_vitamins', DrugPricingEngineService::ALT_BONUS_VITAMINS);
        $this->assertEquals('loyalty_points', DrugPricingEngineService::ALT_LOYALTY_POINTS);
        $this->assertEquals('next_purchase_discount', DrugPricingEngineService::ALT_NEXT_PURCHASE);
    }
    
    /**
     * Test CustomerHealthEngineService constants are defined
     */
    public function testCustomerHealthEngineServiceConstantsAreDefined(): void
    {
        $this->assertEquals('A', CustomerHealthEngineService::TYPE_DIRECT);
        $this->assertEquals('B', CustomerHealthEngineService::TYPE_CONCERNED);
        $this->assertEquals('C', CustomerHealthEngineService::TYPE_DETAILED);
        $this->assertEquals(5, CustomerHealthEngineService::MIN_MESSAGES_FOR_CLASSIFICATION);
    }
    
    /**
     * Test getDraftStyle returns correct structure for Type A
     */
    public function testGetDraftStyleTypeA(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = new CustomerHealthEngineService($pdo, 1);
        
        $style = $service->getDraftStyle('A');
        
        $this->assertEquals('A', $style['type']);
        $this->assertEquals('Direct', $style['typeName']);
        $this->assertEquals(50, $style['maxWords']);
        $this->assertFalse($style['useEmoji']);
        $this->assertFalse($style['includeDetails']);
        $this->assertTrue($style['includePrice']);
        $this->assertEquals('professional', $style['tone']);
        $this->assertIsArray($style['tips']);
    }
    
    /**
     * Test getDraftStyle returns correct structure for Type B
     */
    public function testGetDraftStyleTypeB(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = new CustomerHealthEngineService($pdo, 1);
        
        $style = $service->getDraftStyle('B');
        
        $this->assertEquals('B', $style['type']);
        $this->assertEquals('Concerned', $style['typeName']);
        $this->assertEquals(150, $style['maxWords']);
        $this->assertTrue($style['useEmoji']);
        $this->assertTrue($style['includeDetails']);
        $this->assertFalse($style['includePrice']);
        $this->assertEquals('empathetic', $style['tone']);
        $this->assertIsArray($style['tips']);
    }
    
    /**
     * Test getDraftStyle returns correct structure for Type C
     */
    public function testGetDraftStyleTypeC(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = new CustomerHealthEngineService($pdo, 1);
        
        $style = $service->getDraftStyle('C');
        
        $this->assertEquals('C', $style['type']);
        $this->assertEquals('Detail-oriented', $style['typeName']);
        $this->assertEquals(300, $style['maxWords']);
        $this->assertFalse($style['useEmoji']);
        $this->assertTrue($style['includeDetails']);
        $this->assertTrue($style['includePrice']);
        $this->assertTrue($style['includeComparison']);
        $this->assertTrue($style['includeScientific']);
        $this->assertEquals('informative', $style['tone']);
        $this->assertIsArray($style['tips']);
    }
    
    /**
     * Test getDraftStyle defaults to Type A for invalid type
     */
    public function testGetDraftStyleDefaultsToTypeA(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = new CustomerHealthEngineService($pdo, 1);
        
        $style = $service->getDraftStyle('X'); // Invalid type
        
        $this->assertEquals('A', $style['type']);
    }
}
