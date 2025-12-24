<?php
/**
 * Terms of Service - ข้อตกลงการใช้งาน
 * สำหรับบริการเภสัชกรรมทางไกล (Telepharmacy)
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Get company info from line_accounts and shop_settings
$companyName = 'ร้านยา';
$companyAddress = '';
$pharmacyLicense = '';

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
    $pharmacyLicense = $shopSettings['pharmacy_license'] ?? '';
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อตกลงการใช้งาน - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .content h2 { font-size: 1.25rem; font-weight: bold; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #047857; }
        .content h3 { font-size: 1.1rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
        .content p { margin-bottom: 0.75rem; line-height: 1.8; }
        .content ul, .content ol { margin-left: 1.5rem; margin-bottom: 0.75rem; }
        .content li { margin-bottom: 0.25rem; }
        .content ul { list-style-type: disc; }
        .content ol { list-style-type: decimal; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-green-600 text-white py-6">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-2xl font-bold">📋 ข้อตกลงการใช้งาน</h1>
            <p class="text-green-100 mt-1">Terms of Service - บริการเภสัชกรรมทางไกล</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 content">
            
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <p class="text-sm text-green-800">
                    กรุณาอ่านข้อตกลงการใช้งานนี้อย่างละเอียดก่อนใช้บริการ การใช้บริการของเราถือว่าท่านยอมรับข้อตกลงทั้งหมดนี้
                </p>
            </div>

            <p><strong>วันที่มีผลบังคับใช้:</strong> <?= date('d/m/Y') ?></p>

            <h2>1. คำนิยาม</h2>
            <ul>
                <li><strong>"บริการ"</strong> หมายถึง บริการเภสัชกรรมทางไกล (Telepharmacy) ผ่านแอปพลิเคชัน LINE</li>
                <li><strong>"ผู้ให้บริการ"</strong> หมายถึง <?= htmlspecialchars($companyName) ?> และเภสัชกรผู้ประกอบวิชาชีพ</li>
                <li><strong>"ผู้ใช้บริการ"</strong> หมายถึง บุคคลที่ใช้บริการผ่านระบบ</li>
                <li><strong>"เภสัชกร"</strong> หมายถึง ผู้ประกอบวิชาชีพเภสัชกรรมที่ได้รับใบอนุญาตจากสภาเภสัชกรรม</li>
            </ul>

            <h2>2. ขอบเขตการให้บริการ</h2>
            <p>บริการเภสัชกรรมทางไกลของเราประกอบด้วย:</p>
            <ol>
                <li>การให้คำปรึกษาด้านยาและสุขภาพผ่าน Video Call หรือ Chat</li>
                <li>การจ่ายยาตามใบสั่งยาหรือยาที่ไม่ต้องมีใบสั่งยา</li>
                <li>การติดตามผลการใช้ยา</li>
                <li>การให้ข้อมูลเกี่ยวกับยาและผลิตภัณฑ์สุขภาพ</li>
            </ol>

            <h2>3. คุณสมบัติผู้ใช้บริการ</h2>
            <ul>
                <li>ต้องมีอายุ 18 ปีบริบูรณ์ขึ้นไป หรือได้รับความยินยอมจากผู้ปกครอง</li>
                <li>ต้องให้ข้อมูลที่ถูกต้องและเป็นจริง</li>
                <li>ต้องมีบัญชี LINE ที่ใช้งานได้</li>
            </ul>

            <h2>4. ข้อกำหนดการใช้บริการ</h2>
            
            <h3>4.1 ข้อมูลสุขภาพ</h3>
            <p>ผู้ใช้บริการตกลงที่จะ:</p>
            <ul>
                <li>ให้ข้อมูลประวัติสุขภาพ ประวัติแพ้ยา และยาที่ใช้อยู่อย่างถูกต้องครบถ้วน</li>
                <li>แจ้งอาการผิดปกติหรือผลข้างเคียงจากการใช้ยาทันที</li>
                <li>ปฏิบัติตามคำแนะนำของเภสัชกรอย่างเคร่งครัด</li>
            </ul>

            <h3>4.2 การสั่งซื้อและชำระเงิน</h3>
            <ul>
                <li>ราคาสินค้าเป็นไปตามที่แสดงในระบบ ณ เวลาที่สั่งซื้อ</li>
                <li>การชำระเงินต้องดำเนินการภายในเวลาที่กำหนด</li>
                <li>การยกเลิกคำสั่งซื้อต้องแจ้งก่อนการจัดส่ง</li>
            </ul>

            <h3>4.3 การจัดส่ง</h3>
            <ul>
                <li>ยาจะถูกจัดส่งตามที่อยู่ที่ระบุ</li>
                <li>ผู้รับต้องเป็นผู้ใช้บริการหรือผู้ที่ได้รับมอบหมาย</li>
                <li>ยาบางประเภทอาจต้องเก็บรักษาในอุณหภูมิที่เหมาะสม</li>
            </ul>

            <h2>5. ข้อจำกัดการให้บริการ</h2>
            <p>บริการเภสัชกรรมทางไกล <strong>ไม่เหมาะสำหรับ</strong>:</p>
            <ul>
                <li>กรณีฉุกเฉินที่ต้องการการรักษาทันที</li>
                <li>การวินิจฉัยโรค (ต้องพบแพทย์)</li>
                <li>ยาควบคุมพิเศษหรือวัตถุออกฤทธิ์ต่อจิตและประสาท</li>
                <li>ยาที่ต้องมีใบสั่งยาจากแพทย์ (ยกเว้นมีใบสั่งยาที่ถูกต้อง)</li>
            </ul>

            <h2>6. ความรับผิดชอบของผู้ให้บริการ</h2>
            <ul>
                <li>ให้บริการโดยเภสัชกรที่มีใบอนุญาตประกอบวิชาชีพ</li>
                <li>จ่ายยาที่มีคุณภาพและได้รับการขึ้นทะเบียนถูกต้อง</li>
                <li>รักษาความลับข้อมูลสุขภาพของผู้ใช้บริการ</li>
                <li>ให้ข้อมูลยาที่ถูกต้องและครบถ้วน</li>
            </ul>

            <h2>7. ข้อจำกัดความรับผิด</h2>
            <p>ผู้ให้บริการไม่รับผิดชอบต่อ:</p>
            <ul>
                <li>ความเสียหายจากการให้ข้อมูลที่ไม่ถูกต้องโดยผู้ใช้บริการ</li>
                <li>ผลข้างเคียงจากการใช้ยาที่ไม่ปฏิบัติตามคำแนะนำ</li>
                <li>ความล่าช้าในการจัดส่งที่เกิดจากเหตุสุดวิสัย</li>
                <li>ปัญหาทางเทคนิคของระบบ LINE หรืออินเทอร์เน็ต</li>
            </ul>

            <h2>8. ทรัพย์สินทางปัญญา</h2>
            <p>เนื้อหา รูปภาพ และข้อมูลทั้งหมดในระบบเป็นทรัพย์สินของผู้ให้บริการ ห้ามทำซ้ำ ดัดแปลง หรือเผยแพร่โดยไม่ได้รับอนุญาต</p>

            <h2>9. การระงับหรือยกเลิกบริการ</h2>
            <p>ผู้ให้บริการสงวนสิทธิ์ในการระงับหรือยกเลิกการให้บริการหาก:</p>
            <ul>
                <li>ผู้ใช้บริการให้ข้อมูลเท็จ</li>
                <li>มีพฤติกรรมไม่เหมาะสมหรือผิดกฎหมาย</li>
                <li>ละเมิดข้อตกลงการใช้งานนี้</li>
            </ul>

            <h2>10. กฎหมายที่ใช้บังคับ</h2>
            <p>ข้อตกลงนี้อยู่ภายใต้กฎหมายไทย รวมถึง:</p>
            <ul>
                <li>พระราชบัญญัติยา พ.ศ. 2510 และที่แก้ไขเพิ่มเติม</li>
                <li>พระราชบัญญัติวิชาชีพเภสัชกรรม พ.ศ. 2537</li>
                <li>พระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562</li>
                <li>ประกาศสภาเภสัชกรรมเรื่องการให้บริการเภสัชกรรมทางไกล</li>
            </ul>

            <h2>11. ข้อมูลผู้ให้บริการ</h2>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p><strong><?= htmlspecialchars($companyName) ?></strong></p>
                <?php if ($companyAddress): ?>
                <p>📍 <?= htmlspecialchars($companyAddress) ?></p>
                <?php endif; ?>
                <?php if ($pharmacyLicense): ?>
                <p>🏥 เลขที่ใบอนุญาต: <?= htmlspecialchars($pharmacyLicense) ?></p>
                <?php endif; ?>
            </div>

            <h2>12. การติดต่อ</h2>
            <p>หากมีข้อสงสัยเกี่ยวกับข้อตกลงนี้ กรุณาติดต่อเราผ่านช่องทาง LINE Official Account</p>

        </div>

        <!-- Back Button -->
        <div class="mt-6 text-center">
            <a href="javascript:history.back()" class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                ← กลับ
            </a>
        </div>
    </div>
</body>
</html>
