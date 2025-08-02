<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Models\ThemeSetting;

class ThemeInjectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip API routes and non-HTML responses
        if ($request->is('api/*') || $request->expectsJson()) {
            return $next($request);
        }

        // Get user role for theme application
        $userRole = $this->getUserRole($request);
        $isDarkMode = $this->isDarkMode($request);

        // Inject theme variables into all views
        $this->injectThemeVariables($userRole, $isDarkMode);

        $response = $next($request);

        // Inject theme CSS into HTML responses
        if ($this->shouldInjectCss($response)) {
            $content = $response->getContent();
            $content = $this->injectThemeCss($content, $userRole, $isDarkMode);
            $response->setContent($content);
        }

        return $response;
    }

    /**
     * Get user role from authenticated user or session
     *
     * @param Request $request
     * @return string
     */
    private function getUserRole(Request $request)
    {
        // Check authenticated user
        if (auth()->check()) {
            return auth()->user()->user_type ?? 'customer';
        }

        // Check session for role
        if ($request->session()->has('user_role')) {
            return $request->session()->get('user_role');
        }

        // Default to customer for public pages
        return 'customer';
    }

    /**
     * Check if dark mode is enabled
     *
     * @param Request $request
     * @return bool
     */
    private function isDarkMode(Request $request)
    {
        // Check user preference first
        if (auth()->check() && auth()->user()->theme_preference) {
            return auth()->user()->theme_preference === 'dark';
        }

        // Check session
        if ($request->session()->has('theme_mode')) {
            return $request->session()->get('theme_mode') === 'dark';
        }

        // Check cookie (this is where localStorage data should be synced)
        if ($request->cookie('theme_mode')) {
            return $request->cookie('theme_mode') === 'dark';
        }

        // Check for Bootstrap theme cookie (data-bs-theme)
        if ($request->cookie('data-bs-theme')) {
            return $request->cookie('data-bs-theme') === 'dark';
        }

        // Default to light mode
        return false;
    }

    /**
     * Inject theme variables into views
     *
     * @param string $userRole
     * @param bool $isDarkMode
     * @return void
     */
    private function injectThemeVariables($userRole, $isDarkMode)
    {
        $themeData = Cache::remember("theme_variables_{$userRole}", 3600, function () use ($userRole) {
            return $this->buildThemeVariables($userRole);
        });

        $themeMode = $isDarkMode ? 'dark' : 'light';

        // Share variables with all views
        View::share([
            'themeData' => $themeData,
            'userRole' => $userRole,
            'themeMode' => $themeMode,
            'isDarkMode' => $isDarkMode,
            'themeVersion' => $this->getThemeVersion(),
            'themeCssUrl' => $this->getThemeCssUrl($userRole, $themeMode)
        ]);
    }

    /**
     * Build theme variables for views
     *
     * @param string $userRole
     * @return array
     */
    private function buildThemeVariables($userRole)
    {
        $brandColors = ThemeSetting::getByGroup('brand_colors');
        $roleColors = ThemeSetting::getByGroup('role_colors');

        $brandColorMap = $this->formatColorsForView($brandColors);
        $roleColorMap = $this->formatColorsForView($roleColors);

        return [
            'brand_colors' => $brandColorMap,
            'role_colors' => $roleColorMap,
            'primary_role' => $userRole,
            'primary_color' => $roleColorMap[$userRole] ?? $brandColorMap['blue'] ?? ['light' => '#4A75FB', 'dark' => '#004CB2'],
            'css_variables' => $this->generateCssVariables($brandColorMap, $roleColorMap, $userRole)
        ];
    }

    /**
     * Format colors for view usage
     *
     * @param \Illuminate\Database\Eloquent\Collection $colors
     * @return array
     */
    private function formatColorsForView($colors)
    {
        $formatted = [];

        foreach ($colors as $setting) {
            $parts = explode('_', $setting->setting_key);
            $colorName = $parts[0];
            $theme = $parts[1] ?? 'light';

            if (!isset($formatted[$colorName])) {
                $formatted[$colorName] = ['light' => '#000000', 'dark' => '#000000'];
            }

            $formatted[$colorName][$theme] = $setting->setting_value;
        }

        return $formatted;
    }

    /**
     * Generate CSS variables string
     *
     * @param array $brandColors
     * @param array $roleColors
     * @param string $userRole
     * @return string
     */
    private function generateCssVariables($brandColors, $roleColors, $userRole)
    {
        $variables = [];

        // Brand color variables
        foreach ($brandColors as $name => $colors) {
            $variables["--brand-{$name}"] = $colors['light'];
            $variables["--brand-{$name}-dark"] = $colors['dark'];
        }

        // Role color variables
        foreach ($roleColors as $name => $colors) {
            $variables["--role-{$name}"] = $colors['light'];
            $variables["--role-{$name}-dark"] = $colors['dark'];
        }

        // Primary color for current role
        if (isset($roleColors[$userRole])) {
            $variables["--primary-color"] = $roleColors[$userRole]['light'];
            $variables["--primary-color-dark"] = $roleColors[$userRole]['dark'];
        }

        return $variables;
    }

    /**
     * Check if CSS should be injected into response
     *
     * @param \Illuminate\Http\Response $response
     * @return bool
     */
    private function shouldInjectCss($response)
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html') || empty($contentType);
    }

    /**
     * Inject theme CSS into HTML content
     *
     * @param string $content
     * @param string $userRole
     * @param bool $isDarkMode
     * @return string
     */
    private function injectThemeCss($content, $userRole, $isDarkMode)
    {
        $themeMode = $isDarkMode ? 'dark' : 'light';
        $version = $this->getThemeVersion();

        // Generate CSS URLs
        $themeCssUrl = route('dynamic.theme.css', [
            'role' => $userRole,
            'theme' => $themeMode,
            'v' => $version
        ]);

        // CSS injection template
        $cssInjection = "\n<!-- Dynamic Theme CSS -->\n";
        $cssInjection .= "<link rel=\"stylesheet\" href=\"{$themeCssUrl}\" id=\"dynamic-theme-css\">\n";
        $cssInjection .= "<script>\n";
        $cssInjection .= "window.themeConfig = {\n";
        $cssInjection .= "  role: '{$userRole}',\n";
        $cssInjection .= "  mode: '{$themeMode}',\n";
        $cssInjection .= "  version: '{$version}'\n";
        $cssInjection .= "};\n";
        $cssInjection .= "</script>\n";

        // Inject before closing head tag
        if (str_contains($content, '</head>')) {
            $content = str_replace('</head>', $cssInjection . '</head>', $content);
        } else {
            // Fallback: inject at the beginning of body
            $content = str_replace('<body', $cssInjection . '<body', $content);
        }

        // Add theme classes to body
        $bodyClass = "theme-{$userRole} {$themeMode}-theme";
        $content = preg_replace(
            '/<body([^>]*)class="([^"]*)"/',
            '<body$1class="$2 ' . $bodyClass . '"',
            $content
        );

        // If no class attribute exists, add it
        if (!str_contains($content, 'class=')) {
            $content = preg_replace(
                '/<body([^>]*)>/',
                '<body$1 class="' . $bodyClass . '">',
                $content
            );
        }

        return $content;
    }

    /**
     * Get theme version for cache busting
     *
     * @return string
     */
    private function getThemeVersion()
    {
        return Cache::remember('theme_version', 3600, function () {
            $lastUpdate = ThemeSetting::max('updated_at');
            return substr(md5($lastUpdate ?? 'default'), 0, 8);
        });
    }

    /**
     * Get theme CSS URL
     *
     * @param string $userRole
     * @param string $themeMode
     * @return string
     */
    private function getThemeCssUrl($userRole, $themeMode)
    {
        return route('dynamic.theme.css', [
            'role' => $userRole,
            'theme' => $themeMode,
            'v' => $this->getThemeVersion()
        ]);
    }
}
