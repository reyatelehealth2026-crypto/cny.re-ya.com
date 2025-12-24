<?php
/**
 * PWA Scripts - Include this before </body> for PWA support
 * Note: Main install prompt is now in install_prompt.php
 */
?>
<!-- PWA Service Worker Registration -->
<script>
// Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('SW registered:', registration.scope);
            })
            .catch((error) => {
                console.log('SW registration failed:', error);
            });
    });
}

// Online/Offline status
window.addEventListener('online', () => {
    document.body.classList.remove('offline');
    if (typeof showToast === 'function') {
        showToast('กลับมาออนไลน์แล้ว', 'success');
    }
});

window.addEventListener('offline', () => {
    document.body.classList.add('offline');
    if (typeof showToast === 'function') {
        showToast('ไม่มีการเชื่อมต่ออินเทอร์เน็ต', 'error');
    }
});
</script>
