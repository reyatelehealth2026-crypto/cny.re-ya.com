<?php
/**
 * User Help Center - คู่มือการใช้งานสำหรับ User
 */
$pageTitle = 'คู่มือการใช้งาน';
require_once '../includes/user_header.php';
?>

<style>
.help-card { transition: all 0.3s ease; }
.help-card:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
.accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
.accordion-content.open { max-height: 2000px; }
.step-number { width: 28px; height: 28px; background: linear-gradient(135deg, #06C755 0%, #00a040 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; flex-shrink: 0; }
</style>

<!-- Header -->
<div class="bg-gradient-to-r from-green-500 to-green-600 rounded-2xl p-6 mb-6 text-white">
    <h1 class="text-2xl font-bold mb-2">📚 คู่มือการใช้งาน</h1>
    <p class="text-green-100">เรียนรู้วิธีจัดการร้านค้าและลูกค้าของคุณ</p>
</div>

<!-- Quick Links -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <a href="#orders" class="help-card bg-white rounded-xl p-4 text-center">
        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-2">
            <i class="fas fa-receipt text-green-500"></i>
        </div>
        <div class="font-semibold text-sm text-gray-800">จัดการออเดอร์</div>
    </a>
    <a href="#products" class="help-card bg-white rounded-xl p-4 text-center">
        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-2">
            <i class="fas fa-box text-blue-500"></i>
        </div>
        <div class="font-semibold text-sm text-gray-800">จัดการสินค้า</div>
    </a>
    <a href="#messages" class="help-card bg-white rounded-xl p-4 text-center">
        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-2">
            <i class="fas fa-comments text-purple-500"></i>
        </div>
        <div class="font-semibold text-sm text-gray-800">ข้อความ</div>
    </a>
    <a href="#commands" class="help-card bg-white rounded-xl p-4 text-center">
        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-2">
            <i class="fas fa-terminal text-orange-500"></i>
        </div>
        <div class="font-semibold text-sm text-gray-800">คำสั่งบอท</div>
    </a>
</div>

<!-- Order Management -->
<div id="orders" class="bg-white rounded-xl shadow mb-6">
    <div class="p-4 border-b">
        <h2 class="text-lg font-bold flex items-center">
            <i class="fas fa-receipt text-green-500 mr-2"></i>
            การจัดการคำสั่งซื้อ
        </h2>
    </div>
    <div class="p-4 space-y-4">
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold text-sm">สถานะคำสั่งซื้อมีอะไรบ้าง?</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon text-sm"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600 space-y-2">
                    <div class="flex items-center gap-2"><span class="px-2 py-1 bg-yellow-100 text-yellow-600 rounded text-xs">รอดำเนินการ</span> ออเดอร์ใหม่ รอยืนยัน</div>
                    <div class="flex items-center gap-2"><span class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs">ยืนยันแล้ว</span> รอลูกค้าชำระเงิน</div>
                    <div class="flex items-center gap-2"><span class="px-2 py-1 bg-green-100 text-green-600 rounded text-xs">ชำระแล้ว</span> ได้รับเงินแล้ว รอจัดส่ง</div>
                    <div class="flex items-center gap-2"><span class="px-2 py-1 bg-indigo-100 text-indigo-600 rounded text-xs">กำลังจัดส่ง</span> จัดส่งแล้ว</div>
                    <div class="flex items-center gap-2"><span class="px-2 py-1 bg-emerald-100 text-emerald-600 rounded text-xs">ส่งแล้ว</span> ลูกค้าได้รับสินค้า</div>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold text-sm">วิธียืนยันการชำระเงิน</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon text-sm"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <ol class="list-decimal list-inside space-y-1">
                        <li>ไปที่เมนู "คำสั่งซื้อ"</li>
                        <li>คลิกไอคอน <i class="fas fa-eye text-blue-500"></i> เพื่อดูรายละเอียด</li>
                        <li>ตรวจสอบสลิปการโอนเงินในส่วน "หลักฐานการชำระเงิน"</li>
                        <li>คลิกปุ่ม "ยืนยัน" เพื่ออนุมัติ</li>
                        <li>ระบบจะแจ้งลูกค้าทาง LINE อัตโนมัติ</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold text-sm">วิธีอัพเดทเลขพัสดุ</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon text-sm"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <ol class="list-decimal list-inside space-y-1">
                        <li>เปิดรายละเอียดคำสั่งซื้อ</li>
                        <li>เปลี่ยนสถานะเป็น "กำลังจัดส่ง"</li>
                        <li>กรอกเลขพัสดุในช่อง "เลขพัสดุ"</li>
                        <li>คลิก "บันทึก"</li>
                        <li>ลูกค้าจะได้รับแจ้งเลขพัสดุทาง LINE</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Management -->
<div id="products" class="bg-white rounded-xl shadow mb-6">
    <div class="p-4 border-b">
        <h2 class="text-lg font-bold flex items-center">
            <i class="fas fa-box text-blue-500 mr-2"></i>
            การจัดการสินค้า
        </h2>
    </div>
    <div class="p-4 space-y-4">
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold text-sm">วิธีเพิ่มสินค้าใหม่</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon text-sm"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <ol class="list-decimal list-inside space-y-1">
                        <li>ไปที่เมนู "สินค้า"</li>
                        <li>คลิกปุ่ม "เพิ่มสินค้า"</li>
                        <li>อัพโหลดรูปภาพสินค้า (แนะนำขนาด 1:1)</li>
                        <li>กรอกชื่อสินค้า, ราคา, จำนวนสต็อก</li>
                        <li>เลือกหมวดหมู่ (ถ้ามี)</li>
                        <li>คลิก "บันทึก"</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="border rounded-lg">
            <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                <span class="font-semibold text-sm">วิธีตั้งราคาลดพิเศษ</span>
                <i class="fas fa-chevron-down text-gray-400 accordion-icon text-sm"></i>
            </button>
            <div class="accordion-content">
                <div class="p-4 pt-0 text-sm text-gray-600">
                    <ol class="list-decimal list-inside space-y-1">
                        <li>แก้ไขสินค้าที่ต้องการ</li>
                        <li>กรอกราคาปกติในช่อง "ราคาปกติ"</li>
                        <li>กรอกราคาลดในช่อง "ราคาลด"</li>
                        <li>สินค้าจะแสดงป้าย "SALE" และราคาขีดฆ่า</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Messages -->
<div id="messages" class="bg-white rounded-xl shadow mb-6">
    <div class="p-4 border-b">
        <h2 class="text-lg font-bold flex items-center">
            <i class="fas fa-comments text-purple-500 mr-2"></i>
            การจัดการข้อความ
        </h2>
    </div>
    <div class="p-4 text-sm text-gray-600 space-y-3">
        <p><strong>หน้าข้อความ</strong> ใช้สำหรับดูและตอบข้อความจากลูกค้า:</p>
        <ul class="list-disc list-inside space-y-1 ml-4">
            <li>คลิกที่ชื่อลูกค้าเพื่อเปิดแชท</li>
            <li>พิมพ์ข้อความและกด Enter เพื่อส่ง</li>
            <li>ข้อความใหม่จะแสดงจุดสีแดง</li>
            <li>ข้อความจะส่งไปยัง LINE ของลูกค้าโดยตรง</li>
        </ul>
    </div>
</div>

<!-- Bot Commands -->
<div id="commands" class="bg-white rounded-xl shadow mb-6">
    <div class="p-4 border-b">
        <h2 class="text-lg font-bold flex items-center">
            <i class="fas fa-terminal text-orange-500 mr-2"></i>
            คำสั่งที่ลูกค้าใช้ได้ใน LINE
        </h2>
    </div>
    <div class="p-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2 px-3 font-semibold">คำสั่ง</th>
                        <th class="text-left py-2 px-3 font-semibold">คำอธิบาย</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr><td class="py-2 px-3"><code class="bg-gray-100 px-2 py-1 rounded text-xs">สินค้า</code></td><td class="py-2 px-3 text-gray-600">ดูรายการสินค้า</td></tr>
                    <tr><td class="py-2 px-3"><code class="bg-gray-100 px-2 py-1 rounded text-xs">หมวดหมู่</code></td><td class="py-2 px-3 text-gray-600">ดูหมวดหมู่สินค้า</td></tr>
                    <tr><td class="py-2 px-3"><code class="bg-gray-100 px-2 py-1 rounded text-xs">ตะกร้า</code></td><td class="py-2 px-3 text-gray-600">ดูสินค้าในตะกร้า</td></tr>
                    <tr><td class="py-2 px-3"><code class="bg-gray-100 px-2 py-1 rounded text-xs">เพิ่ม [รหัส]</code></td><td class="py-2 px-3 text-gray-600">เพิ่มสินค้าลงตะกร้า</td></tr>
                    <tr><td class="py-2 px-3"><code class="bg-gray-100 px-2 py-1 rounded text-xs">ลบ [รหัส]</code></td><td class="py-2 px-3 text-gray-600">ลบสินค้าออกจากตะกร้า</td></tr>
                    <tr><td class="py-2 px-3"><code class="bg-gray-100 px-2 py-1 rounded text-xs">สั่งซื้อ</code></td><td class="py-2 px-3 text-gray-600">ยืนยันคำสั่งซื้อ</td></tr>
                    <tr><td class="py-2 px-3"><code class="bg-gray-100 px-2 py-1 rounded text-xs">ออเดอร์</code></td><td class="py-2 px-3 text-gray-600">ดูประวัติคำสั่งซื้อ</td></tr>
                    <tr><td class="py-2 px-3"><code class="bg-gray-100 px-2 py-1 rounded text-xs">ช่วยเหลือ</code></td><td class="py-2 px-3 text-gray-600">ดูคำสั่งทั้งหมด</td></tr>
                </tbody>
            </table>
        </div>
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

<?php require_once '../includes/user_footer.php'; ?>
