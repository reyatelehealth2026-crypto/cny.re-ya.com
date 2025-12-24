<?php
/**
 * PWA Install Prompt - Popup แนะนำติดตั้งแอป
 */
?>
<!-- PWA Install Prompt Modal -->
<div id="pwaInstallModal" class="fixed inset-0 z-[9999] hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closePwaModal()"></div>
    
    <!-- Modal Content -->
    <div class="absolute bottom-0 left-0 right-0 transform transition-transform duration-300 translate-y-full" id="pwaModalContent">
        <div class="bg-white rounded-t-3xl shadow-2xl max-w-lg mx-auto overflow-hidden">
            <!-- Header with gradient -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-8 text-center relative overflow-hidden">
                <!-- Decorative circles -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white/10 rounded-full"></div>
                
                <!-- App Icon -->
                <div class="relative">
                    <div class="w-20 h-20 bg-white rounded-2xl mx-auto flex items-center justify-center shadow-lg mb-4 transform hover:scale-105 transition-transform">
                        <i class="fab fa-line text-green-500 text-4xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-1">ติดตั้ง LINE OA Manager</h2>
                    <p class="text-green-100 text-sm">เพิ่มลงหน้าจอหลักเพื่อเข้าถึงได้เร็วขึ้น</p>
                </div>
            </div>
            
            <!-- Features -->
            <div class="px-6 py-5">
                <div class="space-y-3">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-bolt text-green-600"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">เปิดได้ทันที</div>
                            <div class="text-xs text-gray-500">ไม่ต้องเปิด Browser ก่อน</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-bell text-blue-600"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">รับการแจ้งเตือน</div>
                            <div class="text-xs text-gray-500">ไม่พลาดข้อความจากลูกค้า</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-mobile-alt text-purple-600"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">ใช้งานเหมือนแอป</div>
                            <div class="text-xs text-gray-500">เต็มหน้าจอ ไม่มี Address Bar</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="px-6 pb-6 space-y-3">
                <!-- Install Button (for supported browsers) -->
                <button id="pwaInstallBtn" onclick="installPwa()" class="w-full py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-green-700 transition shadow-lg flex items-center justify-center">
                    <i class="fas fa-download mr-2"></i>
                    ติดตั้งเลย
                </button>
                
                <!-- Manual Install Instructions (for iOS) -->
                <div id="iosInstructions" class="hidden">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-sm font-medium text-gray-700 mb-3">วิธีติดตั้งบน iPhone/iPad:</p>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-2">1</span>
                                กดปุ่ม <i class="fas fa-share-square text-blue-500 mx-1"></i> Share
                            </div>
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-2">2</span>
                                เลื่อนลงแล้วกด "Add to Home Screen"
                            </div>
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-2">3</span>
                                กด "Add" ที่มุมขวาบน
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Later Button -->
                <button onclick="dismissPwaModal()" class="w-full py-3 text-gray-500 font-medium hover:text-gray-700 transition">
                    ไว้ทีหลัง
                </button>
            </div>
            
            <!-- Safe area for iOS -->
            <div class="h-safe-area-bottom bg-white"></div>
        </div>
    </div>
</div>

<style>
.h-safe-area-bottom {
    height: env(safe-area-inset-bottom, 0px);
}

#pwaInstallModal.show {
    display: block;
}

#pwaInstallModal.show #pwaModalContent {
    transform: translateY(0);
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

#pwaInstallModal.show .absolute.inset-0 {
    animation: fadeIn 0.3s ease-out;
}

#pwaInstallModal.show #pwaModalContent {
    animation: slideUp 0.4s ease-out;
}
</style>

<script>
// PWA Install Prompt Logic
let deferredPrompt = null;
let isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
let isAndroid = /Android/.test(navigator.userAgent);
let isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

// Check if should show prompt
function shouldShowInstallPrompt() {
    // Don't show if already installed
    if (isStandalone) return false;
    
    // Don't show if dismissed recently (within 7 days)
    const dismissed = localStorage.getItem('pwaPromptDismissed');
    if (dismissed) {
        const dismissedDate = new Date(parseInt(dismissed));
        const daysSinceDismissed = (Date.now() - dismissedDate) / (1000 * 60 * 60 * 24);
        if (daysSinceDismissed < 7) return false;
    }
    
    // Don't show if already installed (checked via localStorage)
    if (localStorage.getItem('pwaInstalled')) return false;
    
    return true;
}

// Show the modal
function showPwaModal() {
    const modal = document.getElementById('pwaInstallModal');
    if (!modal) return;
    
    // Show iOS instructions if on iOS
    if (isIOS) {
        document.getElementById('pwaInstallBtn').classList.add('hidden');
        document.getElementById('iosInstructions').classList.remove('hidden');
    }
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close the modal
function closePwaModal() {
    const modal = document.getElementById('pwaInstallModal');
    if (!modal) return;
    
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Dismiss and remember
function dismissPwaModal() {
    localStorage.setItem('pwaPromptDismissed', Date.now().toString());
    closePwaModal();
}

// Install PWA
async function installPwa() {
    if (!deferredPrompt) {
        // Fallback for browsers that don't support beforeinstallprompt
        alert('กรุณาใช้เมนูของ Browser เพื่อเพิ่มลงหน้าจอหลัก');
        return;
    }
    
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    
    if (outcome === 'accepted') {
        localStorage.setItem('pwaInstalled', 'true');
        console.log('PWA installed');
    }
    
    deferredPrompt = null;
    closePwaModal();
}

// Listen for beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show prompt after a delay if conditions are met
    if (shouldShowInstallPrompt()) {
        setTimeout(() => {
            showPwaModal();
        }, 3000); // Show after 3 seconds
    }
});

// For iOS, show after page load
if (isIOS && shouldShowInstallPrompt()) {
    window.addEventListener('load', () => {
        setTimeout(() => {
            showPwaModal();
        }, 5000); // Show after 5 seconds on iOS
    });
}

// Track successful installation
window.addEventListener('appinstalled', () => {
    localStorage.setItem('pwaInstalled', 'true');
    closePwaModal();
    console.log('PWA was installed');
});
</script>
