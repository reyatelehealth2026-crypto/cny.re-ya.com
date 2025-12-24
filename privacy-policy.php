<?php
/**
 * Privacy Policy - นโยบายคุ้มครองข้อมูลส่วนบุคคล
 * ตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Get company info from line_accounts and shop_settings
$companyName = 'ร้านยา';
$companyAddress = '';
$companyPhone = '';
$companyEmail = '';

try {
    // Get from line_accounts first
    $stmt = $db->query("SELECT name FROM line_accounts WHERE is_default = 1 OR is_active = 1 ORDER BY is_default DESC LIMIT 1");
    $lineAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lineAccount && $lineAccount['name']) {
        $companyName = $lineAccount['name'];
    }
    
    // Get additional info from shop_settings
    $stmt = $db->query("SELECT * FROM shop_settings LIMIT 1");
    $shopSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $companyAddress = $shopSettings['shop_address'] ?? '';
    $companyPhone = $shopSettings['contact_phone'] ?? $shopSettings['shop_phone'] ?? '';
    $companyEmail = $shopSettings['shop_email'] ?? '';
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นโยบายคุ้มครองข้อมูลส่วนบุคคล - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .content h2 { font-size: 1.25rem; font-weight: bold; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #1e40af; }
        .content h3 { font-size: 1.1rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
        .content p { margin-bottom: 0.75rem; line-height: 1.8; }
        .content ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 0.75rem; }
        .content li { margin-bottom: 0.25rem; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-blue-600 text-white py-6">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-2xl font-bold">🔒 นโยบายคุ้มครองข้อมูลส่วนบุคคล</h1>
            <p class="text-blue-100 mt-1">Privacy Policy</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 content">
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <strong><?= htmlspecialchars($companyName) ?></strong> ("บริษัท" หรือ "เรา") ให้ความสำคัญกับการคุ้มครองข้อมูลส่วนบุคคลของท่าน 
                    ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) และพระราชบัญญัติการรักษาความมั่นคงปลอดภัยไซเบอร์ พ.ศ. 2562
                </p>
            </div>

            <p><strong>วันที่มีผลบังคับใช้:</strong> <?= date('d/m/Y') ?></p>
            <p><strong>ปรับปรุงล่าสุด:</strong> <?= date('d/m/Y') ?></p>

            <h2>1. ข้อมูลส่วนบุคคลที่เราเก็บรวบรวม</h2>
            <p>เราเก็บรวบรวมข้อมูลส่วนบุคคลของท่านดังต่อไปนี้:</p>
            
            <h3>1.1 ข้อมูลที่ท่านให้โดยตรง</h3>
            <ul>
                <li>ชื่อ-นามสกุล</li>
                <li>หมายเลขโทรศัพท์</li>
                <li>ที่อยู่จัดส่ง</li>
                <li>ข้อมูลบัญชี LINE (LINE User ID, Display Name, รูปโปรไฟล์)</li>
            </ul>

            <h3>1.2 ข้อมูลสุขภาพ (ข้อมูลอ่อนไหว)</h3>
            <ul>
                <li>ประวัติการแพ้ยา</li>
                <li>โรคประจำตัว</li>
                <li>ยาที่ใช้อยู่ปัจจุบัน</li>
                <li>ประวัติการรับยา/การจ่ายยา</li>
            </ul>

            <h3>1.3 ข้อมูลการทำธุรกรรม</h3>
            <ul>
                <li>ประวัติการสั่งซื้อสินค้า</li>
                <li>ข้อมูลการชำระเงิน (สลิปโอนเงิน)</li>
                <li>ประวัติการสนทนา</li>
            </ul>

            <h2>2. วัตถุประสงค์ในการเก็บรวบรวมข้อมูล</h2>
            <p>เราเก็บรวบรวมและใช้ข้อมูลส่วนบุคคลของท่านเพื่อวัตถุประสงค์ดังต่อไปนี้:</p>
            <ul>
                <li><strong>การให้บริการเภสัชกรรมทางไกล:</strong> เพื่อให้คำปรึกษาด้านยาและสุขภาพผ่านระบบออนไลน์</li>
                <li><strong>การจ่ายยา:</strong> เพื่อจัดเตรียมและจัดส่งยาตามใบสั่งยา</li>
                <li><strong>ความปลอดภัยในการใช้ยา:</strong> เพื่อตรวจสอบประวัติแพ้ยาและปฏิกิริยาระหว่างยา</li>
                <li><strong>การติดต่อสื่อสาร:</strong> เพื่อแจ้งข้อมูลเกี่ยวกับคำสั่งซื้อและบริการ</li>
                <li><strong>การปฏิบัติตามกฎหมาย:</strong> เพื่อปฏิบัติตามข้อกำหนดทางกฎหมายที่เกี่ยวข้อง</li>
            </ul>

            <h2>3. การเปิดเผยข้อมูลส่วนบุคคล</h2>
            <p>เราอาจเปิดเผยข้อมูลส่วนบุคคลของท่านให้แก่:</p>
            <ul>
                <li>เภสัชกรผู้ให้บริการ</li>
                <li>บริษัทขนส่งสินค้า (เฉพาะข้อมูลที่จำเป็นสำหรับการจัดส่ง)</li>
                <li>หน่วยงานราชการตามที่กฎหมายกำหนด</li>
            </ul>
            <p>เราจะไม่ขายหรือเปิดเผยข้อมูลส่วนบุคคลของท่านให้แก่บุคคลภายนอกเพื่อวัตถุประสงค์ทางการตลาดโดยไม่ได้รับความยินยอมจากท่าน</p>

            <h2>4. ระยะเวลาในการเก็บรักษาข้อมูล</h2>
            <p>เราจะเก็บรักษาข้อมูลส่วนบุคคลของท่านตามระยะเวลาดังนี้:</p>
            <ul>
                <li><strong>ข้อมูลสุขภาพและประวัติการจ่ายยา:</strong> 5 ปี นับจากวันที่ให้บริการครั้งสุดท้าย (ตามข้อกำหนดสภาเภสัชกรรม)</li>
                <li><strong>ข้อมูลการทำธุรกรรม:</strong> 7 ปี (ตามกฎหมายภาษีอากร)</li>
                <li><strong>ข้อมูลทั่วไป:</strong> ตลอดระยะเวลาที่ท่านยังใช้บริการ</li>
            </ul>

            <h2>5. สิทธิของเจ้าของข้อมูล</h2>
            <p>ท่านมีสิทธิตามกฎหมายดังต่อไปนี้:</p>
            <ul>
                <li><strong>สิทธิในการเข้าถึง:</strong> ขอรับสำเนาข้อมูลส่วนบุคคลของท่าน</li>
                <li><strong>สิทธิในการแก้ไข:</strong> ขอแก้ไขข้อมูลที่ไม่ถูกต้องหรือไม่สมบูรณ์</li>
                <li><strong>สิทธิในการลบ:</strong> ขอให้ลบข้อมูลส่วนบุคคล (ภายใต้เงื่อนไขที่กฎหมายกำหนด)</li>
                <li><strong>สิทธิในการคัดค้าน:</strong> คัดค้านการประมวลผลข้อมูลส่วนบุคคล</li>
                <li><strong>สิทธิในการถอนความยินยอม:</strong> ถอนความยินยอมที่เคยให้ไว้</li>
                <li><strong>สิทธิในการโอนย้ายข้อมูล:</strong> ขอรับข้อมูลในรูปแบบที่สามารถอ่านได้</li>
            </ul>

            <h2>6. มาตรการรักษาความปลอดภัย</h2>
            <p>เราใช้มาตรการรักษาความปลอดภัยดังต่อไปนี้:</p>
            <ul>
                <li>การเข้ารหัสข้อมูลด้วย SSL/TLS ในการรับส่งข้อมูล</li>
                <li>การจำกัดสิทธิ์การเข้าถึงข้อมูลเฉพาะผู้ที่เกี่ยวข้อง</li>
                <li>การสำรองข้อมูลอย่างสม่ำเสมอ</li>
                <li>การตรวจสอบและบันทึกการเข้าถึงข้อมูล (Audit Log)</li>
            </ul>

            <h2>7. การติดต่อเรา</h2>
            <p>หากท่านมีคำถามเกี่ยวกับนโยบายนี้ หรือต้องการใช้สิทธิของท่าน กรุณาติดต่อ:</p>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p><strong><?= htmlspecialchars($companyName) ?></strong></p>
                <?php if ($companyAddress): ?>
                <p>📍 <?= htmlspecialchars($companyAddress) ?></p>
                <?php endif; ?>
                <?php if ($companyPhone): ?>
                <p>📞 <?= htmlspecialchars($companyPhone) ?></p>
                <?php endif; ?>
                <?php if ($companyEmail): ?>
                <p>📧 <?= htmlspecialchars($companyEmail) ?></p>
                <?php endif; ?>
            </div>

            <h2>8. การเปลี่ยนแปลงนโยบาย</h2>
            <p>เราอาจปรับปรุงนโยบายนี้เป็นครั้งคราว โดยจะแจ้งให้ท่านทราบผ่านช่องทางที่เหมาะสม การใช้บริการต่อหลังจากมีการเปลี่ยนแปลงถือว่าท่านยอมรับนโยบายที่ปรับปรุงแล้ว</p>

        </div>

        <!-- Back Button -->
        <div class="mt-6 text-center">
            <a href="javascript:history.back()" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                ← กลับ
            </a>
        </div>
    </div>
</body>
</html>
