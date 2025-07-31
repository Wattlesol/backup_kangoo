/**
 * Role-based Theming System
 * Handles dynamic color switching based on user roles and rotating card colors
 */

(function() {
    'use strict';

    // Role-based theme configuration - will be updated from CSS variables
    let roleThemes = {
        customer: {
            light: '#4A75FB',
            dark: '#004CB2',
            class: 'theme-customer'
        },
        admin: {
            light: '#5F60B9',
            dark: '#4153b3',
            class: 'theme-admin'
        },
        handyman: {
            light: '#2DB665',
            dark: '#005F2D',
            class: 'theme-handyman'
        },
        provider: {
            light: '#EF5535',
            dark: '#9B1F0B',
            class: 'theme-provider'
        }
    };

    // Update role themes from CSS variables if available
    function updateRoleThemesFromCSS() {
        const computedStyle = getComputedStyle(document.documentElement);

        Object.keys(roleThemes).forEach(role => {
            const lightVar = `--role-${role}-light`;
            const darkVar = `--role-${role}-dark`;

            const lightColor = computedStyle.getPropertyValue(lightVar).trim();
            const darkColor = computedStyle.getPropertyValue(darkVar).trim();

            if (lightColor) {
                roleThemes[role].light = lightColor;
            }
            if (darkColor) {
                roleThemes[role].dark = darkColor;
            }
        });
    }

    // Brand colors for rotating cards - will be updated from CSS variables
    let brandColors = {
        yellow: { light: '#F0B521', dark: '#8D6710' },
        red: { light: '#EF5535', dark: '#9B1F0B' },
        green: { light: '#2DB665', dark: '#005F2D' },
        blue: { light: '#4A75FB', dark: '#004CB2' }
    };

    // Update brand colors from CSS variables if available
    function updateBrandColorsFromCSS() {
        const computedStyle = getComputedStyle(document.documentElement);

        Object.keys(brandColors).forEach(color => {
            const lightVar = `--brand-${color}-light`;
            const darkVar = `--brand-${color}-dark`;

            const lightColor = computedStyle.getPropertyValue(lightVar).trim();
            const darkColor = computedStyle.getPropertyValue(darkVar).trim();

            if (lightColor) {
                brandColors[color].light = lightColor;
            }
            if (darkColor) {
                brandColors[color].dark = darkColor;
            }
        });

        // Also update from window.brandColors if available (from backend)
        if (window.brandColors) {
            Object.assign(brandColors, window.brandColors);
        }
    }

    /**
     * Apply role-based theme to the document
     */
    function applyRoleTheme(role, isDark = false) {
        const theme = roleThemes[role] || roleThemes.customer;
        const body = document.body;
        const html = document.documentElement;

        // Remove existing theme classes
        Object.values(roleThemes).forEach(t => {
            body.classList.remove(t.class);
            html.classList.remove(t.class);
        });

        // Add current role theme class
        body.classList.add(theme.class);
        html.classList.add(theme.class);

        // Update CSS custom properties with !important priority
        const primaryColor = isDark ? theme.dark : theme.light;
        html.style.setProperty('--c1', primaryColor, 'important');
        html.style.setProperty('--link-color', primaryColor, 'important');
        html.style.setProperty('--input-border-color-active', `${primaryColor}80`, 'important'); // 50% opacity
        html.style.setProperty('--c1-light-bg', `${primaryColor}0D`, 'important'); // 5% opacity

        // Also apply to body for better compatibility
        body.style.setProperty('--c1', primaryColor, 'important');
        body.style.setProperty('--link-color', primaryColor, 'important');
        body.style.setProperty('--input-border-color-active', `${primaryColor}80`, 'important');
        body.style.setProperty('--c1-light-bg', `${primaryColor}0D`, 'important');
    }

    /**
     * Get rotating card color based on index
     */
    function getRotatingCardColor(index, isDark = false) {
        const colorKeys = Object.keys(brandColors);
        const selectedColorKey = colorKeys[index % colorKeys.length];
        const selectedColor = brandColors[selectedColorKey];
        return isDark ? selectedColor.dark : selectedColor.light;
    }

    /**
     * Apply rotating colors to card elements
     */
    function applyRotatingCardColors() {
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark' || 
                      document.documentElement.getAttribute('theme') === 'dark';
        
        // Apply to elements with data-card-index attribute
        document.querySelectorAll('[data-card-index]').forEach(card => {
            const index = parseInt(card.getAttribute('data-card-index'));
            const color = getRotatingCardColor(index, isDark);
            
            // Apply color to the card or its designated color element
            const colorElement = card.querySelector('.card-color-element') || card;
            colorElement.style.backgroundColor = color;
        });

        // Apply to elements with rotating-card-color class
        document.querySelectorAll('.rotating-card-color').forEach((card, index) => {
            const color = getRotatingCardColor(index, isDark);
            card.style.backgroundColor = color;
        });
    }

    /**
     * Initialize role-based theming
     */
    function initRoleBasedTheming() {
        // Update colors from CSS variables first
        updateRoleThemesFromCSS();
        updateBrandColorsFromCSS();

        // Get user role from global variable (set by PHP)
        const userRole = window.userRole || 'customer';

        // Debug logging
        console.log('🎨 Role-based theming initializing...');
        console.log('User Role:', userRole);
        console.log('Body classes:', document.body.className);
        console.log('HTML classes:', document.documentElement.className);

        // Check current theme mode
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark' ||
                      document.documentElement.getAttribute('theme') === 'dark';

        console.log('Dark mode:', isDark);

        // Apply role theme
        applyRoleTheme(userRole, isDark);

        // Debug: Check if CSS variables are applied
        setTimeout(() => {
            const computedStyle = getComputedStyle(document.documentElement);
            const c1Value = computedStyle.getPropertyValue('--c1');
            console.log('Applied --c1 value:', c1Value);

            // Get expected color from updated roleThemes
            const expectedColor = roleThemes[userRole] ?
                (isDark ? roleThemes[userRole].dark : roleThemes[userRole].light) :
                '#4A75FB';

            if (!c1Value.includes(expectedColor.replace('#', ''))) {
                console.warn(`⚠️ ${userRole} theme not applied correctly!`);
                console.log(`Expected: ${expectedColor}, Got: ${c1Value}`);

                // Force apply the theme
                document.documentElement.style.setProperty('--c1', expectedColor, 'important');
                document.body.style.setProperty('--c1', expectedColor, 'important');
                console.log(`🔧 Force-applied ${userRole} theme`);
            }
        }, 100);

        // Apply rotating card colors
        applyRotatingCardColors();

        // Listen for theme mode changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'data-bs-theme' || mutation.attributeName === 'theme')) {
                    const newIsDark = document.documentElement.getAttribute('data-bs-theme') === 'dark' || 
                                     document.documentElement.getAttribute('theme') === 'dark';
                    applyRoleTheme(userRole, newIsDark);
                    applyRotatingCardColors();
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-bs-theme', 'theme']
        });
    }

    /**
     * Utility function to get brand color by name and theme
     */
    function getBrandColor(colorName, isDark = false) {
        const color = brandColors[colorName];
        return color ? (isDark ? color.dark : color.light) : null;
    }

    /**
     * Utility function to get current user role theme colors
     */
    function getCurrentRoleColors() {
        const userRole = window.userRole || 'customer';
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark' || 
                      document.documentElement.getAttribute('theme') === 'dark';
        const theme = roleThemes[userRole] || roleThemes.customer;
        
        return {
            primary: isDark ? theme.dark : theme.light,
            role: userRole,
            isDark: isDark
        };
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRoleBasedTheming);
    } else {
        initRoleBasedTheming();
    }

    // Expose utility functions globally
    window.RoleTheming = {
        applyRoleTheme,
        getRotatingCardColor,
        applyRotatingCardColors,
        getBrandColor,
        getCurrentRoleColors,
        brandColors,
        roleThemes
    };

})();
