<?php
/**
 * Flex Message Builder - สร้าง Flex Message แบบ Visual พร้อม Live Preview
 */
require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Flex Message Builder';

require_once 'includes/header.php';
?>

<!-- Load FlexPreview Component -->
<script src="assets/js/flex-preview.js"></script>

<style>
/* LINE Flex Message Styles */
.line-bubble {
    background: white;
    border-radius: 18px;
    overflow: hidden;
    max-width: 300px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
.line-hero img {
    width: 100%;
    object-fit: cover;
}
.line-body, .line-footer {
    padding: 16px;
}
.line-footer {
    padding-top: 0;
}
.line-box-vertical {
    display: flex;
    flex-direction: column;
}
.line-box-horizontal {
    display: flex;
    flex-direction: row;
    align-items: center;
}
.line-box-baseline {
    display: flex;
    flex-direction: row;
    align-items: baseline;
}
.line-text {
    margin: 0;
    word-wrap: break-word;
}
.line-text-xxs { font-size: 11px; }
.line-text-xs { font-size: 12px; }
.line-text-sm { font-size: 13px; }
.line-text-md { font-size: 14px; }
.line-text-lg { font-size: 16px; }
.line-text-xl { font-size: 18px; }
.line-text-xxl { font-size: 22px; }
.line-text-3xl { font-size: 26px; }
.line-text-4xl { font-size: 32px; }
.line-text-5xl { font-size: 40px; }
.line-text-bold { font-weight: 700; }
.line-text-regular { font-weight: 400; }
.line-button {
    display: block;
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
}
.line-button-primary {
    background: #00B900;
    color: white;
}
.line-button-secondary {
    background: #E8E8E8;
    color: #333;
}
.line-button-link {
    background: transparent;
    color: #00B900;
}
.line-button-sm { padding: 8px; font-size: 13px; }
.line-separator {
    height: 1px;
    background: #E8E8E8;
}
.line-spacer-xs { height: 4px; }
.line-spacer-sm { height: 8px; }
.line-spacer-md { height: 12px; }
.line-spacer-lg { height: 16px; }
.line-spacer-xl { height: 20px; }
.line-spacer-xxl { height: 24px; }
.line-icon {
    width: 16px;
    height: 16px;
    object-fit: contain;
}
.line-margin-none { margin-top: 0; }
.line-margin-xs { margin-top: 4px; }
.line-margin-sm { margin-top: 8px; }
.line-margin-md { margin-top: 12px; }
.line-margin-lg { margin-top: 16px; }
.line-margin-xl { margin-top: 20px; }
.line-margin-xxl { margin-top: 24px; }
.line-spacing-xs > *:not(:first-child) { margin-top: 4px; }
.line-spacing-sm > *:not(:first-child) { margin-top: 8px; }
.line-spacing-md > *:not(:first-child) { margin-top: 12px; }
.line-spacing-lg > *:not(:first-child) { margin-top: 16px; }
.line-spacing-xl > *:not(:first-child) { margin-top: 20px; }
.line-h-spacing-xs > *:not(:first-child) { margin-left: 4px; }
.line-h-spacing-sm > *:not(:first-child) { margin-left: 8px; }
.line-h-spacing-md > *:not(:first-child) { margin-left: 12px; }
.line-h-spacing-lg > *:not(:first-child) { margin-left: 16px; }
.line-carousel {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 8px;
}
.line-filler { flex: 1; }
.flex-1 { flex: 1; }
.flex-2 { flex: 2; }
.flex-3 { flex: 3; }
</style>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Builder -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">สร้าง Flex Message</h3>
        
        <!-- Template Selection -->
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">เลือก Template</label>
            <select id="templateSelect" onchange="loadTemplate()" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">-- เลือก Template --</option>
                <option value="bubble">Bubble (การ์ดเดี่ยว)</option>
                <option value="carousel">Carousel (หลายการ์ด)</option>
                <option value="product">Product Card</option>
                <option value="receipt">Receipt</option>
                <option value="notification">Notification</option>
                <option value="restaurant">Restaurant</option>
                <option value="shopping">Shopping</option>
            </select>
        </div>
        
        <!-- JSON Editor -->
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Flex Message JSON</label>
            <textarea id="flexJson" rows="20" oninput="livePreview()" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 font-mono text-sm" placeholder='{"type": "bubble", "body": {...}}'></textarea>
            <p id="jsonError" class="text-red-500 text-sm mt-1 hidden"></p>
        </div>
        
        <div class="flex space-x-2">
            <button onclick="formatJson()" class="flex-1 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-align-left mr-2"></i>Format
            </button>
            <button onclick="copyJson()" class="flex-1 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-copy mr-2"></i>คัดลอก
            </button>
            <button onclick="saveAsTemplate()" class="flex-1 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                <i class="fas fa-save mr-2"></i>บันทึก
            </button>
        </div>
    </div>
    
    <!-- Live Preview -->
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Live Preview</h3>
            <span class="text-xs text-gray-500">อัพเดทอัตโนมัติ</span>
        </div>
        
        <!-- Phone Frame -->
        <div class="flex justify-center">
            <div class="relative">
                <!-- Phone mockup -->
                <div class="w-80 bg-gray-800 rounded-[3rem] p-3 shadow-xl">
                    <div class="bg-white rounded-[2.5rem] overflow-hidden">
                        <!-- Status bar -->
                        <div class="bg-gray-100 px-6 py-2 flex justify-between items-center text-xs">
                            <span>9:41</span>
                            <div class="flex space-x-1">
                                <i class="fas fa-signal"></i>
                                <i class="fas fa-wifi"></i>
                                <i class="fas fa-battery-full"></i>
                            </div>
                        </div>
                        <!-- LINE header -->
                        <div class="bg-green-500 text-white px-4 py-3 flex items-center">
                            <i class="fas fa-chevron-left mr-3"></i>
                            <div class="w-8 h-8 bg-white rounded-full mr-2"></div>
                            <span class="font-medium">LINE OA</span>
                        </div>
                        <!-- Chat area -->
                        <div class="bg-[#7494A5] min-h-[400px] max-h-[500px] overflow-y-auto p-4" id="previewContainer">
                            <div class="flex justify-start">
                                <div id="flexPreview" class="max-w-[85%]">
                                    <div class="text-white text-center py-20 text-sm opacity-70">
                                        <i class="fas fa-mobile-alt text-4xl mb-3 block"></i>
                                        เลือก Template หรือพิมพ์ JSON
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Input bar -->
                        <div class="bg-gray-100 px-4 py-3 flex items-center space-x-3">
                            <i class="fas fa-plus-circle text-gray-400 text-xl"></i>
                            <div class="flex-1 bg-white rounded-full px-4 py-2 text-sm text-gray-400">Aa</div>
                            <i class="fas fa-microphone text-gray-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const templates = {
    bubble: {
        "type": "bubble",
        "body": {
            "type": "box",
            "layout": "vertical",
            "contents": [
                {"type": "text", "text": "หัวข้อ", "weight": "bold", "size": "xl"},
                {"type": "text", "text": "รายละเอียดข้อความ", "size": "sm", "color": "#666666", "margin": "md", "wrap": true}
            ]
        },
        "footer": {
            "type": "box",
            "layout": "vertical",
            "spacing": "sm",
            "contents": [
                {"type": "button", "action": {"type": "uri", "label": "ดูเพิ่มเติม", "uri": "https://example.com"}, "style": "primary"}
            ]
        }
    },
    carousel: {
        "type": "carousel",
        "contents": [
            {
                "type": "bubble",
                "hero": {"type": "image", "url": "https://via.placeholder.com/400x200/00B900/FFFFFF?text=Item+1", "size": "full", "aspectRatio": "2:1", "aspectMode": "cover"},
                "body": {"type": "box", "layout": "vertical", "contents": [{"type": "text", "text": "สินค้า 1", "weight": "bold", "size": "lg"}]},
                "footer": {"type": "box", "layout": "vertical", "contents": [{"type": "button", "action": {"type": "message", "label": "เลือก", "text": "เลือกสินค้า 1"}, "style": "primary"}]}
            },
            {
                "type": "bubble",
                "hero": {"type": "image", "url": "https://via.placeholder.com/400x200/3B82F6/FFFFFF?text=Item+2", "size": "full", "aspectRatio": "2:1", "aspectMode": "cover"},
                "body": {"type": "box", "layout": "vertical", "contents": [{"type": "text", "text": "สินค้า 2", "weight": "bold", "size": "lg"}]},
                "footer": {"type": "box", "layout": "vertical", "contents": [{"type": "button", "action": {"type": "message", "label": "เลือก", "text": "เลือกสินค้า 2"}, "style": "primary"}]}
            }
        ]
    },
    product: {
        "type": "bubble",
        "hero": {"type": "image", "url": "https://via.placeholder.com/800x600/F3F4F6/333333?text=Product+Image", "size": "full", "aspectRatio": "4:3", "aspectMode": "cover"},
        "body": {
            "type": "box", "layout": "vertical",
            "contents": [
                {"type": "text", "text": "ชื่อสินค้า", "weight": "bold", "size": "xl"},
                {"type": "box", "layout": "baseline", "margin": "md", "contents": [
                    {"type": "text", "text": "฿", "size": "sm", "color": "#00B900"},
                    {"type": "text", "text": "1,299", "size": "xl", "color": "#00B900", "weight": "bold"},
                    {"type": "text", "text": "฿1,599", "size": "sm", "color": "#AAAAAA", "decoration": "line-through", "margin": "sm"}
                ]},
                {"type": "text", "text": "รายละเอียดสินค้าที่น่าสนใจ สามารถใส่ข้อความได้ยาวๆ", "size": "sm", "color": "#666666", "margin": "md", "wrap": true}
            ]
        },
        "footer": {
            "type": "box", "layout": "horizontal", "spacing": "sm",
            "contents": [
                {"type": "button", "action": {"type": "uri", "label": "🛒 ซื้อเลย", "uri": "https://example.com"}, "style": "primary", "flex": 2},
                {"type": "button", "action": {"type": "message", "label": "💬", "text": "สอบถามสินค้า"}, "style": "secondary", "flex": 1}
            ]
        }
    },
    receipt: {
        "type": "bubble",
        "body": {
            "type": "box", "layout": "vertical",
            "contents": [
                {"type": "text", "text": "🧾 ใบเสร็จ", "weight": "bold", "size": "xl", "color": "#00B900"},
                {"type": "text", "text": "#12345678", "size": "xs", "color": "#AAAAAA"},
                {"type": "separator", "margin": "lg"},
                {"type": "box", "layout": "vertical", "margin": "lg", "spacing": "sm", "contents": [
                    {"type": "box", "layout": "horizontal", "contents": [
                        {"type": "text", "text": "สินค้า A x2", "size": "sm", "color": "#555555", "flex": 0},
                        {"type": "text", "text": "฿200", "size": "sm", "color": "#111111", "align": "end"}
                    ]},
                    {"type": "box", "layout": "horizontal", "contents": [
                        {"type": "text", "text": "สินค้า B x1", "size": "sm", "color": "#555555", "flex": 0},
                        {"type": "text", "text": "฿150", "size": "sm", "color": "#111111", "align": "end"}
                    ]},
                    {"type": "box", "layout": "horizontal", "contents": [
                        {"type": "text", "text": "ค่าจัดส่ง", "size": "sm", "color": "#555555", "flex": 0},
                        {"type": "text", "text": "฿50", "size": "sm", "color": "#111111", "align": "end"}
                    ]}
                ]},
                {"type": "separator", "margin": "lg"},
                {"type": "box", "layout": "horizontal", "margin": "lg", "contents": [
                    {"type": "text", "text": "รวมทั้งหมด", "size": "md", "weight": "bold"},
                    {"type": "text", "text": "฿400", "size": "lg", "weight": "bold", "color": "#00B900", "align": "end"}
                ]}
            ]
        },
        "footer": {
            "type": "box", "layout": "vertical",
            "contents": [
                {"type": "button", "action": {"type": "uri", "label": "ดูรายละเอียด", "uri": "https://example.com"}, "style": "primary", "height": "sm"}
            ]
        }
    },
    notification: {
        "type": "bubble",
        "size": "kilo",
        "body": {
            "type": "box", "layout": "vertical",
            "contents": [
                {"type": "box", "layout": "horizontal", "contents": [
                    {"type": "text", "text": "🔔", "size": "xl", "flex": 0},
                    {"type": "text", "text": "แจ้งเตือน", "weight": "bold", "size": "lg", "margin": "md", "flex": 1}
                ]},
                {"type": "text", "text": "คุณมีข้อความใหม่รอการตอบกลับ กรุณาตรวจสอบ", "size": "sm", "color": "#666666", "margin": "lg", "wrap": true},
                {"type": "text", "text": "วันนี้ 14:30", "size": "xs", "color": "#AAAAAA", "margin": "md"}
            ]
        },
        "footer": {
            "type": "box", "layout": "vertical",
            "contents": [
                {"type": "button", "action": {"type": "uri", "label": "ดูเลย", "uri": "https://example.com"}, "style": "link", "height": "sm"}
            ]
        }
    },
    restaurant: {
        "type": "bubble",
        "hero": {"type": "image", "url": "https://via.placeholder.com/800x400/FF6B35/FFFFFF?text=Restaurant", "size": "full", "aspectRatio": "2:1", "aspectMode": "cover"},
        "body": {
            "type": "box", "layout": "vertical",
            "contents": [
                {"type": "text", "text": "ร้านอาหารอร่อย", "weight": "bold", "size": "xl"},
                {"type": "box", "layout": "baseline", "margin": "md", "contents": [
                    {"type": "icon", "url": "https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gold_star_28.png", "size": "sm"},
                    {"type": "icon", "url": "https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gold_star_28.png", "size": "sm"},
                    {"type": "icon", "url": "https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gold_star_28.png", "size": "sm"},
                    {"type": "icon", "url": "https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gold_star_28.png", "size": "sm"},
                    {"type": "icon", "url": "https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gray_star_28.png", "size": "sm"},
                    {"type": "text", "text": "4.0", "size": "sm", "color": "#999999", "margin": "md"}
                ]},
                {"type": "box", "layout": "vertical", "margin": "lg", "spacing": "sm", "contents": [
                    {"type": "box", "layout": "baseline", "contents": [
                        {"type": "text", "text": "📍", "flex": 0},
                        {"type": "text", "text": "สยามพารากอน ชั้น G", "size": "sm", "color": "#666666", "margin": "sm", "wrap": true}
                    ]},
                    {"type": "box", "layout": "baseline", "contents": [
                        {"type": "text", "text": "⏰", "flex": 0},
                        {"type": "text", "text": "10:00 - 22:00", "size": "sm", "color": "#666666", "margin": "sm"}
                    ]}
                ]}
            ]
        },
        "footer": {
            "type": "box", "layout": "horizontal", "spacing": "sm",
            "contents": [
                {"type": "button", "action": {"type": "uri", "label": "โทร", "uri": "tel:021234567"}, "style": "secondary", "height": "sm"},
                {"type": "button", "action": {"type": "uri", "label": "นำทาง", "uri": "https://maps.google.com"}, "style": "primary", "height": "sm"}
            ]
        }
    },
    shopping: {
        "type": "bubble",
        "body": {
            "type": "box", "layout": "vertical",
            "contents": [
                {"type": "text", "text": "🛍️ โปรโมชั่นพิเศษ!", "weight": "bold", "size": "lg", "color": "#E91E63"},
                {"type": "text", "text": "ลดสูงสุด 50%", "size": "xxl", "weight": "bold", "margin": "md"},
                {"type": "text", "text": "เฉพาะวันนี้ - 31 ธ.ค. 67", "size": "sm", "color": "#999999", "margin": "sm"},
                {"type": "box", "layout": "vertical", "margin": "lg", "backgroundColor": "#FFF3E0", "cornerRadius": "md", "paddingAll": "md", "contents": [
                    {"type": "text", "text": "🎁 ใช้โค้ด: SALE50", "size": "md", "weight": "bold", "color": "#FF6F00", "align": "center"}
                ]}
            ]
        },
        "footer": {
            "type": "box", "layout": "vertical",
            "contents": [
                {"type": "button", "action": {"type": "uri", "label": "ช้อปเลย!", "uri": "https://example.com"}, "style": "primary", "color": "#E91E63"}
            ]
        }
    }
};

// Render Flex Message to HTML
function renderFlex(json) {
    if (!json) return '';
    
    if (json.type === 'carousel') {
        return `<div class="line-carousel">${json.contents.map(b => renderBubble(b)).join('')}</div>`;
    } else if (json.type === 'bubble') {
        return renderBubble(json);
    }
    return '<div class="text-red-500 text-sm">Invalid Flex type</div>';
}

function renderBubble(bubble) {
    let html = `<div class="line-bubble" style="${bubble.styles?.body?.backgroundColor ? 'background:'+bubble.styles.body.backgroundColor : ''}">`;
    
    if (bubble.hero) {
        html += renderHero(bubble.hero);
    }
    if (bubble.body) {
        html += `<div class="line-body">${renderBox(bubble.body)}</div>`;
    }
    if (bubble.footer) {
        html += `<div class="line-footer">${renderBox(bubble.footer)}</div>`;
    }
    
    html += '</div>';
    return html;
}

function renderHero(hero) {
    if (hero.type === 'image') {
        const ratio = hero.aspectRatio || '1:1';
        const [w, h] = ratio.split(':').map(Number);
        const paddingTop = (h / w * 100) + '%';
        return `<div class="line-hero" style="position:relative;padding-top:${paddingTop}">
            <img src="${hero.url}" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:${hero.aspectMode || 'cover'}">
        </div>`;
    }
    return '';
}

function renderBox(box) {
    const layout = box.layout || 'vertical';
    const spacing = box.spacing ? `line-${layout === 'horizontal' ? 'h-' : ''}spacing-${box.spacing}` : '';
    const margin = box.margin ? `line-margin-${box.margin}` : '';
    const bg = box.backgroundColor ? `background:${box.backgroundColor};` : '';
    const corner = box.cornerRadius ? `border-radius:${box.cornerRadius === 'md' ? '8px' : box.cornerRadius};` : '';
    const padding = box.paddingAll ? `padding:${box.paddingAll === 'md' ? '12px' : box.paddingAll};` : '';
    
    let html = `<div class="line-box-${layout} ${spacing} ${margin}" style="${bg}${corner}${padding}">`;
    
    if (box.contents) {
        box.contents.forEach(item => {
            html += renderContent(item);
        });
    }
    
    html += '</div>';
    return html;
}

function renderContent(item) {
    switch (item.type) {
        case 'box':
            return renderBox(item);
        case 'text':
            return renderText(item);
        case 'button':
            return renderButton(item);
        case 'image':
            return renderImage(item);
        case 'icon':
            return renderIcon(item);
        case 'separator':
            return renderSeparator(item);
        case 'spacer':
            return renderSpacer(item);
        case 'filler':
            return '<div class="line-filler"></div>';
        default:
            return '';
    }
}

function renderText(text) {
    const size = text.size ? `line-text-${text.size}` : 'line-text-md';
    const weight = text.weight === 'bold' ? 'line-text-bold' : '';
    const margin = text.margin ? `line-margin-${text.margin}` : '';
    const color = text.color || '#333333';
    const align = text.align || 'left';
    const flex = text.flex !== undefined ? `flex:${text.flex};` : '';
    const decoration = text.decoration === 'line-through' ? 'text-decoration:line-through;' : '';
    const wrap = text.wrap ? '' : 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
    
    return `<p class="line-text ${size} ${weight} ${margin}" style="color:${color};text-align:${align};${flex}${decoration}${wrap}">${escapeHtml(text.text || '')}</p>`;
}

function renderButton(btn) {
    const style = btn.style || 'link';
    const height = btn.height === 'sm' ? 'line-button-sm' : '';
    const margin = btn.margin ? `line-margin-${btn.margin}` : '';
    const flex = btn.flex !== undefined ? `flex:${btn.flex};` : '';
    const color = btn.color ? `background:${btn.color};` : '';
    
    return `<button class="line-button line-button-${style} ${height} ${margin}" style="${flex}${color}">${escapeHtml(btn.action?.label || 'Button')}</button>`;
}

function renderImage(img) {
    const margin = img.margin ? `line-margin-${img.margin}` : '';
    const size = img.size || 'md';
    const sizes = {xxs:'40px',xs:'60px',sm:'80px',md:'100px',lg:'120px',xl:'150px',xxl:'200px',full:'100%'};
    const w = sizes[size] || size;
    
    return `<img src="${img.url}" class="${margin}" style="width:${w};height:auto;object-fit:contain;">`;
}

function renderIcon(icon) {
    const size = icon.size || 'md';
    const sizes = {xxs:'12px',xs:'14px',sm:'16px',md:'18px',lg:'20px',xl:'24px',xxl:'28px'};
    const s = sizes[size] || '18px';
    const margin = icon.margin ? `margin-left:${icon.margin === 'md' ? '8px' : '4px'};` : '';
    
    return `<img src="${icon.url}" class="line-icon" style="width:${s};height:${s};${margin}">`;
}

function renderSeparator(sep) {
    const margin = sep.margin ? `line-margin-${sep.margin}` : '';
    return `<div class="line-separator ${margin}"></div>`;
}

function renderSpacer(spacer) {
    const size = spacer.size || 'md';
    return `<div class="line-spacer-${size}"></div>`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Live Preview
function livePreview() {
    const jsonStr = document.getElementById('flexJson').value.trim();
    const errorEl = document.getElementById('jsonError');
    
    if (!jsonStr) {
        document.getElementById('previewContainer').innerHTML = `
            <div class="fp-carousel">
                <div class="fp-carousel-inner">
                    <div class="fp-empty">
                        <i class="fas fa-mobile-alt"></i>
                        <p>เลือก Template หรือพิมพ์ JSON</p>
                    </div>
                </div>
            </div>`;
        errorEl.classList.add('hidden');
        return;
    }
    
    try {
        const json = JSON.parse(jsonStr);
        // Use FlexPreview component
        FlexPreview.render('previewContainer', json);
        errorEl.classList.add('hidden');
    } catch (e) {
        errorEl.textContent = 'JSON Error: ' + e.message;
        errorEl.classList.remove('hidden');
    }
}

function loadTemplate() {
    const selected = document.getElementById('templateSelect').value;
    if (selected && templates[selected]) {
        document.getElementById('flexJson').value = JSON.stringify(templates[selected], null, 2);
        livePreview();
    }
}

function formatJson() {
    try {
        const json = JSON.parse(document.getElementById('flexJson').value);
        document.getElementById('flexJson').value = JSON.stringify(json, null, 2);
        showToast('Formatted!');
    } catch (e) {
        showToast('JSON ไม่ถูกต้อง', 'error');
    }
}

function copyJson() {
    navigator.clipboard.writeText(document.getElementById('flexJson').value).then(() => showToast('คัดลอกแล้ว!'));
}

function saveAsTemplate() {
    const json = document.getElementById('flexJson').value;
    try {
        JSON.parse(json);
        const name = prompt('ตั้งชื่อ Template:');
        if (name) {
            fetch('api/save_template.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({name: name, content: json, message_type: 'flex', category: 'Flex Message'})
            }).then(r => r.json()).then(data => {
                if (data.success) showToast('บันทึกสำเร็จ!');
                else showToast('เกิดข้อผิดพลาด', 'error');
            });
        }
    } catch (e) {
        showToast('JSON ไม่ถูกต้อง', 'error');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', livePreview);
</script>

<?php require_once 'includes/footer.php'; ?>
