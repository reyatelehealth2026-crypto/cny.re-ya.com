            </div><!-- /.content-area -->
        </div><!-- /.main-content -->
    </div><!-- /.app-layout -->

    <!-- AI Assistant Panel (Slide-in from right) -->
    <div id="aiAssistantPanel" class="ai-panel">
        <div class="ai-panel-content">
            <div class="ai-panel-header">
                <div class="ai-panel-title">
                    <i class="fas fa-robot"></i>
                    <span>AI Assistant</span>
                </div>
                <button onclick="closeAiAssistant()" class="ai-panel-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="ai-panel-context">
                <div class="ai-context-badge">
                    <i class="fas fa-map-marker-alt"></i>
                    <span id="aiCurrentPage">Dashboard</span>
                </div>
            </div>
            
            <div class="ai-panel-messages" id="aiPanelMessages">
                <div class="ai-welcome">
                    <div class="ai-welcome-icon">🤖</div>
                    <h4>สวัสดีครับ!</h4>
                    <p>ผมพร้อมช่วยเหลือคุณ ถามอะไรก็ได้เลย</p>
                </div>
                
                <div class="ai-quick-questions">
                    <div class="ai-quick-title">💡 คำถามแนะนำ</div>
                    <button onclick="askAi('หน้านี้ใช้ทำอะไร?')">หน้านี้ใช้ทำอะไร?</button>
                    <button onclick="askAi('แนะนำวิธีใช้งาน')">แนะนำวิธีใช้งาน</button>
                    <button onclick="askAi('มีฟีเจอร์อะไรบ้าง?')">มีฟีเจอร์อะไรบ้าง?</button>
                </div>
            </div>
            
            <div class="ai-panel-input">
                <input type="text" id="aiPanelInput" placeholder="พิมพ์คำถาม..." onkeypress="if(event.key==='Enter')sendAiQuestion()">
                <button onclick="sendAiQuestion()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="aiPanelOverlay" class="ai-panel-overlay" onclick="closeAiAssistant()"></div>

    <!-- AI Assistant Panel JavaScript -->
    <script>
    // AI Panel Functions
    window.openAiAssistant = function() {
        document.getElementById('aiAssistantPanel').classList.add('open');
        document.getElementById('aiPanelOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
        // Update current page
        const pageTitle = document.querySelector('.page-title')?.textContent || 'Dashboard';
        document.getElementById('aiCurrentPage').textContent = pageTitle;
    };
    
    window.closeAiAssistant = function() {
        document.getElementById('aiAssistantPanel').classList.remove('open');
        document.getElementById('aiPanelOverlay').classList.remove('open');
        document.body.style.overflow = '';
    };
    
    // Get current page context for AI
    function getCurrentPageContext() {
        const path = window.location.pathname;
        const pageTitle = document.querySelector('.page-title')?.textContent || 'Dashboard';
        return { path, pageTitle };
    }
    
    // Add message to chat
    function addAiMessage(content, isUser = false) {
        const messagesContainer = document.getElementById('aiPanelMessages');
        const welcomeEl = messagesContainer.querySelector('.ai-welcome');
        if (welcomeEl) welcomeEl.style.display = 'none';
        
        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-message ${isUser ? 'ai-message-user' : 'ai-message-bot'}`;
        msgDiv.innerHTML = `<div class="ai-bubble">${content}</div>`;
        messagesContainer.appendChild(msgDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Show typing indicator
    function showTypingIndicator() {
        const messagesContainer = document.getElementById('aiPanelMessages');
        const typingDiv = document.createElement('div');
        typingDiv.id = 'aiTyping';
        typingDiv.className = 'ai-message ai-message-bot';
        typingDiv.innerHTML = '<div class="ai-bubble"><i class="fas fa-circle-notch fa-spin"></i> กำลังคิด...</div>';
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function removeTypingIndicator() {
        const typing = document.getElementById('aiTyping');
        if (typing) typing.remove();
    }
    
    // Ask AI with quick question
    window.askAi = function(question) {
        addAiMessage(question, true);
        callAiApi(question);
    };
    
    // Send user input
    window.sendAiQuestion = function() {
        const input = document.getElementById('aiPanelInput');
        const question = input.value.trim();
        if (!question) return;
        
        input.value = '';
        addAiMessage(question, true);
        callAiApi(question);
    };
    
    // Call AI API (using sidebar-assistant for context-aware responses)
    async function callAiApi(question) {
        showTypingIndicator();
        
        const context = getCurrentPageContext();
        
        try {
            const response = await fetch('/api/sidebar-assistant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: question,
                    page: context.path,
                    pageTitle: context.pageTitle
                })
            });
            
            removeTypingIndicator();
            
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.message) {
                    // Format message with links
                    let msg = data.message;
                    msg = msg.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-purple-600 underline hover:text-purple-800">$1</a>');
                    msg = msg.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
                    msg = msg.replace(/\n/g, '<br>');
                    
                    let sourceHtml = '';
                    if (data.ai_source === 'gemini') {
                        sourceHtml = '<div class="text-xs text-green-600 mt-2 flex items-center gap-1"><i class="fas fa-robot"></i> Gemini AI</div>';
                    } else if (data.ai_source === 'knowledge') {
                        sourceHtml = '<div class="text-xs text-blue-500 mt-2 flex items-center gap-1"><i class="fas fa-book"></i> Knowledge Base</div>';
                    } else if (data.ai_source === 'fallback') {
                        sourceHtml = '<div class="text-xs text-orange-500 mt-2 flex items-center gap-1"><i class="fas fa-info-circle"></i> คำตอบทั่วไป</div>';
                    }
                    
                    addAiMessage(msg + sourceHtml);
                } else {
                    addAiMessage('❌ ' + (data.error || 'ขออภัย ไม่สามารถตอบได้ในขณะนี้'));
                }
            } else {
                addAiMessage('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่');
            }
        } catch (error) {
            removeTypingIndicator();
            addAiMessage('❌ ไม่สามารถเชื่อมต่อ AI ได้');
        }
    }
    
    // Close panel with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAiAssistant();
        }
    });
    </script>

    <!-- Lazy Load Script -->
    <script src="<?= $baseUrl ?? '' ?>assets/js/lazy-load.js"></script>
    
    <script>
    // Toast notification
    function showToast(message, type = 'success') {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 px-5 py-3 rounded-xl text-white ${colors[type] || colors.success} shadow-xl z-50 flex items-center gap-3 animate-slide-up`;
        toast.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i><span>${message}</span>`;
        document.body.appendChild(toast);
        
        setTimeout(() => { 
            toast.style.opacity = '0'; 
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 300); 
        }, 3000);
    }

    // Confirm delete with custom modal
    function confirmDelete(message = 'คุณแน่ใจหรือไม่ที่จะลบ?') {
        return confirm(message);
    }
    
    // Format number with commas
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Format currency
    function formatCurrency(amount, symbol = '฿') {
        return symbol + formatNumber(parseFloat(amount).toFixed(0));
    }
    
    // Copy to clipboard
    function copyToClipboard(text, successMsg = 'คัดลอกแล้ว!') {
        navigator.clipboard.writeText(text).then(() => {
            showToast(successMsg, 'success');
        }).catch(() => {
            // Fallback
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast(successMsg, 'success');
        });
    }
    
    // Loading overlay
    function showLoading(message = 'กำลังโหลด...') {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-[100]';
        overlay.innerHTML = `
            <div class="bg-white rounded-2xl p-6 flex flex-col items-center gap-3">
                <div class="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-gray-600">${message}</span>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    
    function hideLoading() {
        document.getElementById('loadingOverlay')?.remove();
    }
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => { clearTimeout(timeout); func(...args); };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    </script>
    
    <!-- AI Admin Assistant Chat Widget -->
    <div id="ai-chat-widget" class="fixed bottom-6 right-6 z-50">
        <!-- Chat Toggle Button -->
        <button id="ai-chat-toggle" class="w-14 h-14 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full shadow-lg flex items-center justify-center text-white hover:scale-110 transition-transform">
            <i class="fas fa-robot text-xl"></i>
        </button>
        
        <!-- Chat Window -->
        <div id="ai-chat-window" class="hidden absolute bottom-16 right-0 w-96 bg-white rounded-2xl shadow-2xl overflow-hidden" style="max-height: 550px;">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div class="font-semibold">AI Assistant</div>
                        <div class="text-xs opacity-80">ถามอะไรก็ได้เกี่ยวกับระบบ</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="ai-chat-help" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center" title="วิธีใช้งาน">
                        <i class="fas fa-question-circle"></i>
                    </button>
                    <button id="ai-chat-close" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Messages -->
            <div id="ai-chat-messages" class="p-4 overflow-y-auto" style="height: 340px;">
                <div class="ai-message mb-3">
                    <div class="bg-gray-100 rounded-2xl rounded-tl-sm p-3 text-sm max-w-[85%]">
                        สวัสดีครับ! ผมช่วยคุณได้หลายอย่าง:<br><br>
                        📊 <b>ดูข้อมูล:</b> สรุป, ยอดขาย, ออเดอร์, สินค้า, ลูกค้า<br>
                        🚀 <b>Actions:</b> ยืนยันออเดอร์, อนุมัติสลิป, ปิดสินค้าหมด<br>
                        🚨 <b>Alerts:</b> แจ้งเตือนปัญหาต่างๆ<br>
                        🔍 <b>ค้นหา:</b> หาลูกค้า, หาออเดอร์, หาสินค้า<br><br>
                        กดปุ่ม <b>❓</b> ด้านบนเพื่อดูคำสั่งทั้งหมด 😊
                    </div>
                </div>
            </div>
            
            <!-- Input -->
            <div class="p-3 border-t bg-gray-50">
                <form id="ai-chat-form" class="flex gap-2">
                    <input type="text" id="ai-chat-input" placeholder="พิมพ์คำถาม..." 
                        class="flex-1 px-4 py-2 border rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <button type="submit" class="w-10 h-10 bg-purple-600 text-white rounded-full flex items-center justify-center hover:bg-purple-700">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <!-- Quick Buttons Row 1 -->
                <div class="flex gap-2 mt-2 overflow-x-auto pb-1">
                    <button class="ai-quick-btn px-3 py-1 bg-white border rounded-full text-xs hover:bg-purple-50 whitespace-nowrap" data-msg="แจ้งเตือน">🚨 Alerts</button>
                    <button class="ai-quick-btn px-3 py-1 bg-white border rounded-full text-xs hover:bg-purple-50 whitespace-nowrap" data-msg="สรุปวันนี้">📊 สรุป</button>
                    <button class="ai-quick-btn px-3 py-1 bg-white border rounded-full text-xs hover:bg-purple-50 whitespace-nowrap" data-msg="ออเดอร์รอดำเนินการ">📦 ออเดอร์</button>
                    <button class="ai-quick-btn px-3 py-1 bg-white border rounded-full text-xs hover:bg-purple-50 whitespace-nowrap" data-msg="สลิปรอตรวจ">🧾 สลิป</button>
                </div>
                <!-- Quick Buttons Row 2 -->
                <div class="flex gap-2 mt-1 overflow-x-auto pb-1">
                    <button class="ai-quick-btn px-3 py-1 bg-white border rounded-full text-xs hover:bg-purple-50 whitespace-nowrap" data-msg="ยอดขายวันนี้">💰 ยอดขาย</button>
                    <button class="ai-quick-btn px-3 py-1 bg-white border rounded-full text-xs hover:bg-purple-50 whitespace-nowrap" data-msg="สินค้าหมด">📦 สินค้าหมด</button>
                    <button class="ai-quick-btn px-3 py-1 bg-white border rounded-full text-xs hover:bg-purple-50 whitespace-nowrap" data-msg="top ลูกค้า">🏆 Top</button>
                    <button class="ai-quick-btn px-3 py-1 bg-white border rounded-full text-xs hover:bg-purple-50 whitespace-nowrap" data-msg="สถานะระบบ">🖥️ ระบบ</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // AI Chat Widget
    (function() {
        const toggle = document.getElementById('ai-chat-toggle');
        const chatWindow = document.getElementById('ai-chat-window');
        const closeBtn = document.getElementById('ai-chat-close');
        const helpBtn = document.getElementById('ai-chat-help');
        const form = document.getElementById('ai-chat-form');
        const input = document.getElementById('ai-chat-input');
        const messages = document.getElementById('ai-chat-messages');
        const quickBtns = document.querySelectorAll('.ai-quick-btn');
        
        if (!toggle) return;
        
        // Toggle chat
        toggle.addEventListener('click', () => {
            chatWindow.classList.toggle('hidden');
            if (!chatWindow.classList.contains('hidden')) {
                input.focus();
            }
        });
        
        closeBtn.addEventListener('click', () => {
            chatWindow.classList.add('hidden');
        });
        
        // Help button - show usage guide
        helpBtn.addEventListener('click', () => {
            const helpText = `📖 **คู่มือการใช้งาน AI Assistant**

━━━━━━━━━━━━━━━━━━━━━━

📊 **ดูข้อมูล/รายงาน:**
• "สรุปวันนี้" - ภาพรวมทั้งหมด
• "ยอดขายวันนี้/สัปดาห์/เดือน"
• "ออเดอร์รอดำเนินการ"
• "สินค้าหมด" / "สินค้าใกล้หมด"
• "ลูกค้าใหม่วันนี้"
• "สลิปรอตรวจ"

🔍 **ค้นหา:**
• "หาลูกค้า [ชื่อ]"
• "หาออเดอร์ #[เลข]"
• "หาสินค้า [ชื่อ]"

🚀 **Actions (ทำงานได้เลย):**
• "ยืนยันออเดอร์ #TXN123"
• "อนุมัติสลิป #TXN123"
• "ปฏิเสธสลิป #TXN123"
• "ยกเลิกออเดอร์ #TXN123"
• "ปิดสินค้าหมด"
• "เปิดสินค้ามี stock"

🚨 **แจ้งเตือน:**
• "แจ้งเตือน" - ดูปัญหาทั้งหมด

🏆 **อันดับ:**
• "top ลูกค้า"
• "สินค้าขายดี"
• "สินค้าแพงที่สุด"

🖥️ **ระบบ:**
• "สถานะระบบ"
• "เปรียบเทียบสัปดาห์"`;
            
            addAIMessage(helpText, 'ai');
        });
        
        // Quick buttons
        quickBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                sendAIMessage(btn.dataset.msg);
            });
        });
        
        // Form submit
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const msg = input.value.trim();
            if (msg) {
                sendAIMessage(msg);
                input.value = '';
            }
        });
        
        function sendAIMessage(msg) {
            // Add user message
            addAIMessage(msg, 'user');
            
            // Show typing
            const typingId = 'typing-' + Date.now();
            messages.innerHTML += `<div id="${typingId}" class="ai-message mb-3">
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm p-3 text-sm">
                    <i class="fas fa-circle-notch fa-spin"></i> กำลังคิด...
                </div>
            </div>`;
            scrollAIToBottom();
            
            // Get base URL
            const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
            
            // Call API
            fetch(baseUrl + 'api/ai-admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message: msg})
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById(typingId)?.remove();
                if (data.success) {
                    addAIMessage(data.response, 'ai');
                } else {
                    addAIMessage('❌ ' + (data.error || 'เกิดข้อผิดพลาด'), 'ai');
                }
            })
            .catch(err => {
                document.getElementById(typingId)?.remove();
                addAIMessage('❌ ไม่สามารถเชื่อมต่อได้', 'ai');
            });
        }
        
        function addAIMessage(text, type) {
            const div = document.createElement('div');
            div.className = type === 'user' ? 'user-message mb-3 text-right' : 'ai-message mb-3';
            
            // Convert markdown-like formatting
            let html = text
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
            
            if (type === 'user') {
                div.innerHTML = `<div class="inline-block bg-purple-600 text-white rounded-2xl rounded-tr-sm p-3 text-sm max-w-[85%] text-left">${html}</div>`;
            } else {
                div.innerHTML = `<div class="bg-gray-100 rounded-2xl rounded-tl-sm p-3 text-sm max-w-[85%]">${html}</div>`;
            }
            
            messages.appendChild(div);
            scrollAIToBottom();
        }
        
        function scrollAIToBottom() {
            messages.scrollTop = messages.scrollHeight;
        }
    })();
    </script>
    
    <style>
    @keyframes slide-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-slide-up { animation: slide-up 0.3s ease-out; }
    </style>
</body>
</html>
