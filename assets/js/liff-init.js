/**
 * LIFF Init Helper
 * รองรับทั้ง LINE App และ External Browser
 * 
 * Usage:
 * const liffHelper = new LiffHelper(LIFF_ID);
 * await liffHelper.init();
 * if (liffHelper.isLoggedIn()) {
 *     const profile = await liffHelper.getProfile();
 * }
 */

class LiffHelper {
    constructor(liffId) {
        this.liffId = liffId;
        this.profile = null;
        this.isReady = false;
    }

    /**
     * Initialize LIFF and handle login
     * @param {Object} options - { autoLogin: true, redirectUri: null }
     * @returns {Promise<boolean>} - true if logged in
     */
    async init(options = {}) {
        const { autoLogin = true, redirectUri = null } = options;

        if (!this.liffId) {
            console.error('LIFF ID is required');
            return false;
        }

        try {
            await liff.init({ liffId: this.liffId });
            this.isReady = true;

            if (liff.isLoggedIn()) {
                this.profile = await liff.getProfile();
                return true;
            } else if (autoLogin) {
                // Auto login - works in both LINE App and External Browser
                const loginOptions = {};
                if (redirectUri) {
                    loginOptions.redirectUri = redirectUri;
                }
                liff.login(loginOptions);
                return false; // Will redirect
            }

            return false;
        } catch (error) {
            console.error('LIFF init error:', error);
            return false;
        }
    }

    /**
     * Check if user is logged in
     */
    isLoggedIn() {
        return this.isReady && liff.isLoggedIn();
    }

    /**
     * Get user profile
     */
    async getProfile() {
        if (this.profile) return this.profile;
        if (this.isLoggedIn()) {
            this.profile = await liff.getProfile();
        }
        return this.profile;
    }

    /**
     * Get user ID
     */
    getUserId() {
        return this.profile?.userId || null;
    }

    /**
     * Check if running in LINE App
     */
    isInClient() {
        return this.isReady && liff.isInClient();
    }

    /**
     * Login (redirect to LINE Login)
     */
    login(redirectUri = null) {
        if (!this.isReady) {
            // Fallback: redirect to LIFF URL
            window.location.href = `https://liff.line.me/${this.liffId}`;
            return;
        }

        const options = {};
        if (redirectUri) {
            options.redirectUri = redirectUri;
        }
        liff.login(options);
    }

    /**
     * Logout
     */
    logout() {
        if (this.isReady && liff.isLoggedIn()) {
            liff.logout();
            window.location.reload();
        }
    }

    /**
     * Close LIFF window (only works in LINE App)
     */
    closeWindow() {
        if (this.isInClient()) {
            liff.closeWindow();
        } else {
            window.close();
        }
    }

    /**
     * Send message to chat (only works in LINE App)
     */
    async sendMessage(text) {
        if (!this.isInClient()) {
            console.warn('sendMessage only works in LINE App');
            return false;
        }

        try {
            await liff.sendMessages([{ type: 'text', text: text }]);
            return true;
        } catch (error) {
            console.error('Send message error:', error);
            return false;
        }
    }

    /**
     * Share target picker
     */
    async shareMessage(messages) {
        if (!this.isReady) return false;

        try {
            if (liff.isApiAvailable('shareTargetPicker')) {
                await liff.shareTargetPicker(messages);
                return true;
            }
        } catch (error) {
            console.error('Share error:', error);
        }
        return false;
    }

    /**
     * Open external URL
     */
    openWindow(url, external = true) {
        if (this.isReady) {
            liff.openWindow({ url, external });
        } else {
            window.open(url, external ? '_blank' : '_self');
        }
    }

    /**
     * Get OS type
     */
    getOS() {
        if (!this.isReady) return 'unknown';
        return liff.getOS(); // 'ios', 'android', 'web'
    }

    /**
     * Get LIFF language
     */
    getLanguage() {
        if (!this.isReady) return 'th';
        return liff.getLanguage();
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LiffHelper;
}
