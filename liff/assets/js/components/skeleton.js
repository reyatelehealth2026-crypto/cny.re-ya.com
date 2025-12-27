/**
 * Skeleton Loading Components
 * Provides placeholder UI while content is loading
 * 
 * Requirements: 8.1, 8.3
 * - Display Skeleton_Loading placeholders matching expected content layout
 * - Animate transition from skeleton to actual content
 */

class Skeleton {
    /**
     * Generate a product card skeleton
     * Matches the layout of ProductCard component
     * @param {Object} options - Configuration options
     * @returns {string} HTML string for skeleton product card
     */
    static productCard(options = {}) {
        const { showBadge = false, showWishlist = true } = options;
        
        return `
            <div class="skeleton-product-card">
                <div class="skeleton-product-image-wrapper">
                    ${showBadge ? '<div class="skeleton skeleton-badge"></div>' : ''}
                    <div class="skeleton skeleton-product-image"></div>
                    ${showWishlist ? '<div class="skeleton skeleton-wishlist-btn"></div>' : ''}
                </div>
                <div class="skeleton-product-info">
                    <div class="skeleton skeleton-text skeleton-product-name"></div>
                    <div class="skeleton skeleton-text skeleton-product-name-short"></div>
                    <div class="skeleton skeleton-text skeleton-product-price"></div>
                    <div class="skeleton skeleton-button skeleton-add-to-cart"></div>
                </div>
            </div>
        `;
    }

    /**
     * Generate multiple product card skeletons
     * @param {number} count - Number of skeleton cards to generate
     * @param {Object} options - Configuration options
     * @returns {string} HTML string for multiple skeleton product cards
     */
    static productCards(count = 4, options = {}) {
        return Array(count).fill(null).map(() => this.productCard(options)).join('');
    }

    /**
     * Generate a product grid with skeleton cards
     * @param {number} count - Number of skeleton cards
     * @returns {string} HTML string for skeleton product grid
     */
    static productGrid(count = 6) {
        return `
            <div class="skeleton-product-grid">
                ${this.productCards(count)}
            </div>
        `;
    }

    /**
     * Generate a member card skeleton
     * Matches the layout of MemberCard component
     * @param {Object} options - Configuration options
     * @returns {string} HTML string for skeleton member card
     */
    static memberCard(options = {}) {
        const { showQR = false, showProgress = true } = options;
        
        return `
            <div class="skeleton-member-card">
                <div class="skeleton-member-header">
                    <div class="skeleton skeleton-avatar"></div>
                    <div class="skeleton-member-info">
                        <div class="skeleton skeleton-text skeleton-member-name"></div>
                        <div class="skeleton skeleton-text skeleton-member-id"></div>
                    </div>
                    <div class="skeleton-member-points">
                        <div class="skeleton skeleton-text skeleton-points-value"></div>
                        <div class="skeleton skeleton-text skeleton-points-label"></div>
                    </div>
                </div>
                ${showProgress ? `
                    <div class="skeleton-tier-progress">
                        <div class="skeleton skeleton-progress-bar"></div>
                        <div class="skeleton skeleton-text skeleton-tier-text"></div>
                    </div>
                ` : ''}
                ${showQR ? `
                    <div class="skeleton-qr-section">
                        <div class="skeleton skeleton-qr-code"></div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    /**
     * Generate an order card skeleton
     * Matches the layout of OrderCard component
     * @param {Object} options - Configuration options
     * @returns {string} HTML string for skeleton order card
     */
    static orderCard(options = {}) {
        const { showItems = true, itemCount = 2 } = options;
        
        return `
            <div class="skeleton-order-card">
                <div class="skeleton-order-header">
                    <div class="skeleton skeleton-text skeleton-order-id"></div>
                    <div class="skeleton skeleton-badge skeleton-order-status"></div>
                </div>
                <div class="skeleton-order-date">
                    <div class="skeleton skeleton-text skeleton-date"></div>
                </div>
                ${showItems ? `
                    <div class="skeleton-order-items">
                        ${Array(itemCount).fill(null).map(() => `
                            <div class="skeleton-order-item">
                                <div class="skeleton skeleton-item-image"></div>
                                <div class="skeleton-item-info">
                                    <div class="skeleton skeleton-text skeleton-item-name"></div>
                                    <div class="skeleton skeleton-text skeleton-item-qty"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
                <div class="skeleton-order-footer">
                    <div class="skeleton skeleton-text skeleton-order-total"></div>
                    <div class="skeleton skeleton-button skeleton-order-action"></div>
                </div>
            </div>
        `;
    }

    /**
     * Generate multiple order card skeletons
     * @param {number} count - Number of skeleton cards to generate
     * @param {Object} options - Configuration options
     * @returns {string} HTML string for multiple skeleton order cards
     */
    static orderCards(count = 3, options = {}) {
        return Array(count).fill(null).map(() => this.orderCard(options)).join('');
    }

    /**
     * Generate a pharmacist card skeleton
     * @returns {string} HTML string for skeleton pharmacist card
     */
    static pharmacistCard() {
        return `
            <div class="skeleton-pharmacist-card">
                <div class="skeleton skeleton-pharmacist-photo"></div>
                <div class="skeleton-pharmacist-info">
                    <div class="skeleton skeleton-text skeleton-pharmacist-name"></div>
                    <div class="skeleton skeleton-text skeleton-pharmacist-specialty"></div>
                    <div class="skeleton skeleton-text skeleton-pharmacist-rating"></div>
                </div>
                <div class="skeleton skeleton-button skeleton-book-btn"></div>
            </div>
        `;
    }

    /**
     * Generate multiple pharmacist card skeletons
     * @param {number} count - Number of skeleton cards
     * @returns {string} HTML string for multiple skeleton pharmacist cards
     */
    static pharmacistCards(count = 3) {
        return Array(count).fill(null).map(() => this.pharmacistCard()).join('');
    }

    /**
     * Generate a service grid skeleton
     * @param {number} count - Number of service items
     * @returns {string} HTML string for skeleton service grid
     */
    static serviceGrid(count = 6) {
        return `
            <div class="skeleton-service-grid">
                ${Array(count).fill(null).map(() => `
                    <div class="skeleton-service-item">
                        <div class="skeleton skeleton-service-icon"></div>
                        <div class="skeleton skeleton-text skeleton-service-label"></div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    /**
     * Generate a category filter skeleton
     * @param {number} count - Number of category items
     * @returns {string} HTML string for skeleton category filter
     */
    static categoryFilter(count = 5) {
        return `
            <div class="skeleton-category-filter">
                ${Array(count).fill(null).map(() => `
                    <div class="skeleton skeleton-category-pill"></div>
                `).join('')}
            </div>
        `;
    }

    /**
     * Generate a search bar skeleton
     * @returns {string} HTML string for skeleton search bar
     */
    static searchBar() {
        return `
            <div class="skeleton-search-bar">
                <div class="skeleton skeleton-search-input"></div>
            </div>
        `;
    }

    /**
     * Generate a full shop page skeleton
     * @returns {string} HTML string for skeleton shop page
     */
    static shopPage() {
        return `
            <div class="skeleton-shop-page">
                ${this.searchBar()}
                ${this.categoryFilter()}
                ${this.productGrid(6)}
            </div>
        `;
    }

    /**
     * Generate a full home page skeleton
     * @returns {string} HTML string for skeleton home page
     */
    static homePage() {
        return `
            <div class="skeleton-home-page">
                ${this.memberCard()}
                ${this.serviceGrid()}
                <div class="skeleton-ai-section">
                    <div class="skeleton skeleton-ai-header"></div>
                    <div class="skeleton-ai-buttons">
                        <div class="skeleton skeleton-ai-button"></div>
                        <div class="skeleton skeleton-ai-button"></div>
                        <div class="skeleton skeleton-ai-button"></div>
                        <div class="skeleton skeleton-ai-button"></div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Generate a cart item skeleton
     * @returns {string} HTML string for skeleton cart item
     */
    static cartItem() {
        return `
            <div class="skeleton-cart-item">
                <div class="skeleton skeleton-cart-item-image"></div>
                <div class="skeleton-cart-item-info">
                    <div class="skeleton skeleton-text skeleton-cart-item-name"></div>
                    <div class="skeleton skeleton-text skeleton-cart-item-price"></div>
                </div>
                <div class="skeleton skeleton-cart-qty-control"></div>
            </div>
        `;
    }

    /**
     * Generate multiple cart item skeletons
     * @param {number} count - Number of cart items
     * @returns {string} HTML string for multiple skeleton cart items
     */
    static cartItems(count = 3) {
        return Array(count).fill(null).map(() => this.cartItem()).join('');
    }

    /**
     * Generate a coupon card skeleton
     * @returns {string} HTML string for skeleton coupon card
     */
    static couponCard() {
        return `
            <div class="skeleton-coupon-card">
                <div class="skeleton-coupon-left">
                    <div class="skeleton skeleton-coupon-discount"></div>
                </div>
                <div class="skeleton-coupon-right">
                    <div class="skeleton skeleton-text skeleton-coupon-title"></div>
                    <div class="skeleton skeleton-text skeleton-coupon-desc"></div>
                    <div class="skeleton skeleton-text skeleton-coupon-expiry"></div>
                </div>
            </div>
        `;
    }

    /**
     * Generate a notification item skeleton
     * @returns {string} HTML string for skeleton notification item
     */
    static notificationItem() {
        return `
            <div class="skeleton-notification-item">
                <div class="skeleton skeleton-notification-icon"></div>
                <div class="skeleton-notification-content">
                    <div class="skeleton skeleton-text skeleton-notification-title"></div>
                    <div class="skeleton skeleton-text skeleton-notification-desc"></div>
                    <div class="skeleton skeleton-text skeleton-notification-time"></div>
                </div>
            </div>
        `;
    }

    /**
     * Utility: Wrap content with fade-in animation
     * @param {string} content - HTML content to wrap
     * @returns {string} HTML string with fade-in wrapper
     */
    static withFadeIn(content) {
        return `<div class="skeleton-fade-in">${content}</div>`;
    }

    /**
     * Utility: Replace skeleton with actual content with animation
     * @param {HTMLElement} container - Container element
     * @param {string} content - New HTML content
     * @param {number} delay - Animation delay in ms
     */
    static replaceWithContent(container, content, delay = 300) {
        if (!container) return;
        
        // Add fade-out class to skeleton
        container.classList.add('skeleton-fade-out');
        
        setTimeout(() => {
            container.innerHTML = content;
            container.classList.remove('skeleton-fade-out');
            container.classList.add('skeleton-fade-in');
            
            // Remove animation class after completion
            setTimeout(() => {
                container.classList.remove('skeleton-fade-in');
            }, 300);
        }, delay);
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Skeleton;
}

// Make available globally
window.S