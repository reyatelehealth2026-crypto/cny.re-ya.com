<?php
/**
 * ProductUnitService - จัดการหน่วยสินค้า (Multi-Unit)
 * สินค้า 1 ตัวมีได้หลายหน่วย เช่น ขวด, โหล แต่ละหน่วยมีราคาต่างกัน
 */

class ProductUnitService {
    private $db;
    private $lineAccountId;
    
    public function __construct($db, $lineAccountId = null) {
        $this->db = $db;
        $this->lineAccountId = $lineAccountId;
    }
    
    /**
     * Get all units for a product
     */
    public function getProductUnits(int $productId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM product_units 
            WHERE product_id = ? AND is_active = 1
            ORDER BY is_base_unit DESC, factor ASC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get purchase units for a product (for PO)
     */
    public function getPurchaseUnits(int $productId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM product_units 
            WHERE product_id = ? AND is_active = 1 AND is_purchase_unit = 1
            ORDER BY is_base_unit DESC, factor ASC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get sale units for a product
     */
    public function getSaleUnits(int $productId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM product_units 
            WHERE product_id = ? AND is_active = 1 AND is_sale_unit = 1
            ORDER BY is_base_unit DESC, factor ASC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get base unit for a product
     */
    public function getBaseUnit(int $productId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM product_units 
            WHERE product_id = ? AND is_base_unit = 1 AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Get unit by ID
     */
    public function getUnit(int $unitId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM product_units WHERE id = ?");
        $stmt->execute([$unitId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Create unit for product
     */
    public function createUnit(array $data): int {
        // If this is base unit, unset other base units
        if (!empty($data['is_base_unit'])) {
            $stmt = $this->db->prepare("UPDATE product_units SET is_base_unit = 0 WHERE product_id = ?");
            $stmt->execute([$data['product_id']]);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO product_units 
            (line_account_id, product_id, unit_name, unit_code, factor, cost_price, sale_price, barcode, is_base_unit, is_purchase_unit, is_sale_unit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->lineAccountId,
            $data['product_id'],
            $data['unit_name'],
            $data['unit_code'] ?? null,
            $data['factor'] ?? 1,
            $data['cost_price'] ?? null,
            $data['sale_price'] ?? null,
            $data['barcode'] ?? null,
            $data['is_base_unit'] ?? 0,
            $data['is_purchase_unit'] ?? 1,
            $data['is_sale_unit'] ?? 1
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update unit
     */
    public function updateUnit(int $unitId, array $data): bool {
        // If setting as base unit, unset others
        if (!empty($data['is_base_unit'])) {
            $stmt = $this->db->prepare("SELECT product_id FROM product_units WHERE id = ?");
            $stmt->execute([$unitId]);
            $productId = $stmt->fetchColumn();
            
            $stmt = $this->db->prepare("UPDATE product_units SET is_base_unit = 0 WHERE product_id = ? AND id != ?");
            $stmt->execute([$productId, $unitId]);
        }
        
        $stmt = $this->db->prepare("
            UPDATE product_units SET
                unit_name = ?, unit_code = ?, factor = ?, cost_price = ?, sale_price = ?,
                barcode = ?, is_base_unit = ?, is_purchase_unit = ?, is_sale_unit = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['unit_name'],
            $data['unit_code'] ?? null,
            $data['factor'] ?? 1,
            $data['cost_price'] ?? null,
            $data['sale_price'] ?? null,
            $data['barcode'] ?? null,
            $data['is_base_unit'] ?? 0,
            $data['is_purchase_unit'] ?? 1,
            $data['is_sale_unit'] ?? 1,
            $unitId
        ]);
    }
    
    /**
     * Delete unit
     */
    public function deleteUnit(int $unitId): bool {
        $stmt = $this->db->prepare("UPDATE product_units SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$unitId]);
    }
    
    /**
     * Convert quantity between units
     */
    public function convertQuantity(int $productId, float $quantity, int $fromUnitId, int $toUnitId): float {
        $fromUnit = $this->getUnit($fromUnitId);
        $toUnit = $this->getUnit($toUnitId);
        
        if (!$fromUnit || !$toUnit) {
            throw new Exception("Unit not found");
        }
        
        // Convert to base unit first, then to target unit
        $baseQuantity = $quantity * $fromUnit['factor'];
        return $baseQuantity / $toUnit['factor'];
    }
    
    /**
     * Get all products with their units
     */
    public function getProductsWithUnits(): array {
        $sql = "SELECT bi.id, bi.name, bi.sku, bi.unit, bi.stock, bi.cost_price as default_cost,
                       pu.id as unit_id, pu.unit_name, pu.unit_code, pu.factor, 
                       pu.cost_price as unit_cost, pu.is_base_unit
                FROM business_items bi
                LEFT JOIN product_units pu ON bi.id = pu.product_id AND pu.is_active = 1 AND pu.is_purchase_unit = 1
                WHERE bi.is_active = 1";
        $params = [];
        
        if ($this->lineAccountId) {
            $sql .= " AND (bi.line_account_id = ? OR bi.line_account_id IS NULL)";
            $params[] = $this->lineAccountId;
        }
        
        $sql .= " ORDER BY bi.name, pu.factor";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ensure product has at least base unit from business_items.unit
     */
    public function ensureBaseUnit(int $productId): void {
        // Check if has any unit
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM product_units WHERE product_id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        
        if ($stmt->fetchColumn() == 0) {
            // Get unit from business_items
            $stmt = $this->db->prepare("SELECT unit, cost_price, price FROM business_items WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $this->createUnit([
                    'product_id' => $productId,
                    'unit_name' => $product['unit'] ?: 'ชิ้น',
                    'unit_code' => 'PCS',
                    'factor' => 1,
                    'cost_price' => $product['cost_price'],
                    'sale_price' => $product['price'],
                    'is_base_unit' => 1
                ]);
            }
        }
    }
}
