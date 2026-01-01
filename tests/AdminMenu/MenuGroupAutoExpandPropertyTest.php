<?php
/**
 * Property-Based Test: Menu Group Auto-Expand
 * 
 * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
 * **Validates: Requirements 8.3**
 * 
 * Property: For any current page URL and for any menu group, if the current page URL 
 * matches any menu item within that group, the group SHALL be automatically expanded.
 */

namespace Tests\AdminMenu;

use PHPUnit\Framework\TestCase;

class MenuGroupAutoExpandPropertyTest extends TestCase
{
    /**
     * Menu structure extracted from header.php
     * Maps group keys to their menu items with page identifiers
     */
    private array $menuStructure = [];
    
    /**
     * Collapsible groups that should auto-expand
     */
    private array $collapsibleGroups = ['insights', 'clinical', 'patient', 'supply', 'facility'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->menuStructure = $this->parseMenuStructureFromHeader();
    }

    /**
     * Parse menu structure from header.php file
     * Extracts group keys, URLs, and page identifiers
     */
    private function parseMenuStructureFromHeader(): array
    {
        $headerPath = __DIR__ . '/../../includes/header.php';
        $content = file_get_contents($headerPath);
        
        $menuStructure = [];
        
        foreach ($this->collapsibleGroups as $groupKey) {
            $menuStructure[$groupKey] = $this->extractGroupItems($content, $groupKey);
        }
        
        return $menuStructure;
    }

    /**
     * Extract menu items for a specific group
     */
    private function extractGroupItems(string $content, string $groupKey): array
    {
        $items = [];
        
        // Find the section in the content
        $sectionPattern = "/'{$groupKey}'\s*=>\s*\[/";
        if (!preg_match($sectionPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $items;
        }
        
        $sectionStart = $matches[0][1];
        
        // Find the end of this section (next section or end)
        $nextSectionPos = strlen($content);
        foreach ($this->collapsibleGroups as $nextGroup) {
            if ($nextGroup !== $groupKey) {
                $nextPattern = "/'{$nextGroup}'\s*=>\s*\[/";
                if (preg_match($nextPattern, $content, $nextMatches, PREG_OFFSET_CAPTURE)) {
                    if ($nextMatches[0][1] > $sectionStart && $nextMatches[0][1] < $nextSectionPos) {
                        $nextSectionPos = $nextMatches[0][1];
                    }
                }
            }
        }
        
        $sectionContent = substr($content, $sectionStart, $nextSectionPos - $sectionStart);
        
        // Extract URL and page pairs
        $itemPattern = "/'url'\s*=>\s*'([^']+)'[^]]*'page'\s*=>\s*'([^']+)'/s";
        if (preg_match_all($itemPattern, $sectionContent, $itemMatches, PREG_SET_ORDER)) {
            foreach ($itemMatches as $match) {
                $items[] = [
                    'url' => $match[1],
                    'page' => $match[2],
                ];
            }
        }
        
        // Also try reverse order (page before url)
        $itemPatternReverse = "/'page'\s*=>\s*'([^']+)'[^]]*'url'\s*=>\s*'([^']+)'/s";
        if (preg_match_all($itemPatternReverse, $sectionContent, $itemMatchesReverse, PREG_SET_ORDER)) {
            foreach ($itemMatchesReverse as $match) {
                $item = [
                    'url' => $match[2],
                    'page' => $match[1],
                ];
                // Avoid duplicates
                $exists = false;
                foreach ($items as $existing) {
                    if ($existing['url'] === $item['url'] && $existing['page'] === $item['page']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $items[] = $item;
                }
            }
        }
        
        return $items;
    }

    /**
     * Simulate the auto-expand logic from header.php
     * Returns which groups should be expanded for a given current page
     */
    private function getExpandedGroups(string $currentPage, bool $isShop, bool $isInventory): array
    {
        $expandedGroups = [];
        $isSubfolder = $isShop || $isInventory;
        
        foreach ($this->menuStructure as $groupKey => $items) {
            foreach ($items as $item) {
                $url = $item['url'];
                $page = $item['page'];
                
                // Determine URL type
                $itemIsShopUrl = strpos($url, '/shop/') !== false || strpos($url, 'shop/') === 0;
                $itemIsInventoryUrl = strpos($url, '/inventory/') !== false || strpos($url, 'inventory/') === 0;
                
                $isActive = false;
                
                if ($itemIsShopUrl) {
                    // Shop folder items - active when in shop folder and page matches
                    $isActive = $isShop && $currentPage === $page;
                } elseif ($itemIsInventoryUrl) {
                    // Inventory folder items - active when in inventory folder and page matches
                    $isActive = $isInventory && $currentPage === $page;
                } else {
                    // Root level items - active when not in any subfolder and page matches
                    $isActive = !$isSubfolder && $currentPage === $page;
                }
                
                if ($isActive) {
                    $expandedGroups[$groupKey] = true;
                    break; // Found active item in this group
                }
            }
        }
        
        return array_keys($expandedGroups);
    }

    /**
     * Get all possible page values from menu structure
     */
    private function getAllPages(): array
    {
        $pages = [];
        foreach ($this->menuStructure as $groupKey => $items) {
            foreach ($items as $item) {
                $pages[] = [
                    'page' => $item['page'],
                    'url' => $item['url'],
                    'group' => $groupKey,
                ];
            }
        }
        return $pages;
    }

    /**
     * Property Test: When navigating to a page within a group, that group is expanded
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     * 
     * For any menu item in any collapsible group, when the current page matches
     * that item's page identifier, the containing group SHALL be expanded.
     */
    public function testGroupExpandsWhenContainsActivePage(): void
    {
        $allPages = $this->getAllPages();
        
        $this->assertNotEmpty($allPages, 'Menu structure should contain pages');
        
        foreach ($allPages as $pageInfo) {
            $currentPage = $pageInfo['page'];
            $expectedGroup = $pageInfo['group'];
            $url = $pageInfo['url'];
            
            // Determine folder context based on URL
            $isShop = strpos($url, '/shop/') !== false;
            $isInventory = strpos($url, '/inventory/') !== false;
            
            $expandedGroups = $this->getExpandedGroups($currentPage, $isShop, $isInventory);
            
            $this->assertContains(
                $expectedGroup,
                $expandedGroups,
                "Group '{$expectedGroup}' should be expanded when current page is '{$currentPage}' (URL: {$url})"
            );
        }
    }

    /**
     * Property Test: Only the group containing the active page is auto-expanded
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     * 
     * For any current page, at most one group should be auto-expanded based on
     * the active page (other groups may be expanded via localStorage, but not auto-expanded).
     */
    public function testOnlyOneGroupAutoExpandedPerPage(): void
    {
        $allPages = $this->getAllPages();
        
        foreach ($allPages as $pageInfo) {
            $currentPage = $pageInfo['page'];
            $url = $pageInfo['url'];
            
            // Determine folder context based on URL
            $isShop = strpos($url, '/shop/') !== false;
            $isInventory = strpos($url, '/inventory/') !== false;
            
            $expandedGroups = $this->getExpandedGroups($currentPage, $isShop, $isInventory);
            
            // Should have exactly one expanded group for this page
            $this->assertCount(
                1,
                $expandedGroups,
                "Exactly one group should be auto-expanded for page '{$currentPage}'. " .
                "Found: " . implode(', ', $expandedGroups)
            );
        }
    }

    /**
     * Property Test: Random page selection always results in correct group expansion
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     * 
     * Property-based test: For 100 random page selections, verify the correct
     * group is expanded.
     */
    public function testRandomPageSelectionExpandsCorrectGroup(): void
    {
        $allPages = $this->getAllPages();
        
        $this->assertNotEmpty($allPages, 'Menu structure should contain pages');
        
        // Run 100 random selections
        for ($i = 0; $i < 100; $i++) {
            $randomIndex = array_rand($allPages);
            $pageInfo = $allPages[$randomIndex];
            
            $currentPage = $pageInfo['page'];
            $expectedGroup = $pageInfo['group'];
            $url = $pageInfo['url'];
            
            // Determine folder context based on URL
            $isShop = strpos($url, '/shop/') !== false;
            $isInventory = strpos($url, '/inventory/') !== false;
            
            $expandedGroups = $this->getExpandedGroups($currentPage, $isShop, $isInventory);
            
            $this->assertContains(
                $expectedGroup,
                $expandedGroups,
                "Iteration {$i}: Group '{$expectedGroup}' should be expanded for page '{$currentPage}'"
            );
        }
    }

    /**
     * Property Test: Shop folder pages expand correct group
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     */
    public function testShopFolderPagesExpandCorrectGroup(): void
    {
        $shopPages = [];
        foreach ($this->menuStructure as $groupKey => $items) {
            foreach ($items as $item) {
                if (strpos($item['url'], '/shop/') !== false) {
                    $shopPages[] = [
                        'page' => $item['page'],
                        'url' => $item['url'],
                        'group' => $groupKey,
                    ];
                }
            }
        }
        
        foreach ($shopPages as $pageInfo) {
            $currentPage = $pageInfo['page'];
            $expectedGroup = $pageInfo['group'];
            
            // Simulate being in shop folder
            $expandedGroups = $this->getExpandedGroups($currentPage, true, false);
            
            $this->assertContains(
                $expectedGroup,
                $expandedGroups,
                "Shop page '{$currentPage}' should expand group '{$expectedGroup}'"
            );
        }
    }

    /**
     * Property Test: Inventory folder pages expand correct group
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     */
    public function testInventoryFolderPagesExpandCorrectGroup(): void
    {
        $inventoryPages = [];
        foreach ($this->menuStructure as $groupKey => $items) {
            foreach ($items as $item) {
                if (strpos($item['url'], '/inventory/') !== false) {
                    $inventoryPages[] = [
                        'page' => $item['page'],
                        'url' => $item['url'],
                        'group' => $groupKey,
                    ];
                }
            }
        }
        
        foreach ($inventoryPages as $pageInfo) {
            $currentPage = $pageInfo['page'];
            $expectedGroup = $pageInfo['group'];
            
            // Simulate being in inventory folder
            $expandedGroups = $this->getExpandedGroups($currentPage, false, true);
            
            $this->assertContains(
                $expectedGroup,
                $expandedGroups,
                "Inventory page '{$currentPage}' should expand group '{$expectedGroup}'"
            );
        }
    }

    /**
     * Property Test: Root level pages expand correct group
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     */
    public function testRootLevelPagesExpandCorrectGroup(): void
    {
        $rootPages = [];
        foreach ($this->menuStructure as $groupKey => $items) {
            foreach ($items as $item) {
                if (strpos($item['url'], '/shop/') === false && 
                    strpos($item['url'], '/inventory/') === false) {
                    $rootPages[] = [
                        'page' => $item['page'],
                        'url' => $item['url'],
                        'group' => $groupKey,
                    ];
                }
            }
        }
        
        foreach ($rootPages as $pageInfo) {
            $currentPage = $pageInfo['page'];
            $expectedGroup = $pageInfo['group'];
            
            // Simulate being at root level (not in any subfolder)
            $expandedGroups = $this->getExpandedGroups($currentPage, false, false);
            
            $this->assertContains(
                $expectedGroup,
                $expandedGroups,
                "Root page '{$currentPage}' should expand group '{$expectedGroup}'"
            );
        }
    }

    /**
     * Property Test: Non-matching pages don't auto-expand any group
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     */
    public function testNonMatchingPageDoesNotAutoExpand(): void
    {
        // Test with a page that doesn't exist in any menu
        $nonExistentPages = ['nonexistent-page', 'random-page-xyz', 'test-page-123'];
        
        foreach ($nonExistentPages as $currentPage) {
            $expandedGroups = $this->getExpandedGroups($currentPage, false, false);
            
            $this->assertEmpty(
                $expandedGroups,
                "Non-existent page '{$currentPage}' should not auto-expand any group"
            );
        }
    }

    /**
     * Property Test: All collapsible groups have the collapsible flag
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     */
    public function testAllMainGroupsAreCollapsible(): void
    {
        $headerPath = __DIR__ . '/../../includes/header.php';
        $content = file_get_contents($headerPath);
        
        foreach ($this->collapsibleGroups as $groupKey) {
            // Check that the group has 'collapsible' => true
            $pattern = "/'{$groupKey}'\s*=>\s*\[[^\]]*'collapsible'\s*=>\s*true/s";
            
            $this->assertMatchesRegularExpression(
                $pattern,
                $content,
                "Group '{$groupKey}' should have 'collapsible' => true"
            );
        }
    }

    /**
     * Data provider for group-specific tests
     */
    public function menuGroupProvider(): array
    {
        return [
            'insights_group' => ['insights'],
            'clinical_group' => ['clinical'],
            'patient_group' => ['patient'],
            'supply_group' => ['supply'],
            'facility_group' => ['facility'],
        ];
    }

    /**
     * Property Test: Each group has at least one page that can trigger auto-expand
     * 
     * **Feature: admin-menu-restructure, Property 2: Menu Group Auto-Expand**
     * **Validates: Requirements 8.3**
     * 
     * @dataProvider menuGroupProvider
     */
    public function testEachGroupHasExpandablePage(string $groupKey): void
    {
        $this->assertArrayHasKey(
            $groupKey,
            $this->menuStructure,
            "Group '{$groupKey}' should exist in menu structure"
        );
        
        $items = $this->menuStructure[$groupKey];
        
        $this->assertNotEmpty(
            $items,
            "Group '{$groupKey}' should have at least one menu item"
        );
        
        // Verify at least one item has a valid page identifier
        $hasValidPage = false;
        foreach ($items as $item) {
            if (!empty($item['page'])) {
                $hasValidPage = true;
                break;
            }
        }
        
        $this->assertTrue(
            $hasValidPage,
            "Group '{$groupKey}' should have at least one item with a valid page identifier"
        );
    }
}
