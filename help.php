<?php
/**
 * Help Center - คู่มือการใช้งานระบบ
 */
require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'คู่มือการใช้งาน';
require_once 'includes/header.php';
?>

<style>
.help-card {
    transition: all 0.3s ease;
}
.help-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}
.accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}
.accordion-content.open {
    max-height: 2000px;
}
.step-number {
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #06C755 0%, #00a040 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    flex-shrink: 0;
}
.command-box {
    background: #1e293b;
    color: #e2e8f0;
    padding: 12px 16px;
    border-radius: 8px;
    font-family: monospace;
    margin: 8px 0;
}
.tag {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}
.tag-user { background: #dbeafe; color: #2563eb; }
.tag-admin { background: #fef3c7; color: #d97706; }
.tag-bot { background: #dcfce7; color: #16a34a; }
</style>

<!-- Header -->
<div class="bg-gradient-to-r from-green-500 to-green-600 rounded-2xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2">📚 คู่มือการใช้งาน</h1>
            <p class="text-green-100">เรียนรู้วิธีใช้งานระบบ LINE CRM อย่างครบถ้วน</p>
        </div>
        <div class="text-6xl opacity-20">
            <i class="fas fa-book-open"></i>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <a href="#getting-started" class="help-card bg-white rounded-xl p-4 text-center hover:shadow-lg">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-rocket text-blue-500 text-xl"></i>
        </div>
        <div class="font-semibold text-gray-800">เริ่มต้นใช้งาน</div>
    </a>
    <a href="#shop-commands" class="help-card bg-white rounded-xl p-4 text-center hover:shadow-lg">
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-shopping-cart text-green-500 text-xl"></i>
        </div>
        <div class="font-semibold text-gray-800">คำสั่งร้านค้า</div>
    </a>
    <a href="#admin-guide" class="help-card bg-white rounded-xl p-4 text-center hover:shadow-lg">
        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-user-shield text-purple-500 text-xl"></i>
        </div>
        <div class="font-semibold text-gray-800">สำหรับแอดมิน</div>
    </a>
    <a href="#faq" class="help-card bg-white rounded-xl p-4 text-center hover:shadow-lg">
        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-question-circle text-orange-500 text-xl"></i>
        </div>
        <div class="font-semibold text-gray-800">คำถามที่พบบ่อย</div>
    </a>
</div>

<!-- Getting Started -->
<div id="getting-started" class="bg-white rounded-xl shadow mb-6">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold flex items-center">
            <i class="fas fa-rocket text-blue-500 mr-3"></i>
            เริ่มต้นใช้งาน
        </h2>
    </div>
    <div class="p-6">
        <div class="space-y-6">
            <!-- Step 1 -->
            <div class="flex gap-4">
                <div class="step-number">1</div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800 mb-2">ตั้งค่า LINE Official Account</h4>
                    <p class="text-gray-600 text-sm mb-3">เชื่อมต่อ LINE OA กับระบบโดยใส่ Channel Access Token และ Channel Secret</p>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <ol class="text-sm text-gray-600 space-y-2">
                            <li>1. ไปที่ <a href="https://developers.line.biz" target="_blank" class="text-green-600 hover:underline">LINE Developers Console</a></li>
                            <li>2. สร้าง Provider และ Channel (Messaging API)</li>
                            <li>3. คัดลอก Channel Access Token และ Channel Secret</li>
                            <li>4. ไปที่เมนู "LINE Accounts" แล้วเพิ่ม Account ใหม่</li>
                            <li>5. ตั้งค่า Webhook URL: <code class="bg-gray-200 px-2 py-1 rounded"><?= BASE_URL ?>/webhook.php</code></li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="flex gap-4">
                <div class="step-number">2</div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800 mb-2">ตั้งค่าร้านค้า</h4>
                    <p class="text-gray-600 text-sm mb-3">กำหนดข้อมูลร้านค้า ค่าจัดส่ง และช่องทางชำระเงิน</p>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <ol class="text-sm text-gray-600 space-y-2">
                            <li>1. ไปที่เมนู "ตั้งค่าร้านค้า"</li>
                            <li>2. กรอกชื่อร้าน, ข้อความต้อนรับ</li>
                            <li>3. ตั้งค่าค่าจัดส่งและเงื่อนไขส่งฟรี</li>
                            <li>4. เพิ่มบัญชีธนาคาร/พร้อมเพย์</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="flex gap-4">
                <div class="step-number">3</div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800 mb-2">เพิ่มสินค้า</h4>
                    <p class="text-gray-600 text-sm mb-3">เพิ่มสินค้าและหมวดหมู่เพื่อเริ่มขาย</p>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <ol class="text-sm text-gray-600 space-y-2">
                            <li>1. ไปที่เมนู "หมวดหมู่" สร้างหมวดหมู่สินค้า</li>
                            <li>2. ไปที่เมนู "สินค้า" เพิ่มสินค้าใหม่</li>
                            <li>3. อัพโหลดรูปภาพ กำหนดราคา และจำนวนสต็อก</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shop Commands -->
<div id="shop-commands" class="bg-white rounded-xl shadow mb-6">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold flex items-center">
            <i class="fas fa-terminal text-green-500 mr-3"></i>
            คำสั่งสำหรับลูกค้า (พิมพ์ใน LINE)
        </h2>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">คำสั่ง</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">คำอธิบาย</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">ตัวอย่าง</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">สินค้า</code></td>
                        <td class="py-3 px-4 text-gray-600">ดูรายการสินค้าทั้งหมด</td>
                        <td class="py-3 px-4 text-gray-500">สินค้า</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">หมวดหมู่</code></td>
                        <td class="py-3 px-4 text-gray-600">ดูหมวดหมู่สินค้า</td>
                        <td class="py-3 px-4 text-gray-500">หมวดหมู่</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">ตะกร้า</code></td>
                        <td class="py-3 px-4 text-gray-600">ดูสินค้าในตะกร้า</td>
                        <td class="py-3 px-4 text-gray-500">ตะกร้า</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">เพิ่ม [รหัส] [จำนวน]</code></td>
                        <td class="py-3 px-4 text-gray-600">เพิ่มสินค้าลงตะกร้า</td>
                        <td class="py-3 px-4 text-gray-500">เพิ่ม 1 2</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">ลบ [รหัส]</code></td>
                        <td class="py-3 px-4 text-gray-600">ลบสินค้าออกจากตะกร้า</td>
                        <td class="py-3 px-4 text-gray-500">ลบ 1</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">ล้างตะกร้า</code></td>
                        <td class="py-3 px-4 text-gray-600">ล้างสินค้าทั้งหมดในตะกร้า</td>
                        <td class="py-3 px-4 text-gray-500">ล้างตะกร้า</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">สั่งซื้อ</code></td>
                        <td class="py-3 px-4 text-gray-600">ยืนยันคำสั่งซื้อ</td>
                        <td class="py-3 px-4 text-gray-500">สั่งซื้อ</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">ออเดอร์</code></td>
                        <td class="py-3 px-4 text-gray-600">ดูประวัติคำสั่งซื้อ</td>
                        <td class="py-3 px-4 text-gray-500">ออเดอร์</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">ติดต่อ</code></td>
                        <td class="py-3 px-4 text-gray-600">ดูข้อมูลติดต่อร้าน</td>
                        <td class="py-3 px-4 text-gray-500">ติดต่อ</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded">ช่วยเหลือ</code></td>
                        <td class="py-3 px-4 text-gray-600">ดูคำสั่งทั้งหมด</td>
                        <td class="py-3 px-4 text-gray-500">ช่วยเหลือ</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Order Flow -->
<div class="bg-white rounded-xl shadow mb-6">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold flex items-center">
            <i class="fas fa-exchange-alt text-purple-500 mr-3"></i>
            Flow การสั่งซื้อ
        </h2>
    </div>
    <div class="p-6">
        <div class="flex flex-wrap items-center justify-center gap-4 text-center">
            <div class="bg-blue-50 rounded-xl p-4 w-40">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-2 text-white">1</div>
                <div class="font-semibold text-sm">ลูกค้าดูสินค้า</div>
                <div class="text-xs text-gray-500 mt-1">พิมพ์ "สินค้า"</div>
            </div>
            <i class="fas fa-arrow-right text-gray-300"></i>
            <div class="bg-green-50 rounded-xl p-4 w-40">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-2 text-white">2</div>
                <div class="font-semibold text-sm">เพิ่มลงตะกร้า</div>
                <div class="text-xs text-gray-500 mt-1">พิมพ์ "เพิ่ม 1"</div>
            </div>
            <i class="fas fa-arrow-right text-gray-300"></i>
            <div class="bg-yellow-50 rounded-xl p-4 w-40">
                <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-2 text-white">3</div>
                <div class="font-semibold text-sm">ยืนยันสั่งซื้อ</div>
                <div class="text-xs text-gray-500 mt-1">พิมพ์ "สั่งซื้อ"</div>
            </div>
            <i class="fas fa-arrow-right text-gray-300"></i>
            <div class="bg-purple-50 rounded-xl p-4 w-40">
                <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-2 text-white">4</div>
                <div class="font-semibold text-sm">กรอกที่อยู่</div>
                <div class="text-xs text-gray-500 mt-1">ระบบถามที่อยู่</div>
            </div>
            <i class="fas fa-arrow-right text-gray-300"></i>
            <div class="bg-pink-50 rounded-xl p-4 w-40">
                <div class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center mx-auto mb-2 text-white">5</div>
                <div class="font-semibold text-sm">ชำระเงิน</div>
                <div class="text-xs text-gray-500 mt-1">ส่งสลิปโอนเงิน</div>
            </div>
            <i class="fas fa-arrow-right text-gray-300"></i>
            <div class="bg-indigo-50 rounded-xl p-4 w-40">
                <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center mx-auto mb-2 text-white">6</div>
                <div class="font-semibold text-sm">แอดมินยืนยัน</div>
                <div class="text-xs text-gray-500 mt-1">ตรวจสอบ & จัดส่ง</div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Guide -->
<div id="admin-guide" class="bg-white rounded-xl shadow mb-6">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold flex items-center">
            <i class="fas fa-user-shield text-purple-500 mr-3"></i>
            คู่มือสำหรับแอดมิน
        </h2>
    </div>
    <div class="p-6 space-y-4">
        <!-- Accordion Items -->
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold"><i class="fas fa-inbox text-blue-500 mr-2"></i>จัดการข้อความ</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600 space-y-3">
                    <p><strong>หน้าข้อความ:</strong> ดูและตอบข้อความจากลูกค้าแบบ Real-time</p>
                    <ul class="list-disc list-inside space-y-1 ml-4">
                        <li>คลิกที่ชื่อลูกค้าเพื่อเปิดแชท</li>
                        <li>พิมพ์ข้อความและกด Enter หรือคลิกปุ่มส่ง</li>
                        <li>ข้อความใหม่จะแสดงจุดสีแดง</li>
                        <li>สามารถค้นหาลูกค้าได้จากช่องค้นหา</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold"><i class="fas fa-receipt text-green-500 mr-2"></i>จัดการคำสั่งซื้อ</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600 space-y-3">
                    <p><strong>สถานะคำสั่งซื้อ:</strong></p>
                    <ul class="space-y-2 ml-4">
                        <li><span class="px-2 py-1 bg-yellow-100 text-yellow-600 rounded text-xs">รอดำเนินการ</span> - ออเดอร์ใหม่ รอยืนยัน</li>
                        <li><span class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs">ยืนยันแล้ว</span> - ยืนยันออเดอร์แล้ว รอชำระเงิน</li>
                        <li><span class="px-2 py-1 bg-green-100 text-green-600 rounded text-xs">ชำระแล้ว</span> - ได้รับเงินแล้ว รอจัดส่ง</li>
                        <li><span class="px-2 py-1 bg-indigo-100 text-indigo-600 rounded text-xs">กำลังจัดส่ง</span> - จัดส่งแล้ว รอลูกค้ารับ</li>
                        <li><span class="px-2 py-1 bg-emerald-100 text-emerald-600 rounded text-xs">ส่งแล้ว</span> - ลูกค้าได้รับสินค้าแล้ว</li>
                        <li><span class="px-2 py-1 bg-red-100 text-red-600 rounded text-xs">ยกเลิก</span> - ยกเลิกออเดอร์</li>
                    </ul>
                    <p class="mt-3"><strong>การยืนยันการชำระเงิน:</strong></p>
                    <ol class="list-decimal list-inside space-y-1 ml-4">
                        <li>ไปที่หน้า "คำสั่งซื้อ"</li>
                        <li>คลิกดูรายละเอียดออเดอร์</li>
                        <li>ตรวจสอบสลิปการโอนเงิน</li>
                        <li>คลิก "ยืนยัน" เพื่ออนุมัติการชำระเงิน</li>
                        <li>ระบบจะแจ้งลูกค้าอัตโนมัติ</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold"><i class="fas fa-box text-orange-500 mr-2"></i>จัดการสินค้า</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600 space-y-3">
                    <p><strong>เพิ่มสินค้าใหม่:</strong></p>
                    <ol class="list-decimal list-inside space-y-1 ml-4">
                        <li>ไปที่เมนู "สินค้า"</li>
                        <li>คลิกปุ่ม "เพิ่มสินค้า"</li>
                        <li>กรอกข้อมูล: ชื่อ, ราคา, รูปภาพ, หมวดหมู่</li>
                        <li>กำหนดจำนวนสต็อก</li>
                        <li>คลิก "บันทึก"</li>
                    </ol>
                    <p class="mt-3"><strong>Tips:</strong></p>
                    <ul class="list-disc list-inside space-y-1 ml-4">
                        <li>ใช้รูปภาพขนาด 1:1 (สี่เหลี่ยมจัตุรัส) จะแสดงผลสวยที่สุด</li>
                        <li>ตั้งราคาลดพิเศษได้ในช่อง "ราคาลด"</li>
                        <li>ปิดการขายชั่วคราวได้โดยติ๊กออก "เปิดขาย"</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold"><i class="fas fa-bullhorn text-red-500 mr-2"></i>Broadcast ข้อความ</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600 space-y-3">
                    <p><strong>ส่งข้อความหาลูกค้าทุกคน:</strong></p>
                    <ol class="list-decimal list-inside space-y-1 ml-4">
                        <li>ไปที่เมนู "Broadcast"</li>
                        <li>เลือกประเภทข้อความ (ข้อความ/รูปภาพ/Flex)</li>
                        <li>พิมพ์ข้อความที่ต้องการส่ง</li>
                        <li>เลือกกลุ่มเป้าหมาย (ทุกคน/เฉพาะ Tag)</li>
                        <li>คลิก "ส่งทันที" หรือ "ตั้งเวลาส่ง"</li>
                    </ol>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-3">
                        <p class="text-yellow-700"><i class="fas fa-exclamation-triangle mr-2"></i><strong>หมายเหตุ:</strong> การส่ง Broadcast จะใช้โควต้าข้อความของ LINE OA</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold"><i class="fas fa-robot text-cyan-500 mr-2"></i>ตอบกลับอัตโนมัติ</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600 space-y-3">
                    <p><strong>ตั้งค่าข้อความตอบกลับอัตโนมัติ:</strong></p>
                    <ol class="list-decimal list-inside space-y-1 ml-4">
                        <li>ไปที่เมนู "ตอบกลับอัตโนมัติ"</li>
                        <li>คลิก "เพิ่มกฎใหม่"</li>
                        <li>กำหนด Keyword ที่ต้องการจับ</li>
                        <li>เลือกประเภทการจับคู่:
                            <ul class="list-disc list-inside ml-4 mt-1">
                                <li><strong>มีคำนี้</strong> - ข้อความมีคำนี้อยู่</li>
                                <li><strong>ตรงทั้งหมด</strong> - ข้อความต้องตรงเป๊ะ</li>
                                <li><strong>ขึ้นต้นด้วย</strong> - ข้อความขึ้นต้นด้วยคำนี้</li>
                            </ul>
                        </li>
                        <li>พิมพ์ข้อความตอบกลับ</li>
                        <li>คลิก "บันทึก"</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold"><i class="fas fa-tags text-pink-500 mr-2"></i>จัดการ Tags ลูกค้า</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600 space-y-3">
                    <p><strong>ใช้ Tags เพื่อจัดกลุ่มลูกค้า:</strong></p>
                    <ul class="list-disc list-inside space-y-1 ml-4">
                        <li>สร้าง Tags ที่เมนู "Tags ลูกค้า"</li>
                        <li>ติด Tags ให้ลูกค้าที่หน้ารายละเอียดลูกค้า</li>
                        <li>ใช้ Tags เพื่อกรองลูกค้าใน Broadcast</li>
                        <li>ตัวอย่าง Tags: VIP, ลูกค้าใหม่, สนใจโปรโมชั่น</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- FAQ -->
<div id="faq" class="bg-white rounded-xl shadow mb-6">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold flex items-center">
            <i class="fas fa-question-circle text-orange-500 mr-3"></i>
            คำถามที่พบบ่อย (FAQ)
        </h2>
    </div>
    <div class="p-6 space-y-4">
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold">ลูกค้าพิมพ์คำสั่งแล้วบอทไม่ตอบ?</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <p>ตรวจสอบสิ่งต่อไปนี้:</p>
                    <ol class="list-decimal list-inside space-y-1 mt-2 ml-4">
                        <li>Webhook URL ตั้งค่าถูกต้องหรือไม่</li>
                        <li>เปิดใช้งาน Webhook ใน LINE Developers Console แล้วหรือยัง</li>
                        <li>Channel Access Token ยังไม่หมดอายุ</li>
                        <li>ร้านค้าเปิดอยู่ (ไม่ได้ปิดร้าน)</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold">ลูกค้าส่งสลิปแล้วไม่เห็นในระบบ?</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <p>สลิปจะแสดงในหน้ารายละเอียดคำสั่งซื้อ ตรวจสอบ:</p>
                    <ul class="list-disc list-inside space-y-1 mt-2 ml-4">
                        <li>ลูกค้าส่งสลิปหลังจากสั่งซื้อแล้วหรือยัง</li>
                        <li>ลูกค้าส่งเป็นรูปภาพ (ไม่ใช่ไฟล์)</li>
                        <li>ตรวจสอบที่หน้า "คำสั่งซื้อ" > คลิกดูรายละเอียด</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold">จะเปลี่ยน Webhook URL ได้อย่างไร?</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <ol class="list-decimal list-inside space-y-1 ml-4">
                        <li>ไปที่ <a href="https://developers.line.biz" target="_blank" class="text-green-600 hover:underline">LINE Developers Console</a></li>
                        <li>เลือก Provider และ Channel</li>
                        <li>ไปที่แท็บ "Messaging API"</li>
                        <li>แก้ไข Webhook URL เป็น: <code class="bg-gray-100 px-2 py-1 rounded"><?= BASE_URL ?>/webhook.php</code></li>
                        <li>เปิดใช้งาน "Use webhook"</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold">รองรับ LINE OA กี่บัญชี?</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <p>ระบบรองรับหลาย LINE OA (Multi-bot) โดย:</p>
                    <ul class="list-disc list-inside space-y-1 mt-2 ml-4">
                        <li>แต่ละ LINE OA มีสินค้า, ออเดอร์, ลูกค้าแยกกัน</li>
                        <li>สลับ LINE OA ได้จากเมนูด้านบน</li>
                        <li>Admin สามารถจัดการได้ทุก LINE OA</li>
                        <li>User ทั่วไปจะเห็นเฉพาะ LINE OA ที่ตัวเองดูแล</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold">Flex Message คืออะไร?</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <p>Flex Message คือข้อความแบบกำหนดเองที่สวยงาม สามารถ:</p>
                    <ul class="list-disc list-inside space-y-1 mt-2 ml-4">
                        <li>แสดงรูปภาพ, ปุ่มกด, ลิงก์</li>
                        <li>จัดเลย์เอาต์ได้หลากหลาย</li>
                        <li>ใช้แสดงสินค้า, โปรโมชั่น, ใบเสร็จ</li>
                        <li>สร้างได้ที่เมนู "Flex Builder"</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Keyboard Shortcuts -->
<div class="bg-white rounded-xl shadow mb-6">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold flex items-center">
            <i class="fas fa-keyboard text-gray-500 mr-3"></i>
            ปุ่มลัด (Keyboard Shortcuts)
        </h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">ส่งข้อความ</span>
                <kbd class="px-3 py-1 bg-gray-200 rounded text-sm font-mono">Enter</kbd>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">ขึ้นบรรทัดใหม่</span>
                <kbd class="px-3 py-1 bg-gray-200 rounded text-sm font-mono">Shift + Enter</kbd>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">ค้นหา</span>
                <kbd class="px-3 py-1 bg-gray-200 rounded text-sm font-mono">Ctrl + F</kbd>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">บันทึก</span>
                <kbd class="px-3 py-1 bg-gray-200 rounded text-sm font-mono">Ctrl + S</kbd>
            </div>
        </div>
    </div>
</div>

<!-- Contact Support -->
<div class="bg-gradient-to-r from-gray-700 to-gray-800 rounded-xl p-8 text-white text-center">
    <h3 class="text-xl font-bold mb-2">ยังมีคำถาม?</h3>
    <p class="text-gray-300 mb-4">ติดต่อทีมซัพพอร์ตได้ตลอด 24 ชั่วโมง</p>
    <div class="flex justify-center gap-4">
        <a href="#" class="px-6 py-2 bg-green-500 rounded-lg hover:bg-green-600 transition">
            <i class="fab fa-line mr-2"></i>LINE Support
        </a>
        <a href="mailto:support@example.com" class="px-6 py-2 bg-white text-gray-800 rounded-lg hover:bg-gray-100 transition">
            <i class="fas fa-envelope mr-2"></i>Email
        </a>
    </div>
</div>

<script>
function toggleAccordion(button) {
    const content = button.nextElementSibling;
    const icon = button.querySelector('.accordion-icon');
    
    content.classList.toggle('open');
    icon.classList.toggle('rotate-180');
}
</script>

<?php require_once 'includes/footer.php'; ?>
