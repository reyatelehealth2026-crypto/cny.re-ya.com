/**
 * LINE Flex Message Visual Preview
 * ใช้แสดง Flex Message เหมือน LINE จริง
 * 
 * Usage:
 * FlexPreview.render(containerId, flexJson)
 * FlexPreview.renderBubble(containerId, bubbleJson)
 */

const FlexPreview = {
    // Render flex message (bubble or carousel)
    render: function(containerId, flex) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        if (!flex) {
            container.innerHTML = this.emptyState();
            return;
        }
        
        if (flex.type === 'carousel') {
            container.innerHTML = this.renderCarousel(flex.contents);
        } else if (flex.type === 'bubble') {
            container.innerHTML = `<div class="fp-carousel"><div class="fp-carousel-inner">${this.renderBubble(flex)}</div></div>`;
        } else {
            container.innerHTML = this.emptyState('รูปแบบไม่รองรับ');
        }
    },
    
    emptyState: function(msg = 'ไม่มีข้อมูล') {
        return `
            <div class="fp-empty">
                <i class="fas fa-mobile-alt"></i>
                <p>${msg}</p>
            </div>
        `;
    },
    
    renderCarousel: function(bubbles) {
        if (!bubbles || bubbles.length === 0) return this.emptyState();
        
        let html = '<div class="fp-carousel"><div class="fp-carousel-inner">';
        bubbles.forEach(bubble => {
            html += this.renderBubble(bubble);
        });
        html += '</div></div>';
        
        return html;
    },
    
    renderBubble: function(bubble) {
        if (!bubble) return '';
        
        const size = bubble.size || 'mega';
        let html = `<div class="fp-bubble fp-bubble-${size}">`;
        
        // Hero
        if (bubble.hero) {
            html += this.renderHero(bubble.hero);
        }
        
        // Header
        if (bubble.header) {
            html += `<div class="fp-header">${this.renderBox(bubble.header)}</div>`;
        }
        
        // Body
        if (bubble.body) {
            html += `<div class="fp-body">${this.renderBox(bubble.body)}</div>`;
        }
        
        // Footer
        if (bubble.footer) {
            html += `<div class="fp-footer">${this.renderBox(bubble.footer)}</div>`;
        }
        
        html += '</div>';
        return html;
    },
    
    renderHero: function(hero) {
        if (hero.type === 'image') {
            return `
                <div class="fp-hero">
                    <img src="${hero.url}" alt="" style="aspect-ratio: ${(hero.aspectRatio || '20:13').replace(':', '/')}; object-fit: ${hero.aspectMode || 'cover'};">
                </div>
            `;
        } else if (hero.type === 'box') {
            return `<div class="fp-hero">${this.renderBox(hero)}</div>`;
        }
        return '';
    },
    
    renderBox: function(box) {
        if (!box || box.type !== 'box') {
            return this.renderContent(box);
        }
        
        const layout = box.layout || 'vertical';
        const spacing = box.spacing || 'none';
        const padding = box.paddingAll || 'none';
        const bg = box.backgroundColor || 'transparent';
        const flex = box.flex !== undefined ? `flex: ${box.flex};` : '';
        
        let style = `background-color: ${bg}; ${flex}`;
        if (padding !== 'none') {
            const padMap = { xs: '4px', sm: '8px', md: '12px', lg: '16px', xl: '20px', xxl: '24px' };
            style += `padding: ${padMap[padding] || padding};`;
        }
        
        let classes = `fp-box fp-box-${layout}`;
        if (spacing !== 'none') classes += ` fp-spacing-${spacing}`;
        
        let html = `<div class="${classes}" style="${style}">`;
        
        if (box.contents) {
            box.contents.forEach(content => {
                html += this.renderContent(content);
            });
        }
        
        html += '</div>';
        return html;
    },
    
    renderContent: function(content) {
        if (!content) return '';
        
        switch (content.type) {
            case 'box':
                return this.renderBox(content);
            case 'text':
                return this.renderText(content);
            case 'image':
                return this.renderImage(content);
            case 'button':
                return this.renderButton(content);
            case 'separator':
                return '<div class="fp-separator"></div>';
            case 'spacer':
                return `<div class="fp-spacer fp-spacer-${content.size || 'md'}"></div>`;
            case 'filler':
                return '<div class="fp-filler"></div>';
            default:
                return '';
        }
    },
    
    renderText: function(text) {
        const size = text.size || 'md';
        const weight = text.weight || 'regular';
        const color = text.color || '#333333';
        const align = text.align || 'start';
        const wrap = text.wrap !== false;
        const flex = text.flex !== undefined ? `flex: ${text.flex};` : '';
        const margin = text.margin ? `margin-top: ${this.getMargin(text.margin)};` : '';
        const decoration = text.decoration || 'none';
        
        let style = `color: ${color}; text-align: ${align}; ${flex} ${margin}`;
        if (!wrap) style += 'white-space: nowrap; overflow: hidden; text-overflow: ellipsis;';
        if (decoration === 'line-through') style += 'text-decoration: line-through;';
        
        return `<div class="fp-text fp-text-${size} fp-text-${weight}" style="${style}">${this.escapeHtml(text.text || '')}</div>`;
    },
    
    renderImage: function(img) {
        const size = img.size || 'md';
        const ratio = img.aspectRatio || '1:1';
        const mode = img.aspectMode || 'cover';
        const flex = img.flex !== undefined ? `flex: ${img.flex};` : '';
        const margin = img.margin ? `margin-top: ${this.getMargin(img.margin)};` : '';
        
        return `
            <div class="fp-image fp-image-${size}" style="${flex} ${margin}">
                <img src="${img.url}" alt="" style="aspect-ratio: ${ratio.replace(':', '/')}; object-fit: ${mode};" onerror="this.src='https://via.placeholder.com/100?text=No+Image'">
            </div>
        `;
    },
    
    renderButton: function(btn) {
        const style = btn.style || 'link';
        const height = btn.height || 'md';
        const color = btn.color || '#06C755';
        const margin = btn.margin ? `margin-top: ${this.getMargin(btn.margin)};` : '';
        
        let btnStyle = margin;
        if (style === 'primary') {
            btnStyle += `background-color: ${color}; color: white;`;
        } else if (style === 'secondary') {
            btnStyle += `background-color: #e0e0e0; color: #333;`;
        } else {
            btnStyle += `color: ${color}; background: transparent;`;
        }
        
        const label = btn.action?.label || 'Button';
        
        return `<button class="fp-button fp-button-${height}" style="${btnStyle}">${this.escapeHtml(label)}</button>`;
    },
    
    getMargin: function(margin) {
        const map = { none: '0', xs: '2px', sm: '4px', md: '8px', lg: '12px', xl: '16px', xxl: '20px' };
        return map[margin] || margin;
    },
    
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// CSS Styles
const flexPreviewStyles = `
<style>
.fp-carousel {
    background: linear-gradient(135deg, #7494a5 0%, #5a7a8a 100%);
    padding: 16px;
    border-radius: 12px;
    overflow: hidden;
}
.fp-carousel-inner {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 8px;
    scroll-snap-type: x mandatory;
}
.fp-carousel-inner::-webkit-scrollbar {
    height: 6px;
}
.fp-carousel-inner::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
}
.fp-bubble {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    flex-shrink: 0;
    scroll-snap-align: start;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.fp-bubble-nano { width: 120px; }
.fp-bubble-micro { width: 160px; }
.fp-bubble-deca { width: 200px; }
.fp-bubble-hecto { width: 220px; }
.fp-bubble-kilo { width: 260px; }
.fp-bubble-mega { width: 300px; }
.fp-bubble-giga { width: 340px; }

.fp-hero img {
    width: 100%;
    display: block;
}
.fp-header {
    padding: 12px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.fp-body {
    padding: 16px;
}
.fp-footer {
    padding: 12px 16px;
    border-top: 1px solid #e2e8f0;
}

.fp-box {
    display: flex;
}
.fp-box-vertical {
    flex-direction: column;
}
.fp-box-horizontal {
    flex-direction: row;
}
.fp-box-baseline {
    flex-direction: row;
    align-items: baseline;
}
.fp-spacing-xs > * + * { margin-top: 2px; }
.fp-spacing-sm > * + * { margin-top: 4px; }
.fp-spacing-md > * + * { margin-top: 8px; }
.fp-spacing-lg > * + * { margin-top: 12px; }
.fp-spacing-xl > * + * { margin-top: 16px; }
.fp-box-horizontal.fp-spacing-xs > * + * { margin-top: 0; margin-left: 2px; }
.fp-box-horizontal.fp-spacing-sm > * + * { margin-top: 0; margin-left: 4px; }
.fp-box-horizontal.fp-spacing-md > * + * { margin-top: 0; margin-left: 8px; }
.fp-box-horizontal.fp-spacing-lg > * + * { margin-top: 0; margin-left: 12px; }
.fp-box-horizontal.fp-spacing-xl > * + * { margin-top: 0; margin-left: 16px; }

.fp-text {
    word-break: break-word;
}
.fp-text-xxs { font-size: 10px; line-height: 1.3; }
.fp-text-xs { font-size: 11px; line-height: 1.3; }
.fp-text-sm { font-size: 12px; line-height: 1.4; }
.fp-text-md { font-size: 14px; line-height: 1.4; }
.fp-text-lg { font-size: 16px; line-height: 1.4; }
.fp-text-xl { font-size: 18px; line-height: 1.4; }
.fp-text-xxl { font-size: 20px; line-height: 1.4; }
.fp-text-3xl { font-size: 24px; line-height: 1.3; }
.fp-text-4xl { font-size: 28px; line-height: 1.3; }
.fp-text-5xl { font-size: 32px; line-height: 1.3; }
.fp-text-regular { font-weight: 400; }
.fp-text-bold { font-weight: 700; }

.fp-image img {
    width: 100%;
    border-radius: 8px;
    display: block;
}
.fp-image-xxs img { max-width: 40px; }
.fp-image-xs img { max-width: 60px; }
.fp-image-sm img { max-width: 80px; }
.fp-image-md img { max-width: 100px; }
.fp-image-lg img { max-width: 140px; }
.fp-image-xl img { max-width: 180px; }
.fp-image-xxl img { max-width: 220px; }
.fp-image-3xl img { max-width: 260px; }
.fp-image-4xl img { max-width: 300px; }
.fp-image-5xl img { max-width: 340px; }
.fp-image-full img { max-width: 100%; }

.fp-button {
    width: 100%;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: opacity 0.2s;
}
.fp-button:hover {
    opacity: 0.85;
}
.fp-button-sm { padding: 8px 16px; font-size: 12px; }
.fp-button-md { padding: 12px 16px; font-size: 14px; }

.fp-separator {
    height: 1px;
    background: #e0e0e0;
    margin: 8px 0;
}
.fp-spacer-xs { height: 4px; }
.fp-spacer-sm { height: 8px; }
.fp-spacer-md { height: 16px; }
.fp-spacer-lg { height: 24px; }
.fp-spacer-xl { height: 32px; }
.fp-filler { flex: 1; }

.fp-empty {
    text-align: center;
    padding: 40px 20px;
    color: rgba(255,255,255,0.6);
}
.fp-empty i {
    font-size: 48px;
    margin-bottom: 12px;
    display: block;
}
</style>
`;

// Inject styles
if (!document.getElementById('flex-preview-styles')) {
    const styleEl = document.createElement('div');
    styleEl.id = 'flex-preview-styles';
    styleEl.innerHTML = flexPreviewStyles;
    document.head.appendChild(styleEl.firstElementChild);
}
