/**
 * Permission Checker Component
 * Handles camera and microphone permission requests for video calls
 * 
 * Requirements: 6.1, 6.6
 * - Display pre-call check UI for camera and microphone permissions
 * - Display instructions to enable permissions in device settings if denied
 */

class PermissionChecker {
    constructor() {
        // Permission states
        this.cameraPermission = 'prompt'; // 'granted', 'denied', 'prompt'
        this.microphonePermission = 'prompt';
        
        // Callbacks
        this.onPermissionsGranted = null;
        this.onPermissionsDenied = null;
        this.onPermissionChange = null;
        
        // Device info
        this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        this.isAndroid = /Android/.test(navigator.userAgent);
        this.isLINE = /Line/i.test(navigator.userAgent);
    }

    /**
     * Initialize the permission checker
     * @param {Object} options - Configuration options
     */
    init(options = {}) {
        if (options.onPermissionsGranted) this.onPermissionsGranted = options.onPermissionsGranted;
        if (options.onPermissionsDenied) this.onPermissionsDenied = options.onPermissionsDenied;
        if (options.onPermissionChange) this.onPermissionChange = options.onPermissionChange;
        
        console.log('🔐 PermissionChecker initialized');
    }

    /**
     * Check current permission status without requesting
     * @returns {Promise<Object>} - Current permission states
     */
    async checkPermissionStatus() {
        const result = {
            camera: 'prompt',
            microphone: 'prompt',
            supported: true
        };

        // Check if mediaDevices API is supported
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            result.supported = false;
            return result;
        }

        // Check permissions API if available
        if (navigator.permissions) {
            try {
                const [cameraStatus, micStatus] = await Promise.all([
                    navigator.permissions.query({ name: 'camera' }).catch(() => null),
                    navigator.permissions.query({ name: 'microphone' }).catch(() => null)
                ]);

                if (cameraStatus) {
                    result.camera = cameraStatus.state;
                    this.cameraPermission = cameraStatus.state;
                    
                    // Listen for changes
                    cameraStatus.onchange = () => {
                        this.cameraPermission = cameraStatus.state;
                        if (this.onPermissionChange) {
                            this.onPermissionChange('camera', cameraStatus.state);
                        }
                    };
                }

                if (micStatus) {
                    result.microphone = micStatus.state;
                    this.microphonePermission = micStatus.state;
                    
                    // Listen for changes
                    micStatus.onchange = () => {
                        this.microphonePermission = micStatus.state;
                        if (this.onPermissionChange) {
                            this.onPermissionChange('microphone', micStatus.state);
                        }
                    };
                }
            } catch (error) {
                console.warn('Permissions API not fully supported:', error);
            }
        }

        return result;
    }

    /**
     * Request camera and microphone permissions
     * @param {Object} options - Request options
     * @returns {Promise<Object>} - Result with stream or error
     */
    async requestPermissions(options = {}) {
        const constraints = {
            video: options.video !== false ? {
                facingMode: options.facingMode || 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            } : false,
            audio: options.audio !== false ? {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            } : false
        };

        try {
            console.log('🔐 Requesting media permissions...');
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            
            this.cameraPermission = 'granted';
            this.microphonePermission = 'granted';
            
            console.log('🔐 Permissions granted');
            
            if (this.onPermissionsGranted) {
                this.onPermissionsGranted(stream);
            }
            
            return {
                success: true,
                stream: stream,
                camera: 'granted',
                microphone: 'granted'
            };
            
        } catch (error) {
            console.error('🔐 Permission error:', error);
            
            const result = this.handlePermissionError(error);
            
            if (this.onPermissionsDenied) {
                this.onPermissionsDenied(result);
            }
            
            return result;
        }
    }

    /**
     * Handle permission errors and provide user-friendly messages
     * @param {Error} error - The error from getUserMedia
     * @returns {Object} - Error details with user-friendly message
     */
    handlePermissionError(error) {
        const result = {
            success: false,
            error: error,
            errorType: 'unknown',
            message: 'เกิดข้อผิดพลาดในการเข้าถึงกล้องหรือไมโครโฟน',
            instructions: null
        };

        switch (error.name) {
            case 'NotAllowedError':
            case 'PermissionDeniedError':
                result.errorType = 'denied';
                result.message = 'ไม่ได้รับอนุญาตให้เข้าถึงกล้องหรือไมโครโฟน';
                result.instructions = this.getPermissionInstructions();
                this.cameraPermission = 'denied';
                this.microphonePermission = 'denied';
                break;
                
            case 'NotFoundError':
            case 'DevicesNotFoundError':
                result.errorType = 'not_found';
                result.message = 'ไม่พบกล้องหรือไมโครโฟนบนอุปกรณ์นี้';
                break;
                
            case 'NotReadableError':
            case 'TrackStartError':
                result.errorType = 'in_use';
                result.message = 'กล้องหรือไมโครโฟนกำลังถูกใช้งานโดยแอปอื่น';
                break;
                
            case 'OverconstrainedError':
                result.errorType = 'constraints';
                result.message = 'ไม่สามารถใช้งานกล้องตามที่ต้องการได้';
                break;
                
            case 'SecurityError':
                result.errorType = 'security';
                result.message = 'ไม่สามารถเข้าถึงกล้องได้เนื่องจากข้อจำกัดด้านความปลอดภัย';
                result.instructions = 'กรุณาใช้งานผ่าน HTTPS หรือ localhost';
                break;
                
            case 'AbortError':
                result.errorType = 'aborted';
                result.message = 'การเข้าถึงกล้องถูกยกเลิก';
                break;
                
            default:
                result.errorType = 'unknown';
                result.message = error.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ';
        }

        return result;
    }

    /**
     * Get platform-specific instructions for enabling permissions
     * Requirements: 6.6 - Display instructions to enable permissions in device settings
     * @returns {Object} - Instructions for the current platform
     */
    getPermissionInstructions() {
        if (this.isIOS) {
            if (this.isLINE) {
                return {
                    title: 'วิธีเปิดสิทธิ์การเข้าถึงกล้องใน LINE',
                    steps: [
                        'เปิดแอป "ตั้งค่า" บน iPhone',
                        'เลื่อนลงและแตะ "LINE"',
                        'เปิดสวิตช์ "กล้อง" และ "ไมโครโฟน"',
                        'กลับมาที่ LINE และลองใหม่อีกครั้ง'
                    ],
                    icon: 'fa-mobile-alt'
                };
            }
            return {
                title: 'วิธีเปิดสิทธิ์การเข้าถึงกล้องบน iOS',
                steps: [
                    'เปิดแอป "ตั้งค่า" บน iPhone',
                    'แตะ "Safari" หรือเบราว์เซอร์ที่ใช้',
                    'แตะ "กล้อง" และเลือก "อนุญาต"',
                    'แตะ "ไมโครโฟน" และเลือก "อนุญาต"',
                    'กลับมาที่หน้านี้และรีเฟรช'
                ],
                icon: 'fa-apple'
            };
        }

        if (this.isAndroid) {
            if (this.isLINE) {
                return {
                    title: 'วิธีเปิดสิทธิ์การเข้าถึงกล้องใน LINE',
                    steps: [
                        'เปิด "ตั้งค่า" บนโทรศัพท์',
                        'แตะ "แอป" หรือ "แอปพลิเคชัน"',
                        'ค้นหาและแตะ "LINE"',
                        'แตะ "สิทธิ์" หรือ "Permissions"',
                        'เปิดสิทธิ์ "กล้อง" และ "ไมโครโฟน"',
                        'กลับมาที่ LINE และลองใหม่'
                    ],
                    icon: 'fa-android'
                };
            }
            return {
                title: 'วิธีเปิดสิทธิ์การเข้าถึงกล้องบน Android',
                steps: [
                    'เปิด "ตั้งค่า" บนโทรศัพท์',
                    'แตะ "แอป" หรือ "แอปพลิเคชัน"',
                    'ค้นหาและแตะเบราว์เซอร์ที่ใช้',
                    'แตะ "สิทธิ์" หรือ "Permissions"',
                    'เปิดสิทธิ์ "กล้อง" และ "ไมโครโฟน"',
                    'กลับมาที่หน้านี้และรีเฟรช'
                ],
                icon: 'fa-android'
            };
        }

        // Desktop/Other
        return {
            title: 'วิธีเปิดสิทธิ์การเข้าถึงกล้อง',
            steps: [
                'คลิกไอคอนกล้องหรือแม่กุญแจในแถบที่อยู่',
                'เลือก "อนุญาต" สำหรับกล้องและไมโครโฟน',
                'รีเฟรชหน้าเว็บและลองใหม่อีกครั้ง'
            ],
            icon: 'fa-desktop'
        };
    }

    /**
     * Render permission check UI
     * Requirements: 6.1 - Display pre-call check UI for camera and microphone permissions
     * @param {Object} status - Current permission status
     * @returns {string} - HTML string
     */
    renderPermissionCheckUI(status = {}) {
        const cameraStatus = status.camera || this.cameraPermission;
        const micStatus = status.microphone || this.microphonePermission;
        
        return `
            <div class="permission-check-ui">
                <div class="permission-check-header">
                    <div class="permission-check-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h2 class="permission-check-title">ตรวจสอบสิทธิ์การเข้าถึง</h2>
                    <p class="permission-check-desc">กรุณาอนุญาตการเข้าถึงกล้องและไมโครโฟนเพื่อเริ่มวิดีโอคอล</p>
                </div>
                
                <div class="permission-check-items">
                    <!-- Camera Permission -->
                    <div class="permission-item ${cameraStatus === 'granted' ? 'granted' : cameraStatus === 'denied' ? 'denied' : ''}">
                        <div class="permission-item-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="permission-item-info">
                            <p class="permission-item-name">กล้อง</p>
                            <p class="permission-item-status">${this.getStatusText(cameraStatus)}</p>
                        </div>
                        <div class="permission-item-indicator">
                            ${this.getStatusIcon(cameraStatus)}
                        </div>
                    </div>
                    
                    <!-- Microphone Permission -->
                    <div class="permission-item ${micStatus === 'granted' ? 'granted' : micStatus === 'denied' ? 'denied' : ''}">
                        <div class="permission-item-icon">
                            <i class="fas fa-microphone"></i>
                        </div>
                        <div class="permission-item-info">
                            <p class="permission-item-name">ไมโครโฟน</p>
                            <p class="permission-item-status">${this.getStatusText(micStatus)}</p>
                        </div>
                        <div class="permission-item-indicator">
                            ${this.getStatusIcon(micStatus)}
                        </div>
                    </div>
                </div>
                
                <div class="permission-check-actions">
                    ${cameraStatus === 'denied' || micStatus === 'denied' ? `
                        <button class="btn btn-secondary permission-help-btn" onclick="window.permissionChecker.showInstructions()">
                            <i class="fas fa-question-circle"></i>
                            วิธีเปิดสิทธิ์
                        </button>
                    ` : ''}
                    <button class="btn btn-primary permission-request-btn" onclick="window.permissionChecker.requestAndUpdate()">
                        <i class="fas fa-shield-alt"></i>
                        ${cameraStatus === 'denied' || micStatus === 'denied' ? 'ลองใหม่' : 'ขอสิทธิ์การเข้าถึง'}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Render permission denied UI with instructions
     * Requirements: 6.6 - Display instructions to enable permissions in device settings
     * @param {Object} errorResult - Error result from requestPermissions
     * @returns {string} - HTML string
     */
    renderPermissionDeniedUI(errorResult = {}) {
        const instructions = errorResult.instructions || this.getPermissionInstructions();
        
        return `
            <div class="permission-denied-ui">
                <div class="permission-denied-header">
                    <div class="permission-denied-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="permission-denied-title">${errorResult.message || 'ไม่ได้รับอนุญาต'}</h2>
                </div>
                
                ${instructions ? `
                    <div class="permission-instructions">
                        <div class="permission-instructions-header">
                            <i class="fas ${instructions.icon || 'fa-info-circle'}"></i>
                            <h3>${instructions.title}</h3>
                        </div>
                        <ol class="permission-instructions-steps">
                            ${instructions.steps.map(step => `<li>${step}</li>`).join('')}
                        </ol>
                    </div>
                ` : ''}
                
                <div class="permission-denied-actions">
                    <button class="btn btn-primary" onclick="window.permissionChecker.requestAndUpdate()">
                        <i class="fas fa-redo"></i>
                        ลองใหม่อีกครั้ง
                    </button>
                    <button class="btn btn-secondary" onclick="window.router.navigate('/')">
                        <i class="fas fa-arrow-left"></i>
                        กลับหน้าหลัก
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Get status text for display
     */
    getStatusText(status) {
        switch (status) {
            case 'granted': return 'อนุญาตแล้ว';
            case 'denied': return 'ถูกปฏิเสธ';
            case 'prompt': return 'รอการอนุญาต';
            default: return 'ไม่ทราบสถานะ';
        }
    }

    /**
     * Get status icon HTML
     */
    getStatusIcon(status) {
        switch (status) {
            case 'granted': 
                return '<i class="fas fa-check-circle" style="color: var(--success);"></i>';
            case 'denied': 
                return '<i class="fas fa-times-circle" style="color: var(--danger);"></i>';
            case 'prompt': 
                return '<i class="fas fa-circle" style="color: var(--warning);"></i>';
            default: 
                return '<i class="fas fa-question-circle" style="color: var(--text-muted);"></i>';
        }
    }

    /**
     * Request permissions and update UI
     */
    async requestAndUpdate() {
        const container = document.querySelector('.permission-check-ui, .permission-denied-ui');
        if (container) {
            container.innerHTML = `
                <div class="permission-loading">
                    <div class="permission-loading-spinner"></div>
                    <p>กำลังขอสิทธิ์การเข้าถึง...</p>
                </div>
            `;
        }

        const result = await this.requestPermissions();
        
        if (result.success) {
            // Permissions granted - callback will handle next steps
            return result;
        } else {
            // Show denied UI
            if (container) {
                container.outerHTML = this.renderPermissionDeniedUI(result);
            }
            return result;
        }
    }

    /**
     * Show instructions modal
     */
    showInstructions() {
        const instructions = this.getPermissionInstructions();
        
        const modal = document.createElement('div');
        modal.className = 'permission-modal-overlay';
        modal.innerHTML = `
            <div class="permission-modal">
                <div class="permission-modal-header">
                    <i class="fas ${instructions.icon || 'fa-info-circle'}"></i>
                    <h3>${instructions.title}</h3>
                    <button class="permission-modal-close" onclick="this.closest('.permission-modal-overlay').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="permission-modal-body">
                    <ol class="permission-instructions-steps">
                        ${instructions.steps.map(step => `<li>${step}</li>`).join('')}
                    </ol>
                </div>
                <div class="permission-modal-footer">
                    <button class="btn btn-primary" onclick="this.closest('.permission-modal-overlay').remove()">
                        เข้าใจแล้ว
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on overlay click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    /**
     * Check if WebRTC is supported
     * @returns {boolean}
     */
    isWebRTCSupported() {
        return !!(
            navigator.mediaDevices &&
            navigator.mediaDevices.getUserMedia &&
            window.RTCPeerConnection
        );
    }

    /**
     * Get device capabilities
     * @returns {Promise<Object>}
     */
    async getDeviceCapabilities() {
        const capabilities = {
            hasCamera: false,
            hasMicrophone: false,
            cameras: [],
            microphones: []
        };

        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            return capabilities;
        }

        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            
            capabilities.cameras = devices.filter(d => d.kind === 'videoinput');
            capabilities.microphones = devices.filter(d => d.kind === 'audioinput');
            capabilities.hasCamera = capabilities.cameras.length > 0;
            capabilities.hasMicrophone = capabilities.microphones.length > 0;
            
        } catch (error) {
            console.error('Error enumerating devices:', error);
        }

        return capabilities;
    }
}

// Create global instance
window.PermissionChecker = PermissionChecker;
window.permissionChecker = new PermissionChecker();
