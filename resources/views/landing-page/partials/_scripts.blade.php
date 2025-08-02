
 @if(request()->route() && request()->route()->getName() != "service.detail")
<script src="{{ asset('js/landing-app.min.js') }}"></script>
 @endif
 <script src="{{ asset('js/bootstrap.bundle.js')}}"></script>
 <script src="{{ asset('js/backend-bundle.min/resources_js_handyman_js.js')}}"></script>

<script src="{{ asset('js/app.js') }}"></script>

<!-- Dynamic Theme Management -->
<script src="{{ asset('js/role-based-theming.js?v=' . time()) }}" defer></script>
<script src="{{ asset('js/theme-manager.js?v=' . time()) }}" defer></script>

<!-- Enhanced Theme Management Script -->
<script>
(function() {
    'use strict';

    // Initialize theme system
    function initializeTheme() {
        const savedTheme = localStorage.getItem('data-bs-theme') || 'light';
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';

        // Use saved theme if different from current
        const finalTheme = savedTheme !== currentTheme ? savedTheme : currentTheme;

        // Apply theme
        document.documentElement.setAttribute('data-bs-theme', finalTheme);
        document.body.classList.toggle('dark', finalTheme === 'dark');

        // Update theme classes
        document.body.classList.remove('light-theme', 'dark-theme');
        document.body.classList.add(`${finalTheme}-theme`);

        // Save to localStorage and cookie
        localStorage.setItem('data-bs-theme', finalTheme);
        setCookie('data-bs-theme', finalTheme, 365);
        setCookie('theme_mode', finalTheme, 365);

        console.log('🎨 Theme initialized:', finalTheme);
    }

    // Cookie helper
    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    }

    // Listen for theme changes
    function setupThemeListener() {
        // Watch for theme toggle clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('.change-mode') || e.target.closest('[data-theme-toggle]')) {
                const newTheme = e.target.dataset.themeToggle ||
                               (document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');

                // Apply new theme
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                document.body.classList.toggle('dark', newTheme === 'dark');

                // Update theme classes
                document.body.classList.remove('light-theme', 'dark-theme');
                document.body.classList.add(`${newTheme}-theme`);

                // Save preferences
                localStorage.setItem('data-bs-theme', newTheme);
                setCookie('data-bs-theme', newTheme, 365);
                setCookie('theme_mode', newTheme, 365);

                // Update dynamic CSS if theme manager exists
                if (window.themeManager) {
                    window.themeManager.switchThemeMode(newTheme);
                }

                console.log('🎨 Theme switched to:', newTheme);
            }
        });

        // Watch for storage changes (theme changes in other tabs)
        window.addEventListener('storage', function(e) {
            if (e.key === 'data-bs-theme') {
                initializeTheme();
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
            setupThemeListener();
        });
    } else {
        initializeTheme();
        setupThemeListener();
    }
})();
</script>

@yield('bottom_script')
