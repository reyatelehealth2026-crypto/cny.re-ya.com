            </div>
        </div>
    </div>

    <!-- PWA Install Prompt Modal -->
    <?php include __DIR__ . '/install_prompt.php'; ?>

    <script>
    // Toast notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-20 right-4 px-6 py-3 rounded-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} shadow-lg z-50 transition-opacity`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
    }

    // Confirm delete
    function confirmDelete(message = 'คุณแน่ใจหรือไม่ที่จะลบ?') {
        return confirm(message);
    }
    </script>
    
    <!-- PWA Service Worker -->
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('SW registered'))
            .catch(err => console.log('SW failed', err));
    }
    </script>
</body>
</html>
