/**
 * Theme Manager - Handles dynamic theme switching and real-time updates
 * Integrates with the Laravel theme system for seamless color management
 */

class ThemeManager {
    constructor() {
        this.config = window.themeConfig || {};
        this.currentRole = this.config.role || 'customer';

        // Initialize theme mode from localStorage first, then fallback to config
        const savedTheme = localStorage.getItem('data-bs-theme');
        this.currentMode = savedTheme || this.config.mode || 'light';

        this.version = this.config.version || 'latest';

        this.init();
    }

    /**
     * Initialize theme manager
     */
    init() {
        // Apply initial theme state
        document.documentElement.setAttribute('data-bs-theme', this.currentMode);
        document.body.classList.toggle('dark', this.currentMode === 'dark');

        this.loadThemeCSS();
        this.setupEventListeners();
        this.applyThemeClasses();

        // Sync with cookies
        this.setCookie('data-bs-theme', this.currentMode, 365);
        this.setCookie('theme_mode', this.currentMode, 365);

        // Check for theme updates periodically (every 5 minutes)
        setInterval(() => this.checkForUpdates(), 300000);

        console.log('🎨 Theme Manager initialized:', {
            role: this.currentRole,
            mode: this.currentMode,
            version: this.version
        });
    }

    /**
     * Load dynamic theme CSS
     */
    loadThemeCSS() {
        const existingLink = document.getElementById('dynamic-theme-css');

        if (existingLink) {
            // Update existing link
            const newUrl = this.buildCssUrl();
            if (existingLink.href !== newUrl) {
                existingLink.href = newUrl;
            }
        } else {
            // Create new link element
            const link = document.createElement('link');
            link.id = 'dynamic-theme-css';
            link.rel = 'stylesheet';
            link.href = this.buildCssUrl();
            document.head.appendChild(link);
        }
    }

    /**
     * Build CSS URL with current parameters
     */
    buildCssUrl() {
        // Use the correct dynamic theme CSS route
        const baseUrl = '/css/dynamic-theme';
        const params = new URLSearchParams({
            role: this.currentRole,
            theme: this.currentMode,
            v: this.version
        });

        return `${baseUrl}?${params.toString()}`;
    }

    /**
     * Switch theme mode (light/dark)
     */
    switchThemeMode(mode) {
        if (mode !== 'light' && mode !== 'dark') {
            console.warn('Invalid theme mode:', mode);
            return;
        }

        this.currentMode = mode;

        // Update HTML data attribute
        document.documentElement.setAttribute('data-bs-theme', mode);

        // Update body class for compatibility
        document.body.classList.toggle('dark', mode === 'dark');

        // Save to localStorage and cookie
        localStorage.setItem('data-bs-theme', mode);
        this.setCookie('data-bs-theme', mode, 365);
        this.setCookie('theme_mode', mode, 365);

        this.loadThemeCSS();
        this.applyThemeClasses();

        // Trigger custom event
        this.dispatchThemeEvent('theme-mode-changed', { mode });

        console.log('🎨 Theme switched to:', mode);
    }

    /**
     * Switch user role theme
     */
    switchRoleTheme(role) {
        const validRoles = ['admin', 'provider', 'handyman', 'customer'];

        if (!validRoles.includes(role)) {
            console.warn('Invalid role:', role);
            return;
        }

        this.currentRole = role;
        this.loadThemeCSS();
        this.applyThemeClasses();

        // Trigger custom event
        this.dispatchThemeEvent('theme-role-changed', { role });
    }

    /**
     * Apply theme classes to body
     */
    applyThemeClasses() {
        const body = document.body;

        // Remove existing theme classes
        body.classList.remove('light-theme', 'dark-theme');
        body.classList.remove('theme-admin', 'theme-provider', 'theme-handyman', 'theme-customer');

        // Add current theme classes
        body.classList.add(`${this.currentMode}-theme`);
        body.classList.add(`theme-${this.currentRole}`);
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Listen for theme toggle buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-theme-toggle]')) {
                const mode = e.target.dataset.themeToggle;
                this.switchThemeMode(mode);
            }

            if (e.target.matches('[data-role-toggle]')) {
                const role = e.target.dataset.roleToggle;
                this.switchRoleTheme(role);
            }
        });

        // Listen for custom theme events
        document.addEventListener('theme-update-available', () => {
            this.handleThemeUpdate();
        });
    }

    /**
     * Check for theme updates
     */
    async checkForUpdates() {
        try {
            const response = await fetch('/api/v1/theme/check-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    version: this.version
                })
            });

            const data = await response.json();

            if (data.success && data.has_update) {
                this.version = data.current_version;
                this.dispatchThemeEvent('theme-update-available', {
                    oldVersion: data.client_version,
                    newVersion: data.current_version
                });
            }
        } catch (error) {
            console.warn('Failed to check for theme updates:', error);
        }
    }

    /**
     * Handle theme update
     */
    handleThemeUpdate() {
        // Reload theme CSS with new version
        this.loadThemeCSS();

        // Show notification (optional)
        this.showUpdateNotification();
    }

    /**
     * Show theme update notification
     */
    showUpdateNotification() {
        // Create a simple notification
        const notification = document.createElement('div');
        notification.className = 'theme-update-notification';
        notification.innerHTML = `
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-palette me-2"></i>
                Theme colors have been updated!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insert at top of page
        document.body.insertBefore(notification, document.body.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Save theme preference to localStorage and server
     */
    saveThemePreference() {
        // Save to localStorage
        localStorage.setItem('theme_mode', this.currentMode);
        localStorage.setItem('theme_role', this.currentRole);

        // Save to server (if user is authenticated)
        if (window.Laravel && window.Laravel.user) {
            this.saveThemeToServer();
        }
    }

    /**
     * Save theme preference to server
     */
    async saveThemeToServer() {
        try {
            await fetch('/api/user/theme-preference', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    theme_mode: this.currentMode,
                    role_preference: this.currentRole
                })
            });
        } catch (error) {
            console.warn('Failed to save theme preference:', error);
        }
    }

    /**
     * Dispatch custom theme event
     */
    dispatchThemeEvent(eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            detail: {
                ...detail,
                role: this.currentRole,
                mode: this.currentMode,
                version: this.version
            }
        });

        document.dispatchEvent(event);
    }

    /**
     * Get current theme configuration
     */
    getCurrentTheme() {
        return {
            role: this.currentRole,
            mode: this.currentMode,
            version: this.version
        };
    }

    /**
     * Apply custom CSS variables
     */
    applyCSSVariables(variables) {
        const root = document.documentElement;

        Object.entries(variables).forEach(([property, value]) => {
            root.style.setProperty(property, value);
        });
    }

    /**
     * Get theme colors for current configuration
     */
    async getThemeColors() {
        try {
            const response = await fetch(`/api/v1/theme/colors/${this.currentRole}`);
            const data = await response.json();

            if (data.success) {
                return data.data;
            }
        } catch (error) {
            console.warn('Failed to fetch theme colors:', error);
        }

        return null;
    }

    /**
     * Set cookie helper method
     */
    setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    }
}

// Initialize theme manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
