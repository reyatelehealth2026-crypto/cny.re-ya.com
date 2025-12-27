/**
 * Bottom Navigation Component
 * Fixed bottom navigation bar with 4-5 main sections
 * 
 * Requirements: 9.1, 9.2, 9.3, 9.4
 * - Display fixed bottom navigation bar with 4-5 main sections
 * - Highlight active item on tap and navigate to section
 * - Show icon and label for each item
 * - Display badge on cart icon showing item count
 */

class BottomNav {
    constructor(options = {}) {
        this.container = null;
        this.navItems = [];
        this.currentPage = 'home';
        this.cartCount = 0;
        
        // Default navigation items
        this.items = options.items || [
            { id: 'home', page: 'home', icon: 'fa-home', label: 'หน้าหลัก', path: '/' },
            { id: 'shop', page: 'shop', icon: 'fa-store', label: 'ร้านค้า', path: '/shop' },
            { id: 'cart', page: 'cart', icon: 'fa-shopping-cart', label: 'ตะกร้า', path: '/cart', hasBadge: true },
            { id: 'orders', page: 'orders', icon: 'fa-box', label: 'ออเดอร์', path: '/orders' },
            { id: 'profile', page: 'profile', icon: 'fa-user', label: 'โปรไฟล์', path: '/profile' }
        ];
        
        // Bind methods
        this.handleNavClick = this.handleNavClick.bind(this);
        this.updateActiveItem = this.updateActiveItem.bind(this);
        this.updateCartBadge = this.updateCartBadge.bind(this);
    }

    /**
     * Initialize the bottom navigation
     * @param {string|HTMLElement} containerSelector - Container element or selector
     */
    init(containerSelector = '#bottom-nav') {
        this.container = typeof containerSelector === 'string' 
            ? document.querySelector(containerSelector) 
            : containerSelector;
        
        if (!this.container) {
            console.error('BottomNav: Container not found');
            return;
        }

        // Render navigation
        this.render();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Subscribe to store changes
        this.subscribeToStore();
        
        // Set initial active state
        this.updateActiveFromRoute();
        
        console.log('✅ BottomNav initialized');
    }

    /**
     * Render the navigation HTML
     */
    render() {
        const html = this.items.map(item => this.renderNavItem(item)).join('');
        this.container.innerHTML = html;
        
        // Cache nav item elements
        this.navItems = Array.from(this.container.querySelectorAll('.nav-item'));
    }

    /**
     * Render a single navigation item
     * @param {Object} item - Navigation item config
     * @returns {string} - HTML string
     */
    renderNavItem(item) {
        const isActive = this.currentPage === item.page;
        const activeClass = isActive ? 'active' : '';
        
        return `
            <a href="#${item.path}" 
               class="nav-item ${activeClass}" 
               data-page="${item.page}"
               data-path="${item.path}"
               role="button"
               aria-label="${item.label}"
               aria-current="${isActive ? 'page' : 'false'}">
                <i class="fas ${item.icon}" aria-hidden="true"></i>
                <span class="nav-label">${item.label}</span>
                ${item.hasBadge ? `<span id="cart-badge" class="nav-badge hidden" aria-label="จำนวนสินค้าในตะกร้า">0</span>` : ''}
            </a>
        `;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Navigation item clicks
        this.container.addEventListener('click', this.handleNavClick);
        
        // Listen for route changes
        window.addEventListener('hashchange', () => this.updateActiveFromRoute());
        window.addEventListener('popstate', () => this.updateActiveFromRoute());
    }

    /**
     * Handle navigation item click
     * @param {Event} e - Click event
     */
    handleNavClick(e) {
        const navItem = e.target.closest('.nav-item');
        if (!navItem) return;
        
        e.preventDefault();
        
        const path = navItem.dataset.path;
        const page = navItem.dataset.page;
        
        // Add click feedback animation
        this.addClickFeedback(navItem);
        
        // Navigate using router
        if (window.router) {
            window.router.navigate(path);
        } else {
            // Fallback to hash navigation
            window.location.hash = path;
        }
        
        // Update active state
        this.updateActiveItem(page);
    }

    /**
     * Add click feedback animation
     * @param {HTMLElement} element - Clicked element
     */
    addClickFeedback(element) {
        element.classList.add('nav-item-pressed');
        
        // Remove class after animation
        setTimeout(() => {
            element.classList.remove('nav-item-pressed');
        }, 150);
    }

    /**
     * Update active navigation item
     * @param {string} page - Page name to set as active
     */
    updateActiveItem(page) {
        this.currentPage = page;
        
        this.navItems.forEach(item => {
            const itemPage = item.dataset.page;
            const isActive = itemPage === page;
            
            item.classList.toggle('active', isActive);
            item.setAttribute('aria-current', isActive ? 'page' : 'false');
        });
    }

    /**
     * Update active state from current route
     */
    updateActiveFromRoute() {
        const hash = window.location.hash || '#/';
        const path = hash.slice(1) || '/';
        
        // Find matching item
        const matchedItem = this.items.find(item => {
            if (item.path === path) return true;
            // Handle nested routes (e.g., /orders/123 should highlight orders)
            if (path.startsWith(item.path) && item.path !== '/') return true;
            return false;
        });
        
        if (matchedItem) {
            this.updateActiveItem(matchedItem.page);
        }
    }

    /**
     * Subscribe to store changes for cart badge
     */
    subscribeToStore() {
        if (window.store) {
            // Subscribe to cart changes
            window.store.subscribe('cart', () => {
                this.updateCartBadge();
            });
            
            // Initial cart badge update
            this.updateCartBadge();
        }
    }

    /**
     * Update cart badge count
     * Requirements: 9.4 - Display badge on cart icon showing item count
     */
    updateCartBadge() {
        const badge = this.container.querySelector('#cart-badge');
        if (!badge) return;
        
        const count = window.store?.getCartCount() || 0;
        
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
            badge.setAttribute('aria-label', `${count} สินค้าในตะกร้า`);
            
            // Add bounce animation for new items
            badge.classList.add('badge-bounce');
            setTimeout(() => badge.classList.remove('badge-bounce'), 300);
        } else {
            badge.classList.add('hidden');
        }
    }

    /**
     * Set cart badge count manually
     * @param {number} count - Item count
     */
    setCartBadge(count) {
        this.cartCount = count;
        this.updateCartBadge();
    }

    /**
     * Show/hide the navigation bar
     * @param {boolean} visible - Whether to show the nav
     */
    setVisible(visible) {
        if (this.container) {
            this.container.classList.toggle('hidden', !visible);
        }
    }

    /**
     * Get current active page
     * @returns {string} - Current page name
     */
    getCurrentPage() {
        return this.currentPage;
    }

    /**
     * Destroy the component
     */
    destroy() {
        if (this.container) {
            this.container.removeEventListener('click', this.handleNavClick);
        }
    }
}

// Create global instance
window.bottomNav = new BottomNav();

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize if container exists
    const container = document.getElementById('bottom-nav');
    if (container) {
        window.bottomNav.init(container);
    }
});
