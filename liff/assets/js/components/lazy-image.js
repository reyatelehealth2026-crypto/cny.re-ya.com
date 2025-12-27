/**
 * Lazy Loading Image Component
 * Implements lazy loading with Intersection Observer
 * 
 * Requirements: 8.5
 * - Use lazy loading for images
 * - Display placeholder until loaded
 */

class LazyImage {
    constructor(options = {}) {
        this.options = {
            rootMargin: '50px 0px',
            threshold: 0.01,
            placeholderClass: 'lazy-placeholder',
            loadedClass: 'lazy-loaded',
            errorClass: 'lazy-error',
            ...options
        };
        
        this.observer = null;
        this.init();
    }

    /**
     * Initialize the Intersection Observer
     */
    init() {
        // Check for IntersectionObserver support
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver(
                (entries) => this.handleIntersection(entries),
                {
                    rootMargin: this.options.rootMargin,
                    threshold: this.options.threshold
                }
            );
        }
        
        // Observe existing lazy images
        this.observeAll();
        
        // Set up mutation observer for dynamically added images
        this.setupMutationObserver();
    }

    /**
     * Handle intersection events
     * @param {IntersectionObserverEntry[]} entries 
     */
    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                this.loadImage(entry.target);
                this.observer.unobserve(entry.target);
            }
        });
    }

    /**
     * Load an image
     * @param {HTMLImageElement} img 
     */
    loadImage(img) {
        const src = img.dataset.src;
        const srcset = img.dataset.srcset;
        
        if (!src) return;

        // Create a new image to preload
        const tempImg = new Image();
        
        tempImg.onload = () => {
            img.src = src;
            if (srcset) {
                img.srcset = srcset;
            }
            img.classList.remove(this.options.placeholderClass);
            img.classList.add(this.options.loadedClass);
            
            // Trigger custom event
            img.dispatchEvent(new CustomEvent('lazyloaded', { bubbles: true }));
        };
        
        tempImg.onerror = () => {
            img.classList.remove(this.options.placeholderClass);
            img.classList.add(this.options.errorClass);
            
            // Set fallback image
            img.src = this.getPlaceholderSvg('error');
            
            // Trigger custom event
            img.dispatchEvent(new CustomEvent('lazyerror', { bubbles: true }));
        };
        
        tempImg.src = src;
    }

    /**
     * Observe all lazy images in the document
     */
    observeAll() {
        const images = document.querySelectorAll('img[data-src], img.lazy');
        images.forEach(img => this.observe(img));
    }

    /**
     * Observe a single image
     * @param {HTMLImageElement} img 
     */
    observe(img) {
        if (!img.dataset.src && !img.classList.contains('lazy')) return;
        
        // Set placeholder if not already set
        if (!img.src || img.src === window.location.href) {
            img.src = this.getPlaceholderSvg('loading');
        }
        
        img.classList.add(this.options.placeholderClass);
        
        if (this.observer) {
            this.observer.observe(img);
        } else {
            // Fallback for browsers without IntersectionObserver
            this.loadImage(img);
        }
    }

    /**
     * Setup mutation observer for dynamically added images
     */
    setupMutationObserver() {
        const mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        // Check if the node itself is a lazy image
                        if (node.tagName === 'IMG' && (node.dataset.src || node.classList.contains('lazy'))) {
                            this.observe(node);
                        }
                        // Check for lazy images within the added node
                        const lazyImages = node.querySelectorAll?.('img[data-src], img.lazy');
                        lazyImages?.forEach(img => this.observe(img));
                    }
                });
            });
        });

        mutationObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Generate a placeholder SVG
     * @param {string} type - 'loading' or 'error'
     * @returns {string} Data URI for SVG
     */
    getPlaceholderSvg(type = 'loading') {
        const bgColor = '#F1F5F9';
        const iconColor = '#9CA3AF';
        
        if (type === 'error') {
            return `data:image/svg+xml,${encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                    <rect width="100" height="100" fill="${bgColor}"/>
                    <text x="50" y="55" text-anchor="middle" fill="${iconColor}" font-size="12" font-family="sans-serif">ไม่พบรูป</text>
                </svg>
            `)}`;
        }
        
        // Loading placeholder with subtle pattern
        return `data:image/svg+xml,${encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="${bgColor}"/>
                <rect x="35" y="30" width="30" height="25" rx="2" fill="${iconColor}" opacity="0.3"/>
                <circle cx="42" cy="38" r="3" fill="${iconColor}" opacity="0.3"/>
                <polygon points="35,55 50,42 65,55" fill="${iconColor}" opacity="0.3"/>
                <polygon points="45,55 55,47 65,55" fill="${iconColor}" opacity="0.2"/>
            </svg>
        `)}`;
    }

    /**
     * Force load all remaining lazy images
     */
    loadAll() {
        const images = document.querySelectorAll(`img.${this.options.placeholderClass}`);
        images.forEach(img => {
            if (this.observer) {
                this.observer.unobserve(img);
            }
            this.loadImage(img);
        });
    }

    /**
     * Destroy the lazy loader
     */
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    /**
     * Static method to create a lazy image HTML string
     * @param {Object} options - Image options
     * @returns {string} HTML string
     */
    static createImageHtml(options = {}) {
        const {
            src,
            alt = '',
            className = '',
            width,
            height,
            placeholder = null,
            aspectRatio = null
        } = options;

        const placeholderSrc = placeholder || LazyImage.getDefaultPlaceholder();
        const styleAttr = aspectRatio ? `style="aspect-ratio: ${aspectRatio};"` : '';
        const sizeAttrs = [
            width ? `width="${width}"` : '',
            height ? `height="${height}"` : ''
        ].filter(Boolean).join(' ');

        return `
            <img 
                src="${placeholderSrc}"
                data-src="${src}"
                alt="${alt}"
                class="lazy ${className}"
                loading="lazy"
                ${sizeAttrs}
                ${styleAttr}
                onerror="this.onerror=null; this.src='${LazyImage.getErrorPlaceholder()}';"
            >
        `;
    }

    /**
     * Get default placeholder SVG
     * @returns {string} Data URI
     */
    static getDefaultPlaceholder() {
        return `data:image/svg+xml,${encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="#F1F5F9"/>
                <rect x="35" y="30" width="30" height="25" rx="2" fill="#9CA3AF" opacity="0.3"/>
                <circle cx="42" cy="38" r="3" fill="#9CA3AF" opacity="0.3"/>
                <polygon points="35,55 50,42 65,55" fill="#9CA3AF" opacity="0.3"/>
            </svg>
        `)}`;
    }

    /**
     * Get error placeholder SVG
     * @returns {string} Data URI
     */
    static getErrorPlaceholder() {
        return `data:image/svg+xml,${encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="#F1F5F9"/>
                <text x="50" y="55" text-anchor="middle" fill="#9CA3AF" font-size="10" font-family="sans-serif">ไม่พบรูป</text>
            </svg>
        `)}`;
    }

    /**
     * Static method to create product image HTML
     * @param {Object} product - Product object
     * @returns {string} HTML string
     */
    static productImage(product) {
        return LazyImage.createImageHtml({
            src: product.image_url || product.image,
            alt: product.name,
            className: 'product-image',
            aspectRatio: '1'
        });
    }

    /**
     * Static method to create avatar image HTML
     * @param {Object} user - User object
     * @param {string} size - Size class (sm, md, lg)
     * @returns {string} HTML string
     */
    static avatarImage(user, size = 'md') {
        const sizeMap = { sm: 32, md: 48, lg: 64 };
        const dimension = sizeMap[size] || 48;
        
        return LazyImage.createImageHtml({
            src: user.pictureUrl || user.picture_url || user.avatar,
            alt: user.displayName || user.name || 'User',
            className: `avatar avatar-${size}`,
            width: dimension,
            height: dimension
        });
    }
}

// Create global instance
window.lazyImage = new LazyImage();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LazyImage;
}

// Make class available globally
window.LazyImage = LazyImage;
