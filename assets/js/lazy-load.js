/**
 * Lazy Loading Module
 * - Images lazy loading with IntersectionObserver
 * - Infinite scroll for lists
 * - WebP support detection
 */

class LazyLoader {
    constructor(options = {}) {
        this.options = {
            rootMargin: options.rootMargin || '50px',
            threshold: options.threshold || 0.1,
            loadingClass: options.loadingClass || 'lazy-loading',
            loadedClass: options.loadedClass || 'lazy-loaded',
            errorClass: options.errorClass || 'lazy-error',
            selector: options.selector || '[data-lazy]',
            ...options
        };
        
        this.observer = null;
        this.supportsWebP = false;
        
        this.init();
    }
    
    async init() {
        // Check WebP support
        this.supportsWebP = await this.checkWebPSupport();
        
        // Setup IntersectionObserver
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver(
                this.handleIntersection.bind(this),
                {
                    rootMargin: this.options.rootMargin,
                    threshold: this.options.threshold
                }
            );
            
            this.observe();
        } else {
            // Fallback for older browsers
            this.loadAllImages();
        }
    }
    
    observe() {
        const elements = document.querySelectorAll(this.options.selector);
        elements.forEach(el => {
            if (!el.classList.contains(this.options.loadedClass)) {
                this.observer.observe(el);
            }
        });
    }
    
    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                this.loadElement(entry.target);
                this.observer.unobserve(entry.target);
            }
        });
    }
    
    loadElement(element) {
        element.classList.add(this.options.loadingClass);
        
        if (element.tagName === 'IMG') {
            this.loadImage(element);
        } else if (element.tagName === 'VIDEO') {
            this.loadVideo(element);
        } else if (element.dataset.lazySrc) {
            // Background image
            this.loadBackgroundImage(element);
        }
    }
    
    loadImage(img) {
        const src = this.getOptimalSrc(img);
        const srcset = img.dataset.lazySrcset;
        
        // Create temp image to preload
        const tempImg = new Image();
        
        tempImg.onload = () => {
            img.src = src;
            if (srcset) img.srcset = srcset;
            img.classList.remove(this.options.loadingClass);
            img.classList.add(this.options.loadedClass);
            img.removeAttribute('data-lazy');
            img.removeAttribute('data-lazy-src');
            img.removeAttribute('data-lazy-webp');
            img.removeAttribute('data-lazy-srcset');
            
            // Trigger custom event
            img.dispatchEvent(new CustomEvent('lazyloaded'));
        };
        
        tempImg.onerror = () => {
            img.classList.remove(this.options.loadingClass);
            img.classList.add(this.options.errorClass);
            
            // Try fallback src
            if (img.dataset.lazyFallback) {
                img.src = img.dataset.lazyFallback;
            }
        };
        
        tempImg.src = src;
    }
    
    loadVideo(video) {
        const src = video.dataset.lazySrc;
        const poster = video.dataset.lazyPoster;
        
        if (poster) video.poster = poster;
        if (src) video.src = src;
        
        // Load source elements
        const sources = video.querySelectorAll('source[data-lazy-src]');
        sources.forEach(source => {
            source.src = source.dataset.lazySrc;
            source.removeAttribute('data-lazy-src');
        });
        
        video.load();
        video.classList.remove(this.options.loadingClass);
        video.classList.add(this.options.loadedClass);
    }
    
    loadBackgroundImage(element) {
        const src = this.getOptimalSrc(element);
        
        const tempImg = new Image();
        tempImg.onload = () => {
            element.style.backgroundImage = `url('${src}')`;
            element.classList.remove(this.options.loadingClass);
            element.classList.add(this.options.loadedClass);
        };
        
        tempImg.onerror = () => {
            element.classList.remove(this.options.loadingClass);
            element.classList.add(this.options.errorClass);
        };
        
        tempImg.src = src;
    }
    
    getOptimalSrc(element) {
        // Prefer WebP if supported
        if (this.supportsWebP && element.dataset.lazyWebp) {
            return element.dataset.lazyWebp;
        }
        return element.dataset.lazySrc || element.dataset.lazy;
    }
    
    async checkWebPSupport() {
        return new Promise(resolve => {
            const webP = new Image();
            webP.onload = webP.onerror = () => {
                resolve(webP.height === 2);
            };
            webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
        });
    }
    
    loadAllImages() {
        const elements = document.querySelectorAll(this.options.selector);
        elements.forEach(el => this.loadElement(el));
    }
    
    // Refresh observer for dynamically added elements
    refresh() {
        this.observe();
    }
    
    // Destroy observer
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }
    }
}

/**
 * Infinite Scroll Module
 */
class InfiniteScroll {
    constructor(options = {}) {
        this.options = {
            container: options.container || '#listContainer',
            loadMoreBtn: options.loadMoreBtn || '#loadMoreBtn',
            loader: options.loader || '#infiniteLoader',
            itemSelector: options.itemSelector || '.list-item',
            threshold: options.threshold || 200,
            pageParam: options.pageParam || 'page',
            perPage: options.perPage || 20,
            apiUrl: options.apiUrl || '',
            onLoad: options.onLoad || null,
            onRender: options.onRender || null,
            autoLoad: options.autoLoad !== false,
            ...options
        };
        
        this.page = 1;
        this.loading = false;
        this.hasMore = true;
        this.container = null;
        this.loader = null;
        
        this.init();
    }
    
    init() {
        this.container = document.querySelector(this.options.container);
        this.loader = document.querySelector(this.options.loader);
        
        if (!this.container) {
            console.warn('InfiniteScroll: Container not found');
            return;
        }
        
        if (this.options.autoLoad) {
            this.setupScrollListener();
        }
        
        // Manual load more button
        const loadMoreBtn = document.querySelector(this.options.loadMoreBtn);
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => this.loadMore());
        }
    }
    
    setupScrollListener() {
        let ticking = false;
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    this.checkScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });
    }
    
    checkScroll() {
        if (this.loading || !this.hasMore) return;
        
        const containerRect = this.container.getBoundingClientRect();
        const containerBottom = containerRect.bottom;
        const windowHeight = window.innerHeight;
        
        if (containerBottom - windowHeight < this.options.threshold) {
            this.loadMore();
        }
    }
    
    async loadMore() {
        if (this.loading || !this.hasMore) return;
        
        this.loading = true;
        this.showLoader();
        
        try {
            const url = new URL(this.options.apiUrl, window.location.origin);
            url.searchParams.set(this.options.pageParam, this.page + 1);
            url.searchParams.set('per_page', this.options.perPage);
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.data && data.data.length > 0) {
                this.page++;
                
                if (this.options.onLoad) {
                    this.options.onLoad(data.data);
                }
                
                if (this.options.onRender) {
                    const html = this.options.onRender(data.data);
                    this.container.insertAdjacentHTML('beforeend', html);
                }
                
                // Check if more data available
                this.hasMore = data.data.length >= this.options.perPage;
                
                // Refresh lazy loader for new images
                if (window.lazyLoader) {
                    window.lazyLoader.refresh();
                }
            } else {
                this.hasMore = false;
            }
        } catch (error) {
            console.error('InfiniteScroll error:', error);
        } finally {
            this.loading = false;
            this.hideLoader();
        }
    }
    
    showLoader() {
        if (this.loader) {
            this.loader.classList.remove('hidden');
        }
    }
    
    hideLoader() {
        if (this.loader) {
            this.loader.classList.add('hidden');
        }
        
        // Hide load more button if no more data
        if (!this.hasMore) {
            const loadMoreBtn = document.querySelector(this.options.loadMoreBtn);
            if (loadMoreBtn) {
                loadMoreBtn.classList.add('hidden');
            }
        }
    }
    
    reset() {
        this.page = 1;
        this.hasMore = true;
        this.loading = false;
        this.container.innerHTML = '';
    }
}

/**
 * Image Optimizer Helper
 * Generate optimized image HTML with lazy loading
 */
const ImageHelper = {
    /**
     * Generate lazy load image HTML
     */
    lazyImage(src, alt = '', options = {}) {
        const {
            width = '',
            height = '',
            className = '',
            webpSrc = null,
            thumbnail = null,
            sizes = '100vw'
        } = options;
        
        // Generate WebP path if not provided
        const webp = webpSrc || src.replace(/\.(jpg|jpeg|png)$/i, '.webp');
        
        // Placeholder
        const placeholder = thumbnail || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
        
        return `<img 
            src="${placeholder}"
            data-lazy
            data-lazy-src="${src}"
            data-lazy-webp="${webp}"
            alt="${alt}"
            ${width ? `width="${width}"` : ''}
            ${height ? `height="${height}"` : ''}
            class="lazy-image ${className}"
            loading="lazy"
        />`;
    },
    
    /**
     * Generate responsive image with srcset
     */
    responsiveImage(baseSrc, alt = '', options = {}) {
        const {
            sizes = [320, 640, 960, 1200],
            defaultSize = 640,
            className = ''
        } = options;
        
        const pathInfo = baseSrc.match(/(.+)\.(\w+)$/);
        if (!pathInfo) return this.lazyImage(baseSrc, alt, options);
        
        const [, basePath, ext] = pathInfo;
        
        const srcset = sizes.map(size => `${basePath}_${size}w.${ext} ${size}w`).join(', ');
        const defaultSrc = `${basePath}_${defaultSize}w.${ext}`;
        
        return `<img 
            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
            data-lazy
            data-lazy-src="${defaultSrc}"
            data-lazy-srcset="${srcset}"
            sizes="(max-width: 640px) 100vw, 640px"
            alt="${alt}"
            class="lazy-image ${className}"
            loading="lazy"
        />`;
    }
};

// CSS for lazy loading
const lazyStyles = `
<style>
.lazy-image {
    opacity: 0;
    transition: opacity 0.3s ease;
}
.lazy-image.lazy-loaded {
    opacity: 1;
}
.lazy-image.lazy-loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
.lazy-image.lazy-error {
    background: #f0f0f0;
}
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
`;

// Auto-inject styles
if (typeof document !== 'undefined') {
    document.head.insertAdjacentHTML('beforeend', lazyStyles);
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.lazyLoader = new LazyLoader();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { LazyLoader, InfiniteScroll, ImageHelper };
}
