<?php
/**
 * LIFF Consent Page
 * หน้ายินยอมข้อตกลงและนโยบายความเป็นส่วนตัว
 * แสดงก่อนใช้งานครั้งแรก
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Get company info from line_accounts and shop_settings
$companyName = 'ร้านยา';
$privacyVersion = '1.0';
$termsVersion = '1.0';

try {
    // Get from line_accounts first
    $stmt = $db->query("SELECT name FROM line_accounts WHERE is_default = 1 OR is_active = 1 ORDER BY is_default DESC LIMIT 1");
    $lineAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lineAccount && $lineAccount['name']) {
        $companyName = $lineAccount['name'];
    }
    
    // Get versions from shop_settings
    $stmt = $db->query("SELECT privacy_policy_version, terms_version FROM shop_settings LIMIT 1");
    $shopSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $privacyVersion = $shopSettings['privacy_policy_version'] ?? '1.0';
    $termsVersion = $shopSettings['terms_version'] ?? '1.0';
} catch (Exception $e) {}

// Get LIFF ID - ตรวจสอบว่ามี liff_consent_id หรือไม่
$liffId = '';
$lineAccountId = $_GET['account'] ?? null;
try {
    // ตรวจสอบว่ามี column liff_consent_id หรือไม่
    $hasConsentCol = false;
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'liff_consent_id'");
        $hasConsentCol = $checkCol->rowCount() > 0;
    } catch (Exception $e) {}
    
    if ($lineAccountId) {
        if ($hasConsentCol) {
            $stmt = $db->prepare("SELECT liff_id, liff_consent_id FROM line_accounts WHERE id = ? AND is_active = 1");
        } else {
            $stmt = $db->prepare("SELECT liff_id, NULL as liff_consent_id FROM line_accounts WHERE id = ? AND is_active = 1");
        }
        $stmt->execute([$lineAccountId]);
    } else {
        if ($hasConsentCol) {
            $stmt = $db->query("SELECT liff_id, liff_consent_id FROM line_accounts WHERE is_active = 1 ORDER BY is_default DESC LIMIT 1");
        } else {
            $stmt = $db->query("SELECT liff_id, NULL as liff_consent_id FROM line_accounts WHERE is_active = 1 ORDER BY is_default DESC LIMIT 1");
        }
    }
    $row = $stmt->fetch();
    // ใช้ liff_consent_id ถ้ามี หรือใช้ liff_id ปกติ
    $liffId = $row['liff_consent_id'] ?? $row['liff_id'] ?? '';
} catch (Exception $e) {}

// Redirect URL after consent - ถ้าเปิดจาก LINE Bot ให้ปิด LIFF
$redirectUrl = $_GET['redirect'] ?? '';
$closeAfterConsent = empty($redirectUrl); // ถ้าไม่มี redirect ให้ปิด LIFF
$baseUrl = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ยินยอมข้อตกลง - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 text-center">
        <div class="text-4xl mb-2">🔒</div>
        <h1 class="text-xl font-bold"><?= htmlspecialchars($companyName) ?></h1>
        <p class="text-blue-100 text-sm mt-1">ข้อตกลงและความยินยอม</p>
    </div>

    <div class="p-4 max-w-lg mx-auto">
        <!-- Welcome Message -->
        <div class="bg-white rounded-xl shadow p-4 mb-4">
            <h2 class="font-bold text-lg mb-2">ยินดีต้อนรับ! 👋</h2>
            <p class="text-gray-600 text-sm">
                ก่อนเริ่มใช้บริการ กรุณาอ่านและยอมรับข้อตกลงการใช้งานและนโยบายความเป็นส่วนตัวของเรา
            </p>
        </div>

        <!-- Consent Items -->
        <div class="space-y-3 mb-6">
            <!-- Terms of Service -->
            <div class="bg-white rounded-xl shadow p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="consentTerms" class="w-5 h-5 mt-1 text-blue-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium">📋 ข้อตกลงการใช้งาน</p>
                        <p class="text-sm text-gray-500 mt-1">
                            ฉันได้อ่านและยอมรับ <a href="terms-of-service.php" target="_blank" class="text-blue-600 underline">ข้อตกลงการใช้งาน</a> 
                            ของบริการเภสัชกรรมทางไกล
                        </p>
                        <p class="text-xs text-gray-400 mt-1">เวอร์ชัน <?= $termsVersion ?></p>
                    </div>
                </label>
            </div>

            <!-- Privacy Policy -->
            <div class="bg-white rounded-xl shadow p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="consentPrivacy" class="w-5 h-5 mt-1 text-blue-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium">🔒 นโยบายความเป็นส่วนตัว</p>
                        <p class="text-sm text-gray-500 mt-1">
                            ฉันได้อ่านและยอมรับ <a href="privacy-policy.php" target="_blank" class="text-blue-600 underline">นโยบายคุ้มครองข้อมูลส่วนบุคคล</a> 
                            ตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562
                        </p>
                        <p class="text-xs text-gray-400 mt-1">เวอร์ชัน <?= $privacyVersion ?></p>
                    </div>
                </label>
            </div>

            <!-- Health Data Consent -->
            <div class="bg-white rounded-xl shadow p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="consentHealth" class="w-5 h-5 mt-1 text-blue-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium">💊 ข้อมูลสุขภาพ</p>
                        <p class="text-sm text-gray-500 mt-1">
                            ฉันยินยอมให้เก็บรวบรวมและใช้ข้อมูลสุขภาพของฉัน (ประวัติแพ้ยา, โรคประจำตัว, ยาที่ใช้อยู่) 
                            เพื่อความปลอดภัยในการใช้ยา
                        </p>
                        <p class="text-xs text-gray-400 mt-1">ข้อมูลอ่อนไหวตาม PDPA มาตรา 26</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Accept All -->
        <div class="mb-4">
            <label class="flex items-center gap-3 cursor-pointer bg-blue-50 p-3 rounded-lg border-2 border-blue-200">
                <input type="checkbox" id="acceptAll" class="w-5 h-5 text-blue-600 rounded">
                <span class="font-medium text-blue-800">✅ ยอมรับทั้งหมด</span>
            </label>
        </div>

        <!-- Submit Button -->
        <button onclick="submitConsent()" id="btnSubmit" disabled
            class="w-full py-4 bg-gray-300 text-gray-500 rounded-xl font-bold text-lg transition-colors disabled:cursor-not-allowed">
            ยืนยันและเริ่มใช้งาน
        </button>

        <!-- Note -->
        <p class="text-xs text-gray-400 text-center mt-4">
            ท่านสามารถถอนความยินยอมได้ทุกเมื่อโดยติดต่อเรา<br>
            การถอนความยินยอมอาจส่งผลต่อการใช้บริการบางส่วน
        </p>
    </div>

    <script>
    let userId = null;
    const redirectUrl = '<?= htmlspecialchars($redirectUrl) ?>';
    const liffId = '<?= $liffId ?>';
    const closeAfterConsent = <?= $closeAfterConsent ? 'true' : 'false' ?>;

    // Initialize LIFF
    document.addEventListener('DOMContentLoaded', async function() {
        if (liffId) {
            try {
                await liff.init({ liffId: liffId });
                if (liff.isLoggedIn()) {
                    const profile = await liff.getProfile();
                    userId = profile.userId;
                    
                    // Check if already consented
                    checkExistingConsent();
                } else {
                    // Support external browser - redirect to LINE Login
                    liff.login();
                }
            } catch (e) {
                console.error('LIFF init error:', e);
            }
        }
    });

    // Check existing consent
    async function checkExistingConsent() {
        if (!userId) return;
        
        try {
            const response = await fetch(`api/consent.php?action=check&line_user_id=${userId}`);
            const data = await response.json();
            
            if (data.success && data.all_consented) {
                // Already consented, redirect
                window.location.href = redirectUrl;
            }
        } catch (e) {
            console.error('Check consent error:', e);
        }
    }

    // Checkbox handlers
    const checkboxes = ['consentTerms', 'consentPrivacy', 'consentHealth'];
    const acceptAll = document.getElementById('acceptAll');
    const btnSubmit = document.getElementById('btnSubmit');

    checkboxes.forEach(id => {
        document.getElementById(id).addEventListener('change', updateButtonState);
    });

    acceptAll.addEventListener('change', function() {
        checkboxes.forEach(id => {
            document.getElementById(id).checked = this.checked;
        });
        updateButtonState();
    });

    function updateButtonState() {
        const allChecked = checkboxes.every(id => document.getElementById(id).checked);
        acceptAll.checked = allChecked;
        
        // At minimum, terms and privacy must be accepted
        const termsChecked = document.getElementById('consentTerms').checked;
        const privacyChecked = document.getElementById('consentPrivacy').checked;
        
        if (termsChecked && privacyChecked) {
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('bg-gray-300', 'text-gray-500');
            btnSubmit.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        } else {
            btnSubmit.disabled = true;
            btnSubmit.classList.add('bg-gray-300', 'text-gray-500');
            btnSubmit.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        }
    }

    // Submit consent
    async function submitConsent() {
        if (!userId) {
            // Try to get from URL or prompt
            const urlParams = new URLSearchParams(window.location.search);
            userId = urlParams.get('user');
            
            if (!userId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาเปิดผ่าน LINE',
                    text: 'ต้องเปิดผ่านแอป LINE เพื่อยืนยันตัวตน',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
        }

        const consents = {
            terms_of_service: document.getElementById('consentTerms').checked,
            privacy_policy: document.getElementById('consentPrivacy').checked,
            health_data: document.getElementById('consentHealth').checked
        };

        try {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="animate-pulse">กำลังบันทึก...</span>';

            const response = await fetch('api/consent.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save',
                    line_user_id: userId,
                    consents: consents
                })
            });

            const data = await response.json();

            if (data.success) {
                // ตรวจสอบว่าสมัครสมาชิกแล้วหรือยัง
                const memberCheck = await fetch(`api/member.php?action=check&line_user_id=${userId}&line_account_id=<?= (int)$lineAccountId ?>`);
                const memberData = await memberCheck.json();
                
                if (memberData.success && memberData.is_registered) {
                    // สมัครแล้ว - ไปหน้าบัตรสมาชิกหรือปิด LIFF
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ!',
                        text: 'ขอบคุณที่ยอมรับข้อตกลง',
                        confirmButtonColor: '#2563eb',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        } else if (closeAfterConsent && liff.isInClient()) {
                            liff.closeWindow();
                        } else {
                            window.location.href = '<?= $baseUrl ?>/liff-member-card.php?account=<?= (int)$lineAccountId ?>';
                        }
                    });
                } else {
                    // ยังไม่สมัคร - ไปหน้าสมัครสมาชิก
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ!',
                        html: '<p class="text-gray-600">ขอบคุณที่ยอมรับข้อตกลง</p><p class="text-sm text-blue-600 mt-2">กรุณากรอกข้อมูลสมัครสมาชิก</p>',
                        confirmButtonColor: '#2563eb',
                        confirmButtonText: 'สมัครสมาชิก',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = '<?= $baseUrl ?>/liff-register.php?account=<?= (int)$lineAccountId ?>';
                    });
                }
            } else {
                throw new Error(data.message || 'เกิดข้อผิดพลาด');
            }
        } catch (e) {
            console.error('Submit error:', e);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: e.message,
                confirmButtonColor: '#2563eb'
            });
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = 'ยืนยันและเริ่มใช้งาน';
        }
    }
    </script>
</body>
</html>
