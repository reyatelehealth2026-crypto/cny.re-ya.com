/**
 * LIFF State Management Store
 * Centralized state management for the SPA
 * 
 * Requirements: 1.1, 1.2, 1.3 - State management for SPA
 */

class Store {
    constructor() {
        this.state = {
            // User & Auth
            isLoggedIn: false,
            isInClient: false,
            profile: null,
            userId: null,
            
            // Member data
            member: null,
            tier: null,
            points: 0,
            
            // Cart
            cart: {
                items: [],
                subtotal: 0,
                discount: 0,
                shipping: 0,
                total: 0,
                couponCode: null,
                hasPrescription: false,
                prescriptionApprovalId: null
            },
            
            // Wishlist - Requirements: 16.1, 16.2, 16.3, 16.4, 16.5
            wishlist: {
                items: [],       // Array of product IDs
                isLoading: false,
                lastUpdated: null
            },
            
            // Notification Settings - Requirements: 14.1, 14.2, 14.3
            notificationSettings: {
                orderUpdates: true,
                promotions: true,
                appointmentReminders: true,
                drugReminders: true,
                healthTips: false
            },
            
            // Medication Reminders - Requirements: 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.7
            medicationReminders: [],
            
            // UI State
            isLoading: false,
            currentPage: 'home',
            
            // App Config
            config: {
                baseUrl: '',
                liffId: '',
                accountId: 0,
                shopName: '',
                shopLogo: '',
                companyName: ''
            }
        };
        
        this.listeners = new Map();
        this.persistKeys = ['cart', 'wishlist', 'notificationSettings', 'medicationReminders']; // Keys to persist in localStorage
    }

    /**
     * Initialize store with config
     * @param {Object} config - App configuration
     */
    init(config) {
        this.state.config = { ...this.state.config, ...config };
        
        // Load persisted state
        this.loadPersistedState();
    }

    /**
     * Get state value
     * @param {string} key - State key (supports dot notation: 'cart.items')
     */
    get(key) {
        if (!key) return this.state;
        
        const keys = key.split('.');
        let value = this.state;
        
        for (const k of keys) {
            if (value === undefined || value === null) return undefined;
            value = value[k];
        }
        
        return value;
    }

    /**
     * Set state value
     * @param {string} key - State key (supports dot notation)
     * @param {*} value - Value to set
     */
    set(key, value) {
        const keys = key.split('.');
        let obj = this.state;
        
        for (let i = 0; i < keys.length - 1; i++) {
            if (!(keys[i] in obj)) {
                obj[keys[i]] = {};
            }
            obj = obj[keys[i]];
        }
        
        const lastKey = keys[keys.length - 1];
        const oldValue = obj[lastKey];
        obj[lastKey] = value;
        
        // Notify listeners
        this.notify(key, value, oldValue);
        
        // Persist if needed
        if (this.persistKeys.some(pk => key.startsWith(pk))) {
            this.persistState();
        }
    }

    /**
     * Update state with partial object
     * @param {string} key - State key
     * @param {Object} updates - Partial updates
     */
    update(key, updates) {
        const current = this.get(key);
        if (typeof current === 'object' && current !== null) {
            this.set(key, { ...current, ...updates });
        } else {
            this.set(key, updates);
        }
    }

    /**
     * Subscribe to state changes
     * @param {string} key - State key to watch
     * @param {Function} callback - Callback function(newValue, oldValue)
     * @returns {Function} - Unsubscribe function
     */
    subscribe(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, new Set());
        }
        this.listeners.get(key).add(callback);
        
        // Return unsubscribe function
        return () => {
            this.listeners.get(key)?.delete(callback);
        };
    }

    /**
     * Notify listeners of state change
     * @param {string} key - Changed key
     * @param {*} newValue - New value
     * @param {*} oldValue - Old value
     */
    notify(key, newValue, oldValue) {
        // Notify exact key listeners
        this.listeners.get(key)?.forEach(cb => cb(newValue, oldValue));
        
        // Notify parent key listeners
        const parts = key.split('.');
        for (let i = parts.length - 1; i > 0; i--) {
            const parentKey = parts.slice(0, i).join('.');
            this.listeners.get(parentKey)?.forEach(cb => cb(this.get(parentKey)));
        }
        
        // Notify wildcard listeners
        this.listeners.get('*')?.forEach(cb => cb(key, newValue, oldValue));
    }

    /**
     * Persist state to localStorage
     */
    persistState() {
        try {
            const toPersist = {};
            for (const key of this.persistKeys) {
                toPersist[key] = this.get(key);
            }
            localStorage.setItem('liff_store', JSON.stringify(toPersist));
        } catch (e) {
            console.warn('Failed to persist state:', e);
        }
    }

    /**
     * Load persisted state from localStorage
     */
    loadPersistedState() {
        try {
            const stored = localStorage.getItem('liff_store');
            if (stored) {
                const parsed = JSON.parse(stored);
                for (const key of this.persistKeys) {
                    if (parsed[key] !== undefined) {
                        this.set(key, parsed[key]);
                    }
                }
            }
        } catch (e) {
            console.warn('Failed to load persisted state:', e);
        }
    }

    /**
     * Clear persisted state
     */
    clearPersistedState() {
        try {
            localStorage.removeItem('liff_store');
        } catch (e) {
            console.warn('Failed to clear persisted state:', e);
        }
    }

    // ==================== User Methods ====================

    /**
     * Set user profile
     * @param {Object} profile - LINE profile object
     */
    setProfile(profile) {
        this.set('profile', profile);
        this.set('userId', profile?.userId || null);
        this.set('isLoggedIn', !!profile);
    }

    /**
     * Set member data
     * @param {Object} data - Member data from API
     */
    setMemberData(data) {
        if (data?.member) {
            this.set('member', data.member);
            this.set('tier', data.tier || null);
            this.set('points', data.member.points || 0);
        }
    }

    /**
     * Clear user data (logout)
     */
    clearUserData() {
        this.set('isLoggedIn', false);
        this.set('profile', null);
        this.set('userId', null);
        this.set('member', null);
        this.set('tier', null);
        this.set('points', 0);
    }

    // ==================== Cart Methods ====================

    /**
     * Add item to cart
     * @param {Object} product - Product to add
     * @param {number} quantity - Quantity to add
     * @returns {Object} - Updated cart
     */
    addToCart(product, quantity = 1) {
        const cart = this.get('cart');
        const existingIndex = cart.items.findIndex(item => item.product_id === product.id);
        
        if (existingIndex >= 0) {
            cart.items[existingIndex].quantity += quantity;
        } else {
            cart.items.push({
                product_id: product.id,
                name: product.name,
                price: product.sale_price || product.price,
                original_price: product.price,
                quantity: quantity,
                image_url: product.image_url,
                is_prescription: product.is_prescription || false,
                acknowledged_interactions: []
            });
        }
        
        // Check for prescription items
        cart.hasPrescription = cart.items.some(item => item.is_prescription);
        
        this.recalculateCart(cart);
        this.set('cart', cart);
        
        // Sync to server
        this.syncCartToServer(product.id, quantity, 'add');
        
        return cart;
    }

    /**
     * Sync cart item to server
     * @param {number} productId - Product ID
     * @param {number} quantity - Quantity
     * @param {string} action - 'add', 'update', or 'remove'
     */
    async syncCartToServer(productId, quantity, action = 'add') {
        const profile = this.get('profile');
        const config = this.get('config');
        
        if (!profile?.userId || !config?.baseUrl) {
            console.log('🛒 Cart sync skipped: no user or config');
            return;
        }
        
        try {
            let apiAction = 'add_to_cart';
            if (action === 'update') apiAction = 'update_cart';
            if (action === 'remove') apiAction = 'remove_from_cart';
            
            const response = await fetch(`${config.baseUrl}/api/checkout.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: apiAction,
                    line_user_id: profile.userId,
                    product_id: productId,
                    quantity: quantity
                })
            });
            
            const result = await response.json();
            console.log('🛒 Cart synced to server:', result);
        } catch (error) {
            console.warn('🛒 Cart sync failed:', error);
        }
    }

    /**
     * Update cart item quantity
     * @param {number} productId - Product ID
     * @param {number} quantity - New quantity
     */
    updateCartQuantity(productId, quantity) {
        const cart = this.get('cart');
        const index = cart.items.findIndex(item => item.product_id === productId);
        
        if (index >= 0) {
            if (quantity <= 0) {
                cart.items.splice(index, 1);
                // Sync removal to server
                this.syncCartToServer(productId, 0, 'remove');
            } else {
                cart.items[index].quantity = quantity;
                // Sync update to server
                this.syncCartToServer(productId, quantity, 'update');
            }
            
            cart.hasPrescription = cart.items.some(item => item.is_prescription);
            this.recalculateCart(cart);
            this.set('cart', cart);
        }
    }

    /**
     * Remove item from cart
     * @param {number} productId - Product ID to remove
     */
    removeFromCart(productId) {
        this.updateCartQuantity(productId, 0);
    }

    /**
     * Clear cart
     */
    clearCart() {
        this.set('cart', {
            items: [],
            subtotal: 0,
            discount: 0,
            shipping: 0,
            total: 0,
            couponCode: null,
            hasPrescription: false,
            prescriptionApprovalId: null
        });
        
        // Sync clear to server
        this.syncClearCartToServer();
    }

    /**
     * Sync clear cart to server
     */
    async syncClearCartToServer() {
        const profile = this.get('profile');
        const config = this.get('config');
        
        if (!profile?.userId || !config?.baseUrl) return;
        
        try {
            await fetch(`${config.baseUrl}/api/checkout.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'clear_cart',
                    line_user_id: profile.userId
                })
            });
            console.log('🛒 Cart cleared on server');
        } catch (error) {
            console.warn('🛒 Cart clear sync failed:', error);
        }
    }

    /**
     * Recalculate cart totals
     * @param {Object} cart - Cart object
     */
    recalculateCart(cart) {
        cart.subtotal = cart.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        cart.total = cart.subtotal - cart.discount + cart.shipping;
    }

    /**
     * Get cart item count
     * @returns {number} - Total items in cart
     */
    getCartCount() {
        const cart = this.get('cart');
        return cart.items.reduce((sum, item) => sum + item.quantity, 0);
    }

    /**
     * Serialize cart to JSON for storage
     * Requirements: 2.9 - Serialize cart to JSON for storage
     * @returns {string} - JSON string representation of cart
     */
    serializeCart() {
        const cart = this.get('cart');
        return JSON.stringify({
            items: cart.items.map(item => ({
                product_id: item.product_id,
                name: item.name,
                price: item.price,
                original_price: item.original_price,
                quantity: item.quantity,
                image_url: item.image_url,
                is_prescription: item.is_prescription || false,
                acknowledged_interactions: item.acknowledged_interactions || []
            })),
            subtotal: cart.subtotal,
            discount: cart.discount,
            shipping: cart.shipping,
            total: cart.total,
            couponCode: cart.couponCode,
            hasPrescription: cart.hasPrescription,
            prescriptionApprovalId: cart.prescriptionApprovalId,
            serializedAt: new Date().toISOString()
        });
    }

    /**
     * Deserialize cart from JSON storage
     * Requirements: 2.10 - Deserialize cart from storage
     * @param {string} json - JSON string to deserialize
     * @returns {boolean} - True if successful, false otherwise
     */
    deserializeCart(json) {
        try {
            if (!json || typeof json !== 'string') {
                console.warn('Invalid cart JSON: empty or not a string');
                return false;
            }

            const parsed = JSON.parse(json);
            
            // Validate required structure
            if (!parsed || typeof parsed !== 'object') {
                console.warn('Invalid cart JSON: not an object');
                return false;
            }

            // Validate items array
            if (!Array.isArray(parsed.items)) {
                console.warn('Invalid cart JSON: items is not an array');
                return false;
            }

            // Validate each item has required fields
            const validItems = parsed.items.filter(item => {
                return item && 
                       typeof item.product_id !== 'undefined' &&
                       typeof item.name === 'string' &&
                       typeof item.price === 'number' &&
                       typeof item.quantity === 'number' &&
                       item.quantity > 0;
            });

            // Reconstruct cart object with validated data
            const cart = {
                items: validItems.map(item => ({
                    product_id: item.product_id,
                    name: item.name,
                    price: parseFloat(item.price) || 0,
                    original_price: parseFloat(item.original_price) || item.price,
                    quantity: parseInt(item.quantity) || 1,
                    image_url: item.image_url || '',
                    is_prescription: Boolean(item.is_prescription),
                    acknowledged_interactions: Array.isArray(item.acknowledged_interactions) 
                        ? item.acknowledged_interactions : []
                })),
                subtotal: parseFloat(parsed.subtotal) || 0,
                discount: parseFloat(parsed.discount) || 0,
                shipping: parseFloat(parsed.shipping) || 0,
                total: parseFloat(parsed.total) || 0,
                couponCode: parsed.couponCode || null,
                hasPrescription: Boolean(parsed.hasPrescription),
                prescriptionApprovalId: parsed.prescriptionApprovalId || null
            };

            // Recalculate totals to ensure consistency
            this.recalculateCart(cart);
            
            // Set the cart
            this.set('cart', cart);
            
            return true;
        } catch (e) {
            console.error('Failed to deserialize cart:', e);
            return false;
        }
    }

    /**
     * Export cart for external use (e.g., API submission)
     * @returns {Object} - Cart object ready for API
     */
    exportCartForApi() {
        const cart = this.get('cart');
        return {
            items: cart.items.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                price: item.price,
                is_prescription: item.is_prescription
            })),
            coupon_code: cart.couponCode,
            prescription_approval_id: cart.prescriptionApprovalId
        };
    }

    /**
     * Import cart from API response
     * @param {Object} apiCart - Cart data from API
     */
    importCartFromApi(apiCart) {
        if (!apiCart || !Array.isArray(apiCart.items)) {
            return false;
        }

        const cart = {
            items: apiCart.items.map(item => ({
                product_id: item.product_id || item.id,
                name: item.name || item.product_name,
                price: parseFloat(item.sale_price || item.price) || 0,
                original_price: parseFloat(item.price) || 0,
                quantity: parseInt(item.quantity) || 1,
                image_url: item.image_url || '',
                is_prescription: Boolean(item.is_prescription),
                acknowledged_interactions: []
            })),
            subtotal: parseFloat(apiCart.subtotal) || 0,
            discount: parseFloat(apiCart.discount) || 0,
            shipping: parseFloat(apiCart.shipping_fee || apiCart.shipping) || 0,
            total: parseFloat(apiCart.total) || 0,
            couponCode: apiCart.coupon_code || null,
            hasPrescription: false,
            prescriptionApprovalId: apiCart.prescription_approval_id || null
        };

        // Check for prescription items
        cart.hasPrescription = cart.items.some(item => item.is_prescription);
        
        // Recalculate to ensure consistency
        this.recalculateCart(cart);
        
        this.set('cart', cart);
        return true;
    }

    // ==================== UI Methods ====================

    /**
     * Set loading state
     * @param {boolean} isLoading - Loading state
     */
    setLoading(isLoading) {
        this.set('isLoading', isLoading);
    }

    /**
     * Set current page
     * @param {string} page - Page name
     */
    setCurrentPage(page) {
        this.set('currentPage', page);
    }

    // ==================== Wishlist Methods ====================
    // Requirements: 16.1, 16.2, 16.3, 16.4, 16.5

    /**
     * Check if product is in wishlist
     * @param {number} productId - Product ID to check
     * @returns {boolean} - True if in wishlist
     */
    isInWishlist(productId) {
        const wishlist = this.get('wishlist');
        return wishlist.items.includes(parseInt(productId));
    }

    /**
     * Add product to wishlist
     * @param {number} productId - Product ID to add
     */
    addToWishlist(productId) {
        const wishlist = this.get('wishlist');
        const id = parseInt(productId);
        
        if (!wishlist.items.includes(id)) {
            wishlist.items.push(id);
            wishlist.lastUpdated = new Date().toISOString();
            this.set('wishlist', wishlist);
        }
    }

    /**
     * Remove product from wishlist
     * @param {number} productId - Product ID to remove
     */
    removeFromWishlist(productId) {
        const wishlist = this.get('wishlist');
        const id = parseInt(productId);
        const index = wishlist.items.indexOf(id);
        
        if (index > -1) {
            wishlist.items.splice(index, 1);
            wishlist.lastUpdated = new Date().toISOString();
            this.set('wishlist', wishlist);
        }
    }

    /**
     * Toggle product in wishlist
     * @param {number} productId - Product ID to toggle
     * @returns {boolean} - True if added, false if removed
     */
    toggleWishlist(productId) {
        if (this.isInWishlist(productId)) {
            this.removeFromWishlist(productId);
            return false;
        } else {
            this.addToWishlist(productId);
            return true;
        }
    }

    /**
     * Get wishlist count
     * @returns {number} - Number of items in wishlist
     */
    getWishlistCount() {
        const wishlist = this.get('wishlist');
        return wishlist.items.length;
    }

    /**
     * Set wishlist items from API
     * @param {Array} items - Array of product IDs
     */
    setWishlistItems(items) {
        const wishlist = this.get('wishlist');
        wishlist.items = items.map(id => parseInt(id));
        wishlist.lastUpdated = new Date().toISOString();
        this.set('wishlist', wishlist);
    }

    /**
     * Clear wishlist
     */
    clearWishlist() {
        this.set('wishlist', {
            items: [],
            isLoading: false,
            lastUpdated: null
        });
    }

    // ==================== Notification Settings Methods ====================
    // Requirements: 14.1, 14.2, 14.3

    /**
     * Get notification setting
     * @param {string} category - Notification category
     * @returns {boolean} - Setting value
     */
    getNotificationSetting(category) {
        const settings = this.get('notificationSettings');
        return settings[category] ?? true;
    }

    /**
     * Set notification setting
     * @param {string} category - Notification category
     * @param {boolean} enabled - Enable/disable
     */
    setNotificationSetting(category, enabled) {
        const settings = this.get('notificationSettings');
        settings[category] = enabled;
        this.set('notificationSettings', settings);
    }

    /**
     * Set all notification settings
     * @param {Object} settings - Settings object
     */
    setNotificationSettings(settings) {
        this.set('notificationSettings', { ...this.get('notificationSettings'), ...settings });
    }

    // ==================== Medication Reminder Methods ====================
    // Requirements: 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.7

    /**
     * Get medication reminders
     * @returns {Array} - Array of medication reminders
     */
    getMedicationReminders() {
        return this.get('medicationReminders') || [];
    }

    /**
     * Add medication reminder
     * @param {Object} reminder - Reminder object
     */
    addMedicationReminder(reminder) {
        const reminders = this.getMedicationReminders();
        reminder.id = reminder.id || Date.now();
        reminder.createdAt = new Date().toISOString();
        reminders.push(reminder);
        this.set('medicationReminders', reminders);
    }

    /**
     * Update medication reminder
     * @param {number} id - Reminder ID
     * @param {Object} updates - Updates to apply
     */
    updateMedicationReminder(id, updates) {
        const reminders = this.getMedicationReminders();
        const index = reminders.findIndex(r => r.id === id);
        
        if (index > -1) {
            reminders[index] = { ...reminders[index], ...updates };
            this.set('medicationReminders', reminders);
        }
    }

    /**
     * Remove medication reminder
     * @param {number} id - Reminder ID
     */
    removeMedicationReminder(id) {
        const reminders = this.getMedicationReminders();
        const filtered = reminders.filter(r => r.id !== id);
        this.set('medicationReminders', filtered);
    }

    /**
     * Mark medication as taken
     * @param {number} reminderId - Reminder ID
     * @param {string} timestamp - When taken
     */
    markMedicationTaken(reminderId, timestamp = null) {
        const reminders = this.getMedicationReminders();
        const index = reminders.findIndex(r => r.id === reminderId);
        
        if (index > -1) {
            if (!reminders[index].takenHistory) {
                reminders[index].takenHistory = [];
            }
            reminders[index].takenHistory.push({
                timestamp: timestamp || new Date().toISOString(),
                status: 'taken'
            });
            reminders[index].lastTaken = timestamp || new Date().toISOString();
            this.set('medicationReminders', reminders);
        }
    }
}

// Create global store instance
window.store = new Store();
