<?php
/**
 * LIFF Register - หน้าสมัครสมาชิก
 * UI/UX ปรับปรุงใหม่ - สวยงาม ใช้งานง่าย
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Get company info
$companyName = 'MedCare';
$companyLogo = '';
$lineAccountId = $_GET['account'] ?? 1;
$isEditMode = isset($_GET['edit']);

try {
    $stmt = $db->prepare("SELECT name FROM line_accounts WHERE id = ? OR is_default = 1 ORDER BY is_default DESC LIMIT 1");
    $stmt->execute([$lineAccountId]);
    $lineAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lineAccount) {
        $companyName = $lineAccount['name'];
    }
    
    $stmt = $db->prepare("SELECT shop_name, logo_url FROM shop_settings WHERE line_account_id = ? LIMIT 1");
    $stmt->execute([$lineAccountId]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($shop) {
        $companyName = $shop['shop_name'] ?: $companyName;
        $companyLogo = $shop['logo_url'] ?? '';
    }
} catch (Exception $e) {}

// Get LIFF ID - Use Unified LIFF ID
require_once 'includes/liff-helper.php';
$liffData = getUnifiedLiffId($db, $lineAccountId);
$liffId = $liffData['liff_id'];
$lineAccountId = $liffData['line_account_id'];

$baseUrl = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $isEditMode ? 'แก้ไขข้อมูล' : 'สมัครสมาชิก' ?> - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Prompt', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .input-group input,
        .input-group select {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }
        
        .input-group input:focus,
        .input-group select:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .input-group .icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
            transition: color 0.3s;
        }
        
        .input-group input:focus + .icon,
        .input-group select:focus + .icon {
            color: #667eea;
        }
        
        .input-group label {
            position: absolute;
            left: 48px;
            top: -10px;
            background: white;
            padding: 0 8px;
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .input-group.error input,
        .input-group.error select {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .input-group .error-text {
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }
        
        .input-group.error .error-text {
            display: block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 32px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 18px;
            width: 100%;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px -10px rgba(102, 126, 234, 0.5);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -10px rgba(102, 126, 234, 0.6);
        }
        
        .btn-primary:disabled {
            background: #d1d5db;
            box-shadow: none;
            transform: none;
            cursor: not-allowed;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        
        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: all 0.3s;
        }
        
        .step-dot.active {
            width: 24px;
            border-radius: 4px;
            background: white;
        }
        
        .avatar-upload {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            margin: 0 auto 16px;
        }
        
        .avatar-upload img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #374151;
            margin: 24px 0 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .section-title i {
            color: #667eea;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        
        .input-unit {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
        }
        
        .input-group input.has-unit {
            padding-right: 50px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.3s ease-in-out;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen pb-8">
    <!-- Header -->
    <div class="p-4 pt-6">
        <div class="flex items-center justify-between text-white">
            <button onclick="goBack()" class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center backdrop-blur">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="step-indicator">
                <div class="step-dot"></div>
                <div class="step-dot active"></div>
                <div class="step-dot"></div>
            </div>
            <button onclick="closeLiff()" class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center backdrop-blur">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 fade-in">
        <div class="glass-card p-6 max-w-lg mx-auto">
            <!-- Avatar & Welcome -->
            <div class="text-center mb-6">
                <div class="avatar-upload" id="avatarContainer">
                    <img id="userAvatar" src="https://via.placeholder.com/100/667eea/ffffff?text=?" alt="Avatar">
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-1" id="welcomeTitle">
                    <?= $isEditMode ? 'แก้ไขข้อมูลสมาชิก' : 'สมัครสมาชิก' ?>
                </h1>
                <p class="text-gray-500" id="welcomeSubtitle">กรอกข้อมูลเพื่อรับสิทธิพิเศษมากมาย</p>
            </div>

            <!-- Registration Form -->
            <form id="registerForm">
                <!-- Personal Info Section -->
                <div class="section-title">
                    <i class="fas fa-user"></i>
                    <span>ข้อมูลส่วนตัว</span>
                </div>

                <div class="grid-2">
                    <div class="input-group" id="group_first_name">
                        <input type="text" id="first_name" placeholder="ชื่อ" required>
                        <i class="fas fa-user icon"></i>
                        <label>ชื่อ *</label>
                        <div class="error-text">กรุณากรอกชื่อ</div>
                    </div>
                    <div class="input-group" id="group_last_name">
                        <input type="text" id="last_name" placeholder="นามสกุล">
                        <i class="fas fa-user icon"></i>
                        <label>นามสกุล</label>
                    </div>
                </div>

                <div class="input-group" id="group_phone">
                    <input type="tel" id="phone" placeholder="0812345678" pattern="[0-9]{9,10}" inputmode="numeric">
                    <i class="fas fa-phone icon"></i>
                    <label>เบอร์โทรศัพท์</label>
                    <div class="error-text">กรุณากรอกเบอร์โทรให้ถูกต้อง</div>
                </div>

                <div class="input-group" id="group_email">
                    <input type="email" id="email" placeholder="example@email.com">
                    <i class="fas fa-envelope icon"></i>
                    <label>อีเมล</label>
                    <div class="error-text">กรุณากรอกอีเมลให้ถูกต้อง</div>
                </div>

                <div class="grid-2">
                    <div class="input-group" id="group_birthday">
                        <input type="date" id="birthday" required max="<?= date('Y-m-d') ?>">
                        <i class="fas fa-birthday-cake icon"></i>
                        <label>วันเกิด *</label>
                        <div class="error-text">กรุณาเลือกวันเกิด</div>
                    </div>
                    <div class="input-group" id="group_gender">
                        <select id="gender" required>
                            <option value="">เลือกเพศ</option>
                            <option value="male">ชาย</option>
                            <option value="female">หญิง</option>
                            <option value="other">ไม่ระบุ</option>
                        </select>
                        <i class="fas fa-venus-mars icon"></i>
                        <label>เพศ *</label>
                        <div class="error-text">กรุณาเลือกเพศ</div>
                    </div>
                </div>

                <!-- Health Info Section -->
                <div class="section-title">
                    <i class="fas fa-heartbeat"></i>
                    <span>ข้อมูลสุขภาพ</span>
                    <span class="text-xs text-gray-400 ml-auto">(ไม่บังคับ)</span>
                </div>

                <div class="grid-2">
                    <div class="input-group">
                        <input type="number" id="weight" placeholder="0" step="0.1" min="0" max="300" class="has-unit">
                        <i class="fas fa-weight icon"></i>
                        <label>น้ำหนัก</label>
                        <span class="input-unit">กก.</span>
                    </div>
                    <div class="input-group">
                        <input type="number" id="height" placeholder="0" step="0.1" min="0" max="300" class="has-unit">
                        <i class="fas fa-ruler-vertical icon"></i>
                        <label>ส่วนสูง</label>
                        <span class="input-unit">ซม.</span>
                    </div>
                </div>

                <div class="input-group">
                    <input type="text" id="medical_conditions" placeholder="เช่น เบาหวาน, ความดันสูง">
                    <i class="fas fa-notes-medical icon"></i>
                    <label>โรคประจำตัว</label>
                </div>

                <div class="input-group">
                    <input type="text" id="drug_allergies" placeholder="เช่น Penicillin, Aspirin">
                    <i class="fas fa-allergies icon"></i>
                    <label>ยาที่แพ้</label>
                </div>

                <!-- Address Section -->
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>ที่อยู่</span>
                    <span class="text-xs text-gray-400 ml-auto">(ไม่บังคับ)</span>
                </div>

                <div class="input-group">
                    <input type="text" id="address" placeholder="บ้านเลขที่ ซอย ถนน">
                    <i class="fas fa-home icon"></i>
                    <label>ที่อยู่</label>
                </div>

                <div class="grid-2">
                    <div class="input-group">
                        <input type="text" id="district" placeholder="เขต/อำเภอ">
                        <i class="fas fa-map icon"></i>
                        <label>เขต/อำเภอ</label>
                    </div>
                    <div class="input-group">
                        <input type="text" id="province" placeholder="จังหวัด">
                        <i class="fas fa-city icon"></i>
                        <label>จังหวัด</label>
                    </div>
                </div>

                <div class="input-group">
                    <input type="text" id="postal_code" placeholder="10xxx" pattern="[0-9]{5}" inputmode="numeric" maxlength="5">
                    <i class="fas fa-mail-bulk icon"></i>
                    <label>รหัสไปรษณีย์</label>
                </div>

                <!-- Privacy Notice -->
                <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-2xl p-4 mb-6 flex items-start gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-alt text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">
                            ข้อมูลของคุณจะถูกเก็บรักษาอย่างปลอดภัยตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล
                        </p>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="btnSubmit" class="btn-primary">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= $isEditMode ? 'บันทึกการแก้ไข' : 'ยืนยันสมัครสมาชิก' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-white rounded-3xl p-8 text-center">
            <div class="w-16 h-16 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-gray-600 font-medium">กำลังบันทึกข้อมูล...</p>
        </div>
    </div>

    <script>
    const BASE_URL = '<?= $baseUrl ?>';
    const LIFF_ID = '<?= $liffId ?>';
    const ACCOUNT_ID = <?= (int)$lineAccountId ?>;
    const IS_EDIT_MODE = <?= $isEditMode ? 'true' : 'false' ?>;
    
    let userId = null;
    let profile = null;

    document.addEventListener('DOMContentLoaded', init);

    async function init() {
        if (LIFF_ID) {
            try {
                await liff.init({ liffId: LIFF_ID });
                
                if (liff.isLoggedIn()) {
                    profile = await liff.getProfile();
                    userId = profile.userId;
                    
                    // Update avatar
                    if (profile.pictureUrl) {
                        document.getElementById('userAvatar').src = profile.pictureUrl;
                    }
                    
                    // Update welcome message
                    if (profile.displayName) {
                        document.getElementById('welcomeSubtitle').textContent = 
                            IS_EDIT_MODE ? `แก้ไขข้อมูลของ ${profile.displayName}` : `สวัสดี ${profile.displayName}!`;
                    }
                    
                    if (IS_EDIT_MODE) {
                        await loadExistingData();
                    } else {
                        await checkRegistration();
                    }
                } else {
                    // Support external browser - redirect to LINE Login
                    liff.login();
                }
            } catch (e) {
                console.error('LIFF init error:', e);
            }
        }
    }

    async function checkRegistration() {
        if (!userId) return;
        
        try {
            const response = await fetch(`${BASE_URL}/api/member.php?action=check&line_user_id=${userId}&line_account_id=${ACCOUNT_ID}`);
            const data = await response.json();
            console.log('Check registration result:', data);
            
            if (data.success && data.is_registered) {
                console.log('Already registered, redirecting to edit mode...');
                // Redirect to edit mode instead of member card
                window.location.href = `${BASE_URL}/liff-register.php?account=${ACCOUNT_ID}&edit=1`;
            }
        } catch (e) {
            console.error('Check registration error:', e);
        }
    }

    async function loadExistingData() {
        if (!userId) return;
        
        try {
            const response = await fetch(`${BASE_URL}/api/member.php?action=get_card&line_user_id=${userId}&line_account_id=${ACCOUNT_ID}`);
            const data = await response.json();
            
            if (data.success && data.member) {
                const m = data.member;
                document.getElementById('first_name').value = m.first_name || '';
                document.getElementById('last_name').value = m.last_name || '';
                document.getElementById('phone').value = m.phone || '';
                document.getElementById('email').value = m.email || '';
                document.getElementById('birthday').value = m.birthday || '';
                document.getElementById('gender').value = m.gender || '';
                document.getElementById('weight').value = m.weight || '';
                document.getElementById('height').value = m.height || '';
                document.getElementById('medical_conditions').value = m.medical_conditions || '';
                document.getElementById('drug_allergies').value = m.drug_allergies || '';
                document.getElementById('address').value = m.address || '';
                document.getElementById('district').value = m.district || '';
                document.getElementById('province').value = m.province || '';
                document.getElementById('postal_code').value = m.postal_code || '';
            }
        } catch (e) {
            console.error('Load data error:', e);
        }
    }

    // Form validation
    function validateField(fieldId, condition) {
        const group = document.getElementById('group_' + fieldId);
        if (!group) return condition;
        
        if (!condition) {
            group.classList.add('error');
            group.classList.add('shake');
            setTimeout(() => group.classList.remove('shake'), 300);
            return false;
        } else {
            group.classList.remove('error');
            return true;
        }
    }

    // Form submission
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!userId) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเปิดผ่าน LINE',
                text: 'ต้องเปิดผ่านแอป LINE เพื่อสมัครสมาชิก',
                confirmButtonColor: '#667eea'
            });
            return;
        }
        
        // Validate
        const firstName = document.getElementById('first_name').value.trim();
        const birthday = document.getElementById('birthday').value;
        const gender = document.getElementById('gender').value;
        
        let isValid = true;
        isValid = validateField('first_name', firstName.length > 0) && isValid;
        isValid = validateField('birthday', birthday.length > 0) && isValid;
        isValid = validateField('gender', gender.length > 0) && isValid;
        
        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'กรุณากรอกข้อมูลให้ครบ',
                text: 'โปรดตรวจสอบข้อมูลที่จำเป็น',
                confirmButtonColor: '#667eea'
            });
            return;
        }
        
        // Collect form data
        const formData = {
            action: IS_EDIT_MODE ? 'update_profile' : 'register',
            line_user_id: userId,
            line_account_id: ACCOUNT_ID,
            first_name: firstName,
            last_name: document.getElementById('last_name').value.trim(),
            birthday: birthday,
            gender: gender,
            weight: document.getElementById('weight').value || null,
            height: document.getElementById('height').value || null,
            medical_conditions: document.getElementById('medical_conditions').value.trim(),
            drug_allergies: document.getElementById('drug_allergies').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            email: document.getElementById('email').value.trim(),
            address: document.getElementById('address').value.trim(),
            district: document.getElementById('district').value.trim(),
            province: document.getElementById('province').value.trim(),
            postal_code: document.getElementById('postal_code').value.trim()
        };
        
        // Show loading
        document.getElementById('loadingOverlay').classList.remove('hidden');
        document.getElementById('loadingOverlay').classList.add('flex');
        document.getElementById('btnSubmit').disabled = true;
        
        try {
            const response = await fetch(`${BASE_URL}/api/member.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            // Hide loading
            document.getElementById('loadingOverlay').classList.add('hidden');
            document.getElementById('loadingOverlay').classList.remove('flex');
            
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: IS_EDIT_MODE ? 'บันทึกสำเร็จ! ✨' : 'สมัครสมาชิกสำเร็จ! 🎉',
                    html: IS_EDIT_MODE ? 
                        '<p class="text-gray-600">ข้อมูลของคุณถูกอัพเดทแล้ว</p>' :
                        `<div class="text-center">
                            <p class="text-gray-600 mb-2">รหัสสมาชิก: <strong class="text-purple-600">${data.member_id}</strong></p>
                            <div class="bg-gradient-to-r from-purple-100 to-blue-100 rounded-xl p-3 mt-3">
                                <p class="text-purple-700 font-medium">🎁 คุณได้รับ ${data.welcome_bonus} แต้มต้อนรับ!</p>
                            </div>
                        </div>`,
                    confirmButtonColor: '#667eea',
                    confirmButtonText: IS_EDIT_MODE ? 'กลับหน้าหลัก' : 'ดูบัตรสมาชิก'
                });
                
                window.location.href = `${BASE_URL}/liff-member-card.php?account=${ACCOUNT_ID}`;
            } else {
                throw new Error(data.message || 'เกิดข้อผิดพลาด');
            }
        } catch (e) {
            console.error('Register error:', e);
            document.getElementById('loadingOverlay').classList.add('hidden');
            document.getElementById('loadingOverlay').classList.remove('flex');
            
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: e.message,
                confirmButtonColor: '#667eea'
            });
            document.getElementById('btnSubmit').disabled = false;
        }
    });

    // Real-time validation
    ['first_name', 'birthday', 'gender'].forEach(fieldId => {
        const el = document.getElementById(fieldId);
        if (el) {
            el.addEventListener('input', () => {
                const group = document.getElementById('group_' + fieldId);
                if (group) group.classList.remove('error');
            });
        }
    });

    function goBack() {
        if (liff.isInClient()) {
            liff.closeWindow();
        } else {
            window.history.back();
        }
    }

    function closeLiff() {
        if (liff.isInClient()) {
            liff.closeWindow();
        } else {
            window.close();
        }
    }
    </script>
</body>
</html>
