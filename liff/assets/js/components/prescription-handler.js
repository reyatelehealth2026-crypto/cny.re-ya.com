/**
 * Prescription Handler Component
 * Handles prescription product detection, consultation requirements, and approval system
 * 
 * Requirements:
 * - 11.1: Show "Rx" badge and "Requires Pharmacist Approval" label for prescription products
 * - 11.2: Display modal explaining approval process when adding prescription product
 * - 11.3: Block checkout for Rx items without approval
 * - 11.4: Display "Consult Pharmacist" button prominently
 * - 11.7: Create approval record on pharmacist approval
 * - 11.9: Set 24-hour expiry for prescription approval
 * - 11.10: Require re-consultation if approval expired
 */

class PrescriptionHandler {
    constructor() {
        this.baseUrl = window.APP_CONFIG?.BASE_URL || '';
        this.accountId = window.APP_CONFIG?.ACCOUNT_ID || 0;
        this.modalContainer = null;
        this.currentApproval = null;
    }

    /**
     * Check if a product is a prescription product
     * Requirements: 11.1 - Check is_prescription flag on products
     * 
     * @param {Object} product - Product object
     * @returns {boolean} - True if prescription required
     */
    isPrescriptionProduct(product) {
        return Boolean(product?.is_prescription);
    }

    /**
     * Check if cart has prescription items
     * 
     * @param {Object} cart - Cart object
     * @returns {boolean} - True if cart contains prescription items
     */
    cartHasPrescriptionItems(cart) {
        if (!cart || !Array.isArray(cart.items)) return false;
        return cart.items.some(item => item.is_prescription);
    }

    /**
     * Get prescription items from cart
     * 
     * @param {Object} cart - Cart object
     * @returns {Array} - Array of prescription items
     */
    getPrescriptionItems(cart) {
        if (!cart || !Array.isArray(cart.items)) return [];
        return cart.items.filter(item => item.is_prescription);
    }

    /**
     * Render Rx badge for prescription products
     * Requirements: 11.1 - Show "Rx" badge
     * 
     * @param {Object} product - Product object
     * @returns {string} - HTML for Rx badge
     */
    renderRxBadge(product) {
        if (!this.isPrescriptionProduct(product)) return '';
        return '<span class="product-badge product-badge-rx">Rx</span>';
    }

    /**
     * Render prescription warning label
     * Requirements: 11.1 - Show "Requires Pharmacist Approval" label
     * 
     * @param {Object} product - Product object
     * @returns {string} - HTML for warning label
     */
    renderPrescriptionWarningLabel(product) {
        if (!this.isPrescriptionProduct(product)) return '';
        return `
            <div class="prescription-warning-label">
                <i class="fas fa-prescription"></i>
                <span>ต้องได้รับการอนุมัติจากเภสัชกร</span>
            </div>
        `;
    }

    /**
     * Show prescription info modal when adding Rx product to cart
     * Requirements: 11.2 - Display modal explaining approval process
     * 
     * @param {Object} product - Product being added
     * @param {Function} onContinue - Callback when user continues
     * @param {Function} onCancel - Callback when user cancels
     */
    showPrescriptionInfoModal(product, onContinue, onCancel) {
        const self = this;
        const html = `
            <div class="prescription-modal-backdrop" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; display: flex; align-items: flex-end; justify-content: center;">
                <div class="prescription-modal" style="background: white; border-radius: 20px 20px 0 0; width: 100%; max-width: 500px; max-height: 85vh; overflow: hidden; animation: slideUp 0.3s ease;">
                    <!-- Header -->
                    <div class="prescription-modal-header" style="padding: 20px; background: linear-gradient(135deg, #FEE2E2, #FECACA); border-bottom: 1px solid #FCA5A5;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: #EF4444; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-prescription" style="color: white; font-size: 24px;"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 18px; font-weight: 700; color: #991B1B; margin-bottom: 4px;">
                                    ยาที่ต้องมีใบสั่งแพทย์
                                </h3>
                                <p style="font-size: 13px; color: #B91C1C;">
                                    ${product.name}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Body -->
                    <div class="prescription-modal-body" style="padding: 20px;">
                        <div style="background: #FEF3C7; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                            <h4 style="font-size: 14px; font-weight: 600; color: #92400E; margin-bottom: 8px;">
                                <i class="fas fa-info-circle"></i> ขั้นตอนการสั่งซื้อยานี้
                            </h4>
                            <ol style="font-size: 13px; color: #78350F; padding-left: 20px; margin: 0;">
                                <li style="margin-bottom: 8px;">เพิ่มสินค้าลงตะกร้า</li>
                                <li style="margin-bottom: 8px;">ปรึกษาเภสัชกรผ่านวิดีโอคอล</li>
                                <li style="margin-bottom: 8px;">รอการอนุมัติจากเภสัชกร (ภายใน 24 ชม.)</li>
                                <li>ดำเนินการชำระเงินและรับสินค้า</li>
                            </ol>
                        </div>
                        
                        <div style="background: #F3F4F6; border-radius: 12px; padding: 16px;">
                            <p style="font-size: 13px; color: #4B5563; line-height: 1.6;">
                                <i class="fas fa-shield-alt" style="color: #11B0A6;"></i>
                                เพื่อความปลอดภัยของคุณ ยาบางชนิดต้องได้รับการตรวจสอบจากเภสัชกรก่อนจำหน่าย 
                                เภสัชกรจะสอบถามอาการและประวัติการใช้ยาเพื่อให้คำแนะนำที่เหมาะสม
                            </p>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="prescription-modal-footer" style="padding: 16px; border-top: 1px solid #E5E7EB; display: flex; gap: 12px; padding-bottom: max(16px, env(safe-area-inset-bottom));">
                        <button id="rx-modal-cancel" class="btn btn-secondary" style="flex: 1; min-height: 48px; border-radius: 12px; font-weight: 600;">
                            ยกเลิก
                        </button>
                        <button id="rx-modal-continue" class="btn btn-primary" style="flex: 1; min-height: 48px; border-radius: 12px; font-weight: 600; background: #11B0A6;">
                            <i class="fas fa-cart-plus"></i> เพิ่มลงตะกร้า
                        </button>
                    </div>
                </div>
            </div>
            
            <style>
                @keyframes slideUp {
                    from { transform: translateY(100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            </style>
        `;

        // Remove existing modal
        this.closeModal();

        // Create container
        this.modalContainer = document.createElement('div');
        this.modalContainer.id = 'prescription-modal-container';
        this.modalContainer.innerHTML = html;
        document.body.appendChild(this.modalContainer);

        // Bind events
        const cancelBtn = this.modalContainer.querySelector('#rx-modal-cancel');
        const continueBtn = this.modalContainer.querySelector('#rx-modal-continue');
        const backdrop = this.modalContainer.querySelector('.prescription-modal-backdrop');

        cancelBtn?.addEventListener('click', () => {
            self.closeModal();
            if (onCancel) onCancel();
        });

        continueBtn?.addEventListener('click', () => {
            self.closeModal();
            if (onContinue) onContinue(product);
        });

        backdrop?.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                self.closeModal();
                if (onCancel) onCancel();
            }
        });

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    /**
     * Check if cart can proceed to checkout
     * Requirements: 11.3 - Block checkout for Rx items without approval
     * 
     * @param {Object} cart - Cart object
     * @returns {Object} - { canCheckout: boolean, reason: string, needsConsultation: boolean }
     */
    async canProceedToCheckout(cart) {
        if (!this.cartHasPrescriptionItems(cart)) {
            return { canCheckout: true, reason: null, needsConsultation: false };
        }

        // Check if there's a valid approval
        const approvalId = cart.prescriptionApprovalId;
        
        if (!approvalId) {
            return {
                canCheckout: false,
                reason: 'มียาที่ต้องได้รับการอนุมัติจากเภสัชกรก่อนสั่งซื้อ',
                needsConsultation: true
            };
        }

        // Verify approval is still valid
        const approvalStatus = await this.checkApprovalStatus(approvalId);
        
        if (!approvalStatus.valid) {
            return {
                canCheckout: false,
                reason: approvalStatus.reason || 'การอนุมัติหมดอายุ กรุณาปรึกษาเภสัชกรอีกครั้ง',
                needsConsultation: true,
                expired: approvalStatus.expired
            };
        }

        return { canCheckout: true, reason: null, needsConsultation: false };
    }

    /**
     * Check prescription approval status
     * Requirements: 11.10 - Check approval validity before checkout
     * 
     * @param {number} approvalId - Approval ID
     * @returns {Promise<Object>} - Approval status
     */
    async checkApprovalStatus(approvalId) {
        try {
            const profile = window.store?.get('profile');
            
            const response = await fetch(`${this.baseUrl}/api/prescription-approval.php?action=check_status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    approval_id: approvalId,
                    line_user_id: profile?.userId || null,
                    line_account_id: this.accountId
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                return { valid: false, reason: data.message || 'ไม่พบข้อมูลการอนุมัติ' };
            }

            return {
                valid: data.data.valid,
                expired: data.data.expired,
                reason: data.data.reason,
                expiresAt: data.data.expires_at,
                approvedItems: data.data.approved_items
            };
        } catch (error) {
            console.error('Error checking approval status:', error);
            return { valid: false, reason: 'ไม่สามารถตรวจสอบสถานะการอนุมัติได้' };
        }
    }

    /**
     * Show checkout blocked modal for prescription items
     * Requirements: 11.3, 11.4 - Block checkout and show "Consult Pharmacist" button
     * 
     * @param {Object} cart - Cart object
     * @param {Object} checkResult - Result from canProceedToCheckout
     * @param {Function} onConsult - Callback when user wants to consult
     * @param {Function} onClose - Callback when modal closes
     */
    showCheckoutBlockedModal(cart, checkResult, onConsult, onClose) {
        const prescriptionItems = this.getPrescriptionItems(cart);
        const self = this;
        
        const itemsHtml = prescriptionItems.map(item => `
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #FEF2F2; border-radius: 8px; margin-bottom: 8px;">
                <div style="width: 48px; height: 48px; border-radius: 8px; overflow: hidden; flex-shrink: 0;">
                    <img src="${item.image_url || this.baseUrl + '/assets/images/image-placeholder.svg'}" 
                         alt="${item.name}" 
                         style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="flex: 1; min-width: 0;">
                    <p style="font-size: 13px; font-weight: 600; color: #1F2937; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${item.name}
                    </p>
                    <span style="font-size: 11px; background: #EF4444; color: white; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                        Rx
                    </span>
                </div>
            </div>
        `).join('');

        const html = `
            <div class="prescription-modal-backdrop" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; display: flex; align-items: flex-end; justify-content: center;">
                <div class="prescription-modal" style="background: white; border-radius: 20px 20px 0 0; width: 100%; max-width: 500px; max-height: 85vh; overflow: hidden; animation: slideUp 0.3s ease;">
                    <!-- Header -->
                    <div style="padding: 20px; background: linear-gradient(135deg, #FEE2E2, #FECACA); text-align: center;">
                        <div style="width: 64px; height: 64px; background: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                            <i class="fas fa-user-md" style="color: white; font-size: 28px;"></i>
                        </div>
                        <h3 style="font-size: 18px; font-weight: 700; color: #991B1B; margin-bottom: 4px;">
                            ${checkResult.expired ? 'การอนุมัติหมดอายุ' : 'ต้องปรึกษาเภสัชกรก่อน'}
                        </h3>
                        <p style="font-size: 13px; color: #B91C1C;">
                            ${checkResult.reason}
                        </p>
                    </div>
                    
                    <!-- Body -->
                    <div style="padding: 16px; max-height: 40vh; overflow-y: auto;">
                        <p style="font-size: 13px; color: #6B7280; margin-bottom: 12px;">
                            สินค้าต่อไปนี้ต้องได้รับการอนุมัติจากเภสัชกร:
                        </p>
                        ${itemsHtml}
                    </div>
                    
                    <!-- Footer -->
                    <div style="padding: 16px; border-top: 1px solid #E5E7EB; display: flex; flex-direction: column; gap: 12px; padding-bottom: max(16px, env(safe-area-inset-bottom));">
                        <button id="rx-consult-btn" style="width: 100%; min-height: 52px; background: linear-gradient(135deg, #11B0A6, #0D8A82); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas fa-video"></i> ปรึกษาเภสัชกร
                        </button>
                        <button id="rx-close-btn" style="width: 100%; min-height: 44px; background: #F3F4F6; color: #6B7280; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer;">
                            กลับไปดูสินค้า
                        </button>
                    </div>
                </div>
            </div>
            
            <style>
                @keyframes slideUp {
                    from { transform: translateY(100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            </style>
        `;

        // Remove existing modal
        this.closeModal();

        // Create container
        this.modalContainer = document.createElement('div');
        this.modalContainer.id = 'prescription-modal-container';
        this.modalContainer.innerHTML = html;
        document.body.appendChild(this.modalContainer);

        // Bind events
        const consultBtn = this.modalContainer.querySelector('#rx-consult-btn');
        const closeBtn = this.modalContainer.querySelector('#rx-close-btn');
        const backdrop = this.modalContainer.querySelector('.prescription-modal-backdrop');

        consultBtn?.addEventListener('click', () => {
            self.closeModal();
            if (onConsult) onConsult(prescriptionItems);
        });

        closeBtn?.addEventListener('click', () => {
            self.closeModal();
            if (onClose) onClose();
        });

        backdrop?.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                self.closeModal();
                if (onClose) onClose();
            }
        });

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    /**
     * Request pharmacist consultation for prescription items
     * Requirements: 11.4, 11.5 - Initiate consultation with patient info pre-loaded
     * 
     * @param {Array} prescriptionItems - Items requiring consultation
     */
    requestPharmacistConsultation(prescriptionItems) {
        // Navigate to video call page with prescription context
        if (window.router) {
            window.router.navigate('/video-call', {
                reason: 'prescription_approval',
                items: prescriptionItems.map(item => ({
                    product_id: item.product_id,
                    name: item.name,
                    quantity: item.quantity
                }))
            });
        } else {
            // Fallback - show toast
            window.liffApp?.showToast('กรุณาติดต่อเภสัชกรเพื่อขอการอนุมัติ', 'info');
        }
    }

    /**
     * Create prescription approval record
     * Requirements: 11.7, 11.9 - Create approval with 24-hour expiry
     * 
     * @param {Object} approvalData - Approval data from pharmacist
     * @returns {Promise<Object>} - Created approval record
     */
    async createApproval(approvalData) {
        try {
            const profile = window.store?.get('profile');
            
            const response = await fetch(`${this.baseUrl}/api/prescription-approval.php?action=create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    line_user_id: profile?.userId || null,
                    line_account_id: this.accountId,
                    pharmacist_id: approvalData.pharmacist_id,
                    approved_items: approvalData.items,
                    video_call_id: approvalData.video_call_id,
                    notes: approvalData.notes
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Store approval ID in cart
                const cart = window.store?.get('cart');
                if (cart) {
                    cart.prescriptionApprovalId = data.data.approval_id;
                    window.store?.set('cart', cart);
                }
                
                this.currentApproval = data.data;
                return data.data;
            }
            
            throw new Error(data.message || 'Failed to create approval');
        } catch (error) {
            console.error('Error creating prescription approval:', error);
            throw error;
        }
    }

    /**
     * Handle approval received from pharmacist (via webhook or real-time)
     * 
     * @param {Object} approval - Approval data
     */
    handleApprovalReceived(approval) {
        this.currentApproval = approval;
        
        // Update cart with approval ID
        const cart = window.store?.get('cart');
        if (cart) {
            cart.prescriptionApprovalId = approval.id;
            window.store?.set('cart', cart);
        }

        // Show success notification
        window.liffApp?.showToast('เภสัชกรอนุมัติยาแล้ว คุณสามารถดำเนินการสั่งซื้อได้', 'success', 5000);

        // If on checkout page, refresh
        if (window.store?.get('currentPage') === 'checkout') {
            window.router?.navigate('/checkout');
        }
    }

    /**
     * Handle approval rejection from pharmacist
     * 
     * @param {Object} rejection - Rejection data
     */
    handleApprovalRejected(rejection) {
        const cart = window.store?.get('cart');
        
        // Remove rejected items from cart
        if (cart && rejection.rejected_items) {
            cart.items = cart.items.filter(item => 
                !rejection.rejected_items.includes(item.product_id)
            );
            cart.hasPrescription = cart.items.some(item => item.is_prescription);
            cart.prescriptionApprovalId = null;
            window.store?.set('cart', cart);
        }

        // Show rejection notification
        const reason = rejection.reason || 'เภสัชกรไม่อนุมัติยาบางรายการ';
        window.liffApp?.showToast(reason, 'warning', 5000);
    }

    /**
     * Calculate approval expiry time (24 hours from now)
     * Requirements: 11.9 - Set 24-hour expiry
     * 
     * @param {Date} createdAt - Creation timestamp
     * @returns {Date} - Expiry timestamp
     */
    calculateExpiryTime(createdAt = new Date()) {
        const expiry = new Date(createdAt);
        expiry.setHours(expiry.getHours() + 24);
        return expiry;
    }

    /**
     * Check if approval is expired
     * Requirements: 11.10 - Check expiry
     * 
     * @param {string|Date} expiresAt - Expiry timestamp
     * @returns {boolean} - True if expired
     */
    isApprovalExpired(expiresAt) {
        if (!expiresAt) return true;
        const expiry = new Date(expiresAt);
        return new Date() > expiry;
    }

    /**
     * Get time remaining until approval expires
     * 
     * @param {string|Date} expiresAt - Expiry timestamp
     * @returns {Object} - { hours, minutes, expired }
     */
    getTimeUntilExpiry(expiresAt) {
        if (!expiresAt) return { hours: 0, minutes: 0, expired: true };
        
        const expiry = new Date(expiresAt);
        const now = new Date();
        const diff = expiry - now;
        
        if (diff <= 0) {
            return { hours: 0, minutes: 0, expired: true };
        }
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        return { hours, minutes, expired: false };
    }

    /**
     * Render approval status badge
     * 
     * @param {Object} approval - Approval object
     * @returns {string} - HTML for status badge
     */
    renderApprovalStatusBadge(approval) {
        if (!approval) {
            return `
                <div class="approval-status-badge pending">
                    <i class="fas fa-clock"></i>
                    <span>รอการอนุมัติ</span>
                </div>
            `;
        }

        const timeRemaining = this.getTimeUntilExpiry(approval.expires_at);
        
        if (timeRemaining.expired) {
            return `
                <div class="approval-status-badge expired">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>หมดอายุ</span>
                </div>
            `;
        }

        return `
            <div class="approval-status-badge approved">
                <i class="fas fa-check-circle"></i>
                <span>อนุมัติแล้ว (เหลือ ${timeRemaining.hours}ชม. ${timeRemaining.minutes}น.)</span>
            </div>
        `;
    }

    /**
     * Close modal
     */
    closeModal() {
        if (this.modalContainer) {
            this.modalContainer.remove();
            this.modalContainer = null;
        }
        document.body.style.overflow = '';
    }
}

// Create global instance
window.PrescriptionHandler = new PrescriptionHandler();
