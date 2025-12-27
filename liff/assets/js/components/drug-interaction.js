/**
 * Drug Interaction Checker Component
 * Handles drug interaction checking and warning display for LIFF app
 * 
 * Requirements:
 * - 12.1: Check for interactions with existing cart items and user medication history
 * - 12.2: Display warning modal with severity level (Mild, Moderate, Severe)
 * - 12.3: Show interacting drugs, interaction type, and recommended action
 * - 12.4: Block addition for severe interactions and require pharmacist consultation
 * - 12.5: Allow moderate interactions with user acknowledgment
 * - 12.8: Use color coding (Red for Severe, Orange for Moderate, Yellow for Mild)
 */

class DrugInteractionChecker {
    constructor() {
        this.baseUrl = window.APP_CONFIG?.BASE_URL || '';
        this.accountId = window.APP_CONFIG?.ACCOUNT_ID || 0;
        this.modalContainer = null;
        this.currentInteraction = null;
        this.onAcknowledge = null;
        this.onConsult = null;
        this.onCancel = null;
    }

    /**
     * Check interactions for a product being added to cart
     * Requirements: 12.1 - Check for interactions with existing cart items and user medication history
     * 
     * @param {number} productId - Product ID to check
     * @param {Array} cartItems - Current cart items
     * @param {Array} userMedications - User's current medications (optional)
     * @returns {Promise<Object>} - Interaction check result
     */
    async checkInteractions(productId, cartItems = [], userMedications = []) {
        try {
            const profile = window.store?.get('profile');
            
            const response = await fetch(`${this.baseUrl}/api/drug-interactions.php?action=check`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    cart_items: cartItems.map(item => ({
                        product_id: item.product_id,
                        name: item.name
                    })),
                    user_medications: userMedications,
                    line_user_id: profile?.userId || null,
                    line_account_id: this.accountId
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                console.error('Interaction check failed:', data.message);
                return { interactions: [], can_add: true };
            }

            return data.data;
        } catch (error) {
            console.error('Error checking interactions:', error);
            // On error, allow addition but log the issue
            return { interactions: [], can_add: true, error: error.message };
        }
    }

    /**
     * Check interactions for entire cart before checkout
     * 
     * @param {Array} cartItems - Cart items to check
     * @returns {Promise<Object>} - Cart interaction check result
     */
    async checkCartInteractions(cartItems = []) {
        try {
            const profile = window.store?.get('profile');
            
            const response = await fetch(`${this.baseUrl}/api/drug-interactions.php?action=check_cart`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    cart_items: cartItems.map(item => ({
                        product_id: item.product_id,
                        name: item.name
                    })),
                    line_user_id: profile?.userId || null,
                    line_account_id: this.accountId
                })
            });

            const data = await response.json();
            return data.success ? data.data : { interactions: [], can_checkout: true };
        } catch (error) {
            console.error('Error checking cart interactions:', error);
            return { interactions: [], can_checkout: true, error: error.message };
        }
    }

    /**
     * Handle product addition with interaction check
     * This is the main entry point for adding products to cart
     * 
     * @param {Object} product - Product to add
     * @param {Function} onSuccess - Callback when product can be added
     * @param {Function} onBlock - Callback when product is blocked
     */
    async handleAddToCart(product, onSuccess, onBlock) {
        const cart = window.store?.get('cart') || { items: [] };
        const result = await this.checkInteractions(product.id, cart.items);

        if (!result.interactions || result.interactions.length === 0) {
            // No interactions, proceed with addition
            onSuccess(product);
            return;
        }

        // Has interactions - show appropriate warning
        const hasSevere = result.interactions.some(i => 
            i.severity === 'severe' || i.severity === 'contraindicated'
        );
        const hasModerate = result.interactions.some(i => i.severity === 'moderate');

        if (hasSevere) {
            // Requirements: 12.4 - Block addition for severe interactions
            this.showSevereWarning(result.interactions, product, onBlock);
        } else if (hasModerate) {
            // Requirements: 12.5 - Allow moderate with acknowledgment
            this.showModerateWarning(result.interactions, product, onSuccess, onBlock);
        } else {
            // Mild interactions - show info and proceed
            this.showMildNotice(result.interactions, product, onSuccess);
        }
    }

    /**
     * Show severe interaction warning modal
     * Requirements: 12.4 - Block product addition for severe interactions
     * 
     * @param {Array} interactions - List of interactions
     * @param {Object} product - Product being added
     * @param {Function} onBlock - Callback when blocked
     */
    showSevereWarning(interactions, product, onBlock) {
        const severeInteractions = interactions.filter(i => 
            i.severity === 'severe' || i.severity === 'contraindicated'
        );

        const self = this;
        const modal = this.createModalWithActions({
            type: 'severe',
            title: '⛔ ปฏิกิริยารุนแรง',
            subtitle: 'ไม่สามารถเพิ่มสินค้านี้ได้',
            interactions: severeInteractions,
            product: product,
            buttons: [
                {
                    text: '<i class="fas fa-user-md"></i> ปรึกษาเภสัชกร',
                    class: 'btn-danger',
                    action: function() {
                        self.closeModal();
                        self.requestPharmacistConsult(product, severeInteractions);
                    }
                },
                {
                    text: 'ปิด',
                    class: 'btn-secondary',
                    action: function() {
                        self.closeModal();
                        if (onBlock) onBlock(product, severeInteractions);
                    }
                }
            ]
        });

        this.showModalWithActions(modal);
    }

    /**
     * Show moderate interaction warning modal
     * Requirements: 12.5 - Allow addition with user acknowledgment checkbox
     * 
     * @param {Array} interactions - List of interactions
     * @param {Object} product - Product being added
     * @param {Function} onSuccess - Callback when acknowledged
     * @param {Function} onCancel - Callback when cancelled
     */
    showModerateWarning(interactions, product, onSuccess, onCancel) {
        const moderateInteractions = interactions.filter(i => i.severity === 'moderate');

        const self = this;
        const modal = this.createModalWithActions({
            type: 'moderate',
            title: '⚠️ ปฏิกิริยาปานกลาง',
            subtitle: 'กรุณาอ่านคำเตือนก่อนเพิ่มสินค้า',
            interactions: moderateInteractions,
            product: product,
            showAcknowledgment: true,
            buttons: [
                {
                    text: '<i class="fas fa-check"></i> ยืนยันเพิ่มสินค้า',
                    class: 'btn-warning',
                    id: 'btn-acknowledge',
                    disabled: true,
                    action: function() {
                        self.closeModal();
                        // Record acknowledged interactions
                        self.recordAcknowledgment(product, moderateInteractions);
                        if (onSuccess) onSuccess(product, moderateInteractions);
                    }
                },
                {
                    text: 'ยกเลิก',
                    class: 'btn-secondary',
                    action: function() {
                        self.closeModal();
                        if (onCancel) onCancel(product);
                    }
                }
            ]
        });

        this.showModalWithActions(modal);
    }

    /**
     * Show mild interaction notice
     * Requirements: 12.6 - Display informational notice without blocking
     * 
     * @param {Array} interactions - List of interactions
     * @param {Object} product - Product being added
     * @param {Function} onSuccess - Callback to proceed
     */
    showMildNotice(interactions, product, onSuccess) {
        const mildInteractions = interactions.filter(i => i.severity === 'mild');

        const self = this;
        const modal = this.createModalWithActions({
            type: 'mild',
            title: 'ℹ️ ข้อมูลเพิ่มเติม',
            subtitle: 'มีข้อควรทราบเกี่ยวกับยานี้',
            interactions: mildInteractions,
            product: product,
            buttons: [
                {
                    text: '<i class="fas fa-cart-plus"></i> เพิ่มลงตะกร้า',
                    class: 'btn-primary',
                    action: function() {
                        self.closeModal();
                        if (onSuccess) onSuccess(product);
                    }
                },
                {
                    text: 'ยกเลิก',
                    class: 'btn-secondary',
                    action: function() {
                        self.closeModal();
                    }
                }
            ]
        });

        this.showModalWithActions(modal);
    }

    /**
     * Create modal HTML with action bindings
     * Requirements: 12.2, 12.3, 12.8 - Display warning with severity color coding
     * 
     * @param {Object} config - Modal configuration
     * @returns {Object} - Modal HTML and button actions
     */
    createModalWithActions(config) {
        const { type, title, subtitle, interactions, product, showAcknowledgment, buttons } = config;
        
        // Severity colors (Requirement 12.8)
        const severityColors = {
            'contraindicated': { bg: '#FEE2E2', border: '#EF4444', text: '#991B1B', icon: '⛔' },
            'severe': { bg: '#FEE2E2', border: '#EF4444', text: '#991B1B', icon: '🚫' },
            'moderate': { bg: '#FEF3C7', border: '#F59E0B', text: '#92400E', icon: '⚠️' },
            'mild': { bg: '#FEF9C3', border: '#FCD34D', text: '#854D0E', icon: 'ℹ️' }
        };

        const typeColors = severityColors[type] || severityColors.mild;

        const interactionsList = interactions.map(interaction => {
            const colors = severityColors[interaction.severity] || severityColors.mild;
            return `
                <div class="interaction-item" style="background: ${colors.bg}; border-left: 4px solid ${colors.border}; padding: 12px; border-radius: 8px; margin-bottom: 8px;">
                    <div class="interaction-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span class="interaction-icon">${colors.icon}</span>
                        <span class="interaction-drugs" style="font-weight: 600; color: ${colors.text};">
                            ${interaction.drug1} + ${interaction.drug2}
                        </span>
                        <span class="interaction-severity" style="font-size: 11px; padding: 2px 8px; border-radius: 12px; background: ${colors.border}; color: white; margin-left: auto;">
                            ${this.getSeverityLabel(interaction.severity)}
                        </span>
                    </div>
                    <p class="interaction-description" style="font-size: 13px; color: #374151; margin-bottom: 4px;">
                        ${interaction.description}
                    </p>
                    <p class="interaction-recommendation" style="font-size: 12px; color: #6B7280;">
                        <i class="fas fa-lightbulb" style="color: ${colors.border};"></i> ${interaction.recommendation}
                    </p>
                    ${interaction.source === 'user_medication' ? `
                        <p class="interaction-source" style="font-size: 11px; color: #9CA3AF; margin-top: 4px;">
                            <i class="fas fa-pills"></i> จากยาที่คุณทานอยู่
                        </p>
                    ` : ''}
                </div>
            `;
        }).join('');

        const acknowledgmentHtml = showAcknowledgment ? `
            <div class="acknowledgment-section" style="margin-top: 16px; padding: 12px; background: #F9FAFB; border-radius: 8px;">
                <label class="acknowledgment-label" style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                    <input type="checkbox" id="acknowledge-checkbox" class="acknowledge-checkbox" 
                           style="width: 20px; height: 20px; margin-top: 2px; accent-color: #F59E0B;">
                    <span style="font-size: 13px; color: #374151; line-height: 1.4;">
                        ฉันได้อ่านและเข้าใจคำเตือนข้างต้นแล้ว และยืนยันที่จะเพิ่มสินค้านี้ลงตะกร้า
                    </span>
                </label>
            </div>
        ` : '';

        const buttonsHtml = buttons.map((btn, index) => `
            <button class="interaction-btn ${btn.class}" 
                    id="${btn.id || ''}" 
                    data-btn-index="${index}"
                    ${btn.disabled ? 'disabled' : ''}
                    style="flex: 1; min-height: 44px; padding: 12px 16px; border-radius: 10px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                ${btn.text}
            </button>
        `).join('');

        const html = `
            <div class="interaction-modal-backdrop" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; display: flex; align-items: flex-end; justify-content: center;">
                <div class="interaction-modal" style="background: white; border-radius: 20px 20px 0 0; width: 100%; max-width: 500px; max-height: 85vh; overflow: hidden; animation: slideUp 0.3s ease;">
                    <!-- Header -->
                    <div class="interaction-modal-header" style="padding: 20px; background: ${typeColors.bg}; border-bottom: 1px solid ${typeColors.border};">
                        <h3 style="font-size: 18px; font-weight: 700; color: ${typeColors.text}; margin-bottom: 4px;">
                            ${title}
                        </h3>
                        <p style="font-size: 13px; color: ${typeColors.text}; opacity: 0.8;">
                            ${subtitle}
                        </p>
                        ${product ? `
                            <p style="font-size: 12px; color: #6B7280; margin-top: 8px;">
                                <i class="fas fa-pills"></i> ${product.name}
                            </p>
                        ` : ''}
                    </div>
                    
                    <!-- Body -->
                    <div class="interaction-modal-body" style="padding: 16px; max-height: 50vh; overflow-y: auto;">
                        <div class="interactions-list">
                            ${interactionsList}
                        </div>
                        ${acknowledgmentHtml}
                    </div>
                    
                    <!-- Footer -->
                    <div class="interaction-modal-footer" style="padding: 16px; border-top: 1px solid #E5E7EB; display: flex; gap: 12px; padding-bottom: max(16px, env(safe-area-inset-bottom));">
                        ${buttonsHtml}
                    </div>
                </div>
            </div>
            
            <style>
                @keyframes slideUp {
                    from { transform: translateY(100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .interaction-btn.btn-danger { background: #EF4444; color: white; }
                .interaction-btn.btn-danger:hover { background: #DC2626; }
                .interaction-btn.btn-warning { background: #F59E0B; color: white; }
                .interaction-btn.btn-warning:hover { background: #D97706; }
                .interaction-btn.btn-warning:disabled { background: #D1D5DB; color: #9CA3AF; cursor: not-allowed; }
                .interaction-btn.btn-primary { background: #11B0A6; color: white; }
                .interaction-btn.btn-primary:hover { background: #0D8A82; }
                .interaction-btn.btn-secondary { background: #F3F4F6; color: #374151; }
                .interaction-btn.btn-secondary:hover { background: #E5E7EB; }
            </style>
        `;

        return { html, buttons };
    }

    /**
     * Show modal with action bindings
     * @param {Object} modalData - Modal HTML and button actions
     */
    showModalWithActions(modalData) {
        const { html, buttons } = modalData;
        
        // Remove existing modal
        this.closeModal();

        // Create container
        this.modalContainer = document.createElement('div');
        this.modalContainer.id = 'interaction-modal-container';
        this.modalContainer.innerHTML = html;
        document.body.appendChild(this.modalContainer);

        // Bind button actions
        buttons.forEach((btn, index) => {
            const btnEl = this.modalContainer.querySelector(`[data-btn-index="${index}"]`);
            if (btnEl && btn.action) {
                btnEl.addEventListener('click', btn.action);
            }
        });

        // Setup other event listeners
        this.setupModalEvents();

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    /**
     * Get severity label in Thai
     * 
     * @param {string} severity - Severity level
     * @returns {string} - Thai label
     */
    getSeverityLabel(severity) {
        const labels = {
            'contraindicated': 'ห้ามใช้ร่วมกัน',
            'severe': 'รุนแรง',
            'moderate': 'ปานกลาง',
            'mild': 'เล็กน้อย'
        };
        return labels[severity] || severity;
    }

    /**
     * Show modal
     * 
     * @param {string} html - Modal HTML
     * @param {Array} buttonActions - Array of button action functions
     */
    showModal(html, buttonActions = []) {
        // Remove existing modal
        this.closeModal();

        // Create container
        this.modalContainer = document.createElement('div');
        this.modalContainer.id = 'interaction-modal-container';
        this.modalContainer.innerHTML = html;
        document.body.appendChild(this.modalContainer);

        // Setup event listeners
        this.setupModalEvents(buttonActions);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    /**
     * Setup modal event listeners
     * @param {Array} buttonActions - Array of button action functions
     */
    setupModalEvents(buttonActions = []) {
        // Backdrop click to close
        const backdrop = this.modalContainer.querySelector('.interaction-modal-backdrop');
        backdrop?.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                this.closeModal();
            }
        });

        // Acknowledgment checkbox
        const checkbox = this.modalContainer.querySelector('#acknowledge-checkbox');
        const acknowledgeBtn = this.modalContainer.querySelector('#btn-acknowledge');
        
        if (checkbox && acknowledgeBtn) {
            checkbox.addEventListener('change', () => {
                acknowledgeBtn.disabled = !checkbox.checked;
            });
        }
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

    /**
     * Record acknowledged interaction
     * Requirements: 12.5 - Record acknowledged interactions
     * 
     * @param {Object} product - Product being added
     * @param {Array} interactions - Acknowledged interactions
     */
    recordAcknowledgment(product, interactions) {
        // Store in cart item
        const cart = window.store?.get('cart');
        if (cart) {
            const item = cart.items.find(i => i.product_id === product.id);
            if (item) {
                item.acknowledged_interactions = interactions.map(i => ({
                    id: i.id,
                    drug1: i.drug1,
                    drug2: i.drug2,
                    severity: i.severity,
                    acknowledged_at: new Date().toISOString()
                }));
                window.store?.set('cart', cart);
            }
        }

        console.log('Interaction acknowledged:', { product: product.id, interactions });
    }

    /**
     * Request pharmacist consultation
     * Requirements: 12.4 - Offer pharmacist consultation for severe interactions
     * 
     * @param {Object} product - Product with interaction
     * @param {Array} interactions - Severe interactions
     */
    requestPharmacistConsult(product, interactions) {
        // Navigate to video call or consultation page
        if (window.router) {
            window.router.navigate('/video-call', {
                reason: 'drug_interaction',
                product_id: product.id,
                product_name: product.name,
                interactions: interactions
            });
        } else {
            // Fallback - show toast
            window.liffApp?.showToast('กรุณาติดต่อเภสัชกรเพื่อขอคำปรึกษา', 'info');
        }
    }
}

// Create global instance
window.DrugInteractionChecker = new DrugInteractionChecker();
