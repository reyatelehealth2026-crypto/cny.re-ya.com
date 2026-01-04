<?php
/**
 * WMSPrintService - Print Service for WMS
 * Generates packing slips and shipping labels
 * 
 * Requirements: 3.4, 4.1, 4.2, 4.5, 8.2, 8.3
 */

class WMSPrintService {
    private $db;
    private $lineAccountId;
    
    public function __construct($db, $lineAccountId = null) {
        $this->db = $db;
        $this->lineAccountId = $lineAccountId;
    }
    
    /**
     * Set line account ID
     */
    public function setLineAccountId(int $lineAccountId): void {
        $this->lineAccountId = $lineAccountId;
    }
    
    /**
     * Get order details for printing
     * 
     * @param int $orderId Order ID
     * @return array Order data with items and shop settings
     * @throws Exception if order not found
     */
    private function getOrderForPrint(int $orderId): array {
        // Get order
        $stmt = $this->db->prepare("
            SELECT t.*, 
                   u.display_name as customer_name,
                   u.phone as customer_phone,
                   u.email as customer_email
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception("Order not found");
        }
        
        // Verify line account access
        if ($this->lineAccountId && $order['line_account_id'] != $this->lineAccountId) {
            throw new Exception("Access denied to this order");
        }
        
        // Get order items
        $stmt = $this->db->prepare("
            SELECT ti.*, 
                   bi.storage_condition as storage_location
            FROM transaction_items ti
            LEFT JOIN business_items bi ON ti.product_id = bi.id
            WHERE ti.transaction_id = ?
            ORDER BY ti.product_name ASC
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get shop settings for sender info
        $lineAccountId = $order['line_account_id'] ?? $this->lineAccountId;
        if ($lineAccountId) {
            $stmt = $this->db->prepare("
                SELECT * FROM shop_settings WHERE line_account_id = ? LIMIT 1
            ");
            $stmt->execute([$lineAccountId]);
        } else {
            $stmt = $this->db->query("SELECT * FROM shop_settings WHERE id = 1 LIMIT 1");
        }
        $order['shop'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
        return $order;
    }

    
    /**
     * Generate packing slip HTML for an order
     * Requirements: 3.4
     * 
     * @param int $orderId Order ID
     * @return string HTML content for packing slip
     */
    public function generatePackingSlip(int $orderId): string {
        $order = $this->getOrderForPrint($orderId);
        
        // Record print timestamp
        $this->recordLabelPrint($orderId, 'packing_slip');
        
        $shopName = $order['shop']['shop_name'] ?? 'ร้านค้า';
        $shopPhone = $order['shop']['contact_phone'] ?? '';
        $shopAddress = $order['shop']['address'] ?? '';
        
        $html = $this->getPackingSlipHeader($shopName);
        $html .= $this->getPackingSlipContent($order, $shopName, $shopPhone, $shopAddress);
        $html .= $this->getPackingSlipFooter();
        
        return $html;
    }
    
    /**
     * Generate packing slip header HTML
     */
    private function getPackingSlipHeader(string $shopName): string {
        return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing Slip - ' . htmlspecialchars($shopName) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Sarabun", "Helvetica Neue", Arial, sans-serif; font-size: 12px; line-height: 1.4; }
        .packing-slip { width: 210mm; min-height: 148mm; padding: 10mm; margin: 0 auto; background: white; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        .shop-info h1 { font-size: 18px; margin-bottom: 5px; }
        .shop-info p { font-size: 11px; color: #666; }
        .order-info { text-align: right; }
        .order-info .order-number { font-size: 16px; font-weight: bold; }
        .order-info .order-date { font-size: 11px; color: #666; }
        .section { margin-bottom: 15px; }
        .section-title { font-size: 13px; font-weight: bold; background: #f5f5f5; padding: 5px 10px; margin-bottom: 10px; }
        .customer-info { display: flex; gap: 20px; }
        .customer-info .col { flex: 1; }
        .customer-info label { font-weight: bold; display: block; margin-bottom: 3px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background: #f5f5f5; font-weight: bold; }
        .items-table .qty { text-align: center; width: 60px; }
        .items-table .check { text-align: center; width: 50px; }
        .checkbox { width: 16px; height: 16px; border: 2px solid #333; display: inline-block; }
        .totals { margin-top: 15px; text-align: right; }
        .totals table { margin-left: auto; }
        .totals td { padding: 3px 10px; }
        .totals .total-row { font-weight: bold; font-size: 14px; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px dashed #ccc; font-size: 10px; color: #666; text-align: center; }
        .notes { margin-top: 15px; padding: 10px; background: #fffbeb; border: 1px solid #fcd34d; }
        .notes-title { font-weight: bold; margin-bottom: 5px; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .packing-slip { page-break-after: always; }
            .packing-slip:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>';
    }

    
    /**
     * Generate packing slip content HTML
     */
    private function getPackingSlipContent(array $order, string $shopName, string $shopPhone, string $shopAddress): string {
        $orderNumber = htmlspecialchars($order['order_number'] ?? '');
        $orderDate = isset($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : '';
        
        // Customer info
        $customerName = htmlspecialchars($order['shipping_name'] ?? $order['customer_name'] ?? '');
        $customerPhone = htmlspecialchars($order['shipping_phone'] ?? $order['customer_phone'] ?? '');
        $customerAddress = htmlspecialchars($order['shipping_address'] ?? '');
        
        $html = '<div class="packing-slip">
    <div class="header">
        <div class="shop-info">
            <h1>' . htmlspecialchars($shopName) . '</h1>
            <p>' . htmlspecialchars($shopAddress) . '</p>
            <p>โทร: ' . htmlspecialchars($shopPhone) . '</p>
        </div>
        <div class="order-info">
            <div class="order-number">ใบจัดสินค้า</div>
            <div class="order-number">#' . $orderNumber . '</div>
            <div class="order-date">' . $orderDate . '</div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">ข้อมูลลูกค้า</div>
        <div class="customer-info">
            <div class="col">
                <label>ชื่อผู้รับ:</label>
                <p>' . $customerName . '</p>
            </div>
            <div class="col">
                <label>เบอร์โทร:</label>
                <p>' . $customerPhone . '</p>
            </div>
        </div>
        <div style="margin-top: 10px;">
            <label style="font-weight: bold;">ที่อยู่จัดส่ง:</label>
            <p>' . nl2br($customerAddress) . '</p>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">รายการสินค้า</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="check">✓</th>
                    <th>รหัสสินค้า</th>
                    <th>ชื่อสินค้า</th>
                    <th class="qty">จำนวน</th>
                    <th>ตำแหน่ง</th>
                </tr>
            </thead>
            <tbody>';
        
        $totalItems = 0;
        foreach ($order['items'] as $item) {
            $totalItems += (int)$item['quantity'];
            $html .= '
                <tr>
                    <td class="check"><span class="checkbox"></span></td>
                    <td>' . htmlspecialchars($item['product_sku'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($item['product_name']) . '</td>
                    <td class="qty">' . (int)$item['quantity'] . '</td>
                    <td>' . htmlspecialchars($item['storage_location'] ?? '-') . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">รวมทั้งหมด:</td>
                    <td class="qty" style="font-weight: bold;">' . $totalItems . ' ชิ้น</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>';
        
        // Add notes if present
        $notes = $order['note'] ?? $order['notes'] ?? '';
        if (!empty($notes)) {
            $html .= '
    <div class="notes">
        <div class="notes-title">📝 หมายเหตุจากลูกค้า:</div>
        <p>' . nl2br(htmlspecialchars($notes)) . '</p>
    </div>';
        }
        
        $html .= '
    <div class="footer">
        <p>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . ' | ผู้จัดสินค้า: _________________ | ผู้ตรวจสอบ: _________________</p>
    </div>
</div>';
        
        return $html;
    }
    
    /**
     * Generate packing slip footer HTML
     */
    private function getPackingSlipFooter(): string {
        return '</body></html>';
    }

    
    /**
     * Generate shipping label HTML for an order
     * Requirements: 4.1, 4.2, 4.5
     * 
     * Label contains:
     * - Recipient name, address, order number (4.1)
     * - Sender information from shop settings (4.2)
     * - Barcode/QR code for tracking if available (4.3)
     * - Standard label size A6/10x15cm (4.4)
     * - Records print timestamp for audit (4.5)
     * 
     * @param int $orderId Order ID
     * @return string HTML content for shipping label
     */
    public function generateShippingLabel(int $orderId): string {
        $order = $this->getOrderForPrint($orderId);
        
        // Record print timestamp (Requirements 4.5)
        $this->recordLabelPrint($orderId, 'shipping_label');
        
        // Shop/Sender info (Requirements 4.2)
        $shopName = $order['shop']['shop_name'] ?? 'ร้านค้า';
        $shopPhone = $order['shop']['contact_phone'] ?? '';
        $shopAddress = $order['shop']['address'] ?? '';
        
        $html = $this->getShippingLabelHeader($shopName);
        $html .= $this->getShippingLabelContent($order, $shopName, $shopPhone, $shopAddress);
        $html .= $this->getShippingLabelFooter();
        
        return $html;
    }
    
    /**
     * Generate shipping label header HTML
     * Standard A6 size (105mm x 148mm) or 10x15cm
     */
    private function getShippingLabelHeader(string $shopName): string {
        return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - ' . htmlspecialchars($shopName) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Sarabun", "Helvetica Neue", Arial, sans-serif; font-size: 11px; line-height: 1.3; }
        .shipping-label { width: 100mm; height: 150mm; padding: 5mm; margin: 0 auto; background: white; border: 1px solid #000; position: relative; }
        .label-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 8px; }
        .order-number { font-size: 14px; font-weight: bold; }
        .carrier-info { font-size: 12px; font-weight: bold; text-align: right; }
        .section { margin-bottom: 8px; padding: 5px; }
        .section-title { font-size: 10px; font-weight: bold; color: #666; text-transform: uppercase; margin-bottom: 3px; }
        .recipient { background: #f5f5f5; border: 1px solid #ddd; padding: 8px; }
        .recipient-name { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
        .recipient-phone { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .recipient-address { font-size: 12px; line-height: 1.4; }
        .sender { font-size: 10px; border-top: 1px dashed #ccc; padding-top: 5px; margin-top: 5px; }
        .sender-title { font-weight: bold; }
        .tracking-section { text-align: center; padding: 8px; border: 2px solid #000; margin-top: 8px; }
        .tracking-number { font-size: 14px; font-weight: bold; font-family: monospace; letter-spacing: 1px; }
        .barcode { margin: 5px 0; font-family: "Libre Barcode 39", monospace; font-size: 40px; }
        .qr-placeholder { width: 60px; height: 60px; border: 1px solid #ccc; margin: 5px auto; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #999; }
        .items-summary { font-size: 10px; border-top: 1px solid #ddd; padding-top: 5px; margin-top: 5px; }
        .print-date { position: absolute; bottom: 3mm; right: 5mm; font-size: 8px; color: #999; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .shipping-label { page-break-after: always; border: none; }
            .shipping-label:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>';
    }

    
    /**
     * Generate shipping label content HTML
     * Requirements: 4.1 (recipient name, address, order number), 4.2 (sender info)
     */
    private function getShippingLabelContent(array $order, string $shopName, string $shopPhone, string $shopAddress): string {
        $orderNumber = htmlspecialchars($order['order_number'] ?? '');
        
        // Recipient info (Requirements 4.1)
        $recipientName = htmlspecialchars($order['shipping_name'] ?? $order['customer_name'] ?? '');
        $recipientPhone = htmlspecialchars($order['shipping_phone'] ?? $order['customer_phone'] ?? '');
        $recipientAddress = htmlspecialchars($order['shipping_address'] ?? '');
        
        // Carrier and tracking
        $carrier = htmlspecialchars($order['carrier'] ?? $order['shipping_provider'] ?? '');
        $trackingNumber = htmlspecialchars($order['tracking_number'] ?? $order['shipping_tracking'] ?? '');
        
        // Calculate total items
        $totalItems = 0;
        foreach ($order['items'] as $item) {
            $totalItems += (int)$item['quantity'];
        }
        
        $html = '<div class="shipping-label">
    <div class="label-header">
        <div class="order-number">#' . $orderNumber . '</div>
        <div class="carrier-info">' . ($carrier ?: 'ขนส่ง') . '</div>
    </div>
    
    <div class="section recipient">
        <div class="section-title">ผู้รับ / Recipient</div>
        <div class="recipient-name">' . $recipientName . '</div>
        <div class="recipient-phone">📞 ' . $recipientPhone . '</div>
        <div class="recipient-address">' . nl2br($recipientAddress) . '</div>
    </div>
    
    <div class="section sender">
        <div class="sender-title">ผู้ส่ง / Sender:</div>
        <div>' . htmlspecialchars($shopName) . '</div>
        <div>' . htmlspecialchars($shopPhone) . '</div>
        <div>' . htmlspecialchars($shopAddress) . '</div>
    </div>';
        
        // Tracking section (Requirements 4.3)
        if (!empty($trackingNumber)) {
            $html .= '
    <div class="tracking-section">
        <div class="section-title">เลขพัสดุ / Tracking</div>
        <div class="tracking-number">' . $trackingNumber . '</div>
        <div class="barcode">*' . $trackingNumber . '*</div>
    </div>';
        } else {
            $html .= '
    <div class="tracking-section">
        <div class="section-title">เลขพัสดุ / Tracking</div>
        <div style="color: #999; font-style: italic;">รอกรอกเลขพัสดุ</div>
    </div>';
        }
        
        $html .= '
    <div class="items-summary">
        📦 จำนวนสินค้า: ' . $totalItems . ' ชิ้น | ' . count($order['items']) . ' รายการ
    </div>
    
    <div class="print-date">พิมพ์: ' . date('d/m/Y H:i') . '</div>
</div>';
        
        return $html;
    }
    
    /**
     * Generate shipping label footer HTML
     */
    private function getShippingLabelFooter(): string {
        return '</body></html>';
    }

    
    /**
     * Record label print timestamp for audit
     * Requirements: 4.5
     * 
     * @param int $orderId Order ID
     * @param string $type Print type (packing_slip, shipping_label)
     */
    private function recordLabelPrint(int $orderId, string $type): void {
        try {
            // Update label_printed_at timestamp
            $stmt = $this->db->prepare("
                UPDATE transactions 
                SET label_printed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$orderId]);
            
            // Log activity
            $lineAccountId = $this->lineAccountId ?? 0;
            $stmt = $this->db->prepare("
                INSERT INTO wms_activity_logs 
                (line_account_id, order_id, action, notes, metadata, created_at)
                VALUES (?, ?, 'label_printed', ?, ?, NOW())
            ");
            $stmt->execute([
                $lineAccountId,
                $orderId,
                "Printed {$type}",
                json_encode(['type' => $type, 'printed_at' => date('Y-m-d H:i:s')])
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the print operation
            error_log("Failed to record label print for order {$orderId}: " . $e->getMessage());
        }
    }

    
    // =============================================
    // BATCH PRINTING METHODS (Requirements 8.2, 8.3)
    // =============================================
    
    /**
     * Generate batch packing slips for multiple orders
     * Requirements: 8.2 - Generate multi-page PDF with one slip per page
     * 
     * @param array $orderIds Array of order IDs
     * @return string HTML content for all packing slips
     */
    public function generateBatchPackingSlips(array $orderIds): string {
        if (empty($orderIds)) {
            throw new Exception("No orders provided for batch printing");
        }
        
        $shopName = 'ร้านค้า';
        
        // Get first order's shop name for header
        try {
            $order = $this->getOrderForPrint($orderIds[0]);
            $shopName = $order['shop']['shop_name'] ?? 'ร้านค้า';
        } catch (Exception $e) {
            // Use default shop name
        }
        
        $html = $this->getPackingSlipHeader($shopName);
        
        foreach ($orderIds as $orderId) {
            try {
                $order = $this->getOrderForPrint($orderId);
                $orderShopName = $order['shop']['shop_name'] ?? 'ร้านค้า';
                $orderShopPhone = $order['shop']['contact_phone'] ?? '';
                $orderShopAddress = $order['shop']['address'] ?? '';
                
                $html .= $this->getPackingSlipContent($order, $orderShopName, $orderShopPhone, $orderShopAddress);
                
                // Record print for each order
                $this->recordLabelPrint($orderId, 'packing_slip_batch');
            } catch (Exception $e) {
                // Skip orders that can't be printed, log error
                error_log("Failed to generate packing slip for order {$orderId}: " . $e->getMessage());
                continue;
            }
        }
        
        $html .= $this->getPackingSlipFooter();
        
        return $html;
    }
    
    /**
     * Generate batch shipping labels for multiple orders
     * Requirements: 8.3 - Generate labels in sequence
     * 
     * @param array $orderIds Array of order IDs
     * @return string HTML content for all shipping labels
     */
    public function generateBatchLabels(array $orderIds): string {
        if (empty($orderIds)) {
            throw new Exception("No orders provided for batch printing");
        }
        
        $shopName = 'ร้านค้า';
        
        // Get first order's shop name for header
        try {
            $order = $this->getOrderForPrint($orderIds[0]);
            $shopName = $order['shop']['shop_name'] ?? 'ร้านค้า';
        } catch (Exception $e) {
            // Use default shop name
        }
        
        $html = $this->getShippingLabelHeader($shopName);
        
        foreach ($orderIds as $orderId) {
            try {
                $order = $this->getOrderForPrint($orderId);
                $orderShopName = $order['shop']['shop_name'] ?? 'ร้านค้า';
                $orderShopPhone = $order['shop']['contact_phone'] ?? '';
                $orderShopAddress = $order['shop']['address'] ?? '';
                
                $html .= $this->getShippingLabelContent($order, $orderShopName, $orderShopPhone, $orderShopAddress);
                
                // Record print for each order
                $this->recordLabelPrint($orderId, 'shipping_label_batch');
            } catch (Exception $e) {
                // Skip orders that can't be printed, log error
                error_log("Failed to generate shipping label for order {$orderId}: " . $e->getMessage());
                continue;
            }
        }
        
        $html .= $this->getShippingLabelFooter();
        
        return $html;
    }
    
    /**
     * Get orders ready for label printing
     * Returns orders that are packed but haven't had labels printed
     * 
     * @param array $filters Optional filters
     * @return array List of orders
     */
    public function getOrdersForPrinting(array $filters = []): array {
        $sql = "SELECT t.id, t.order_number, t.shipping_name, t.wms_status, 
                       t.label_printed_at, t.created_at,
                       (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as item_count
                FROM transactions t
                WHERE t.wms_status IN ('packed', 'ready_to_ship')";
        
        $params = [];
        
        if ($this->lineAccountId) {
            $sql .= " AND t.line_account_id = ?";
            $params[] = $this->lineAccountId;
        }
        
        // Filter for unprinted labels only
        if (!empty($filters['unprinted_only'])) {
            $sql .= " AND t.label_printed_at IS NULL";
        }
        
        $sql .= " ORDER BY t.pack_completed_at ASC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark orders as label printed
     * Requirements: 8.4
     * 
     * @param array $orderIds Array of order IDs
     * @return bool Success
     */
    public function markLabelsPrinted(array $orderIds): bool {
        if (empty($orderIds)) {
            return true;
        }
        
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        
        $stmt = $this->db->prepare("
            UPDATE transactions 
            SET label_printed_at = NOW()
            WHERE id IN ({$placeholders})
        ");
        
        return $stmt->execute($orderIds);
    }
    
    /**
     * Check if shipping label contains all required fields
     * Used for validation/testing
     * 
     * @param int $orderId Order ID
     * @return array Validation result with missing fields
     */
    public function validateShippingLabelFields(int $orderId): array {
        $order = $this->getOrderForPrint($orderId);
        
        $requiredFields = [
            'recipient_name' => !empty($order['shipping_name'] ?? $order['customer_name']),
            'recipient_address' => !empty($order['shipping_address']),
            'order_number' => !empty($order['order_number']),
            'sender_name' => !empty($order['shop']['shop_name']),
            'sender_address' => !empty($order['shop']['address']),
        ];
        
        $missingFields = [];
        foreach ($requiredFields as $field => $present) {
            if (!$present) {
                $missingFields[] = $field;
            }
        }
        
        return [
            'valid' => empty($missingFields),
            'missing_fields' => $missingFields,
            'fields_checked' => array_keys($requiredFields)
        ];
    }
}
