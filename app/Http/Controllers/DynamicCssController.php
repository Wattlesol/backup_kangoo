<?php

namespace App\Http\Controllers;

use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DynamicCssController extends Controller
{
    /**
     * Generate and serve dynamic theme CSS
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateThemeCss(Request $request)
    {
        $version = $request->get('v', 'latest');
        $role = $request->get('role', 'default');
        $theme = $request->get('theme', 'light'); // light or dark

        try {
            $css = Cache::remember("theme_css_{$role}_{$theme}_{$version}", 3600, function () use ($role, $theme) {
                return $this->buildThemeCss($role, $theme);
            });

            $etag = 'W/"'.md5($css).'"';
            $clientEtags = $request->getETags();
            if (in_array($etag, $clientEtags, true)) {
                return response('', 304)
                    ->header('ETag', $etag)
                    ->header('Cache-Control', 'public, max-age=3600');
            }
            return response($css)
                ->header('Content-Type', 'text/css')
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('ETag', $etag)
                ->header('Last-Modified', gmdate('D, d M Y H:i:s', time()).' GMT');

        } catch (\Exception $e) {
            return response('/* Error generating theme CSS */', 500)
                ->header('Content-Type', 'text/css');
        }
    }

    /**
     * Generate CSS for landing pages
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateLandingCss(Request $request)
    {
        $theme = $request->get('theme', 'light');

        try {
            $css = Cache::remember("landing_css_{$theme}", 3600, function () use ($theme) {
                return $this->buildLandingPageCss($theme);
            });

            $etag = 'W/"'.md5($css).'"';
            $clientEtags = $request->getETags();
            if (in_array($etag, $clientEtags, true)) {
                return response('', 304)
                    ->header('ETag', $etag)
                    ->header('Cache-Control', 'public, max-age=3600');
            }
            return response($css)
                ->header('Content-Type', 'text/css')
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('ETag', $etag)
                ->header('Last-Modified', gmdate('D, d M Y H:i:s', time()).' GMT');

        } catch (\Exception $e) {
            return response('/* Error generating landing CSS */', 500)
                ->header('Content-Type', 'text/css');
        }
    }

    /**
     * Build complete theme CSS
     *
     * @param string $role
     * @param string $theme
     * @return string
     */
    private function buildThemeCss($role, $theme)
    {
        $brandColors = ThemeSetting::getByGroup('brand_colors');
        $roleColors = ThemeSetting::getByGroup('role_colors');

        $css = "/* Dynamic Theme CSS - Generated at " . now() . " */\n\n";

        // Root variables for brand colors
        $css .= ":root {\n";
        $css .= $this->generateBrandColorVariables($brandColors, $theme);
        $css .= $this->generateRoleColorVariables($roleColors, $role, $theme);
        $css .= "}\n\n";

        // Role-specific body classes
        if ($role !== 'default') {
            $css .= $this->generateRoleSpecificCss($role, $theme);
        }

        // Theme-specific overrides
        $css .= $this->generateThemeOverrides($theme);

        // Component-specific CSS
        $css .= $this->generateComponentCss();

        return $css;
    }

    /**
     * Build landing page CSS
     *
     * @param string $theme
     * @return string
     */
    private function buildLandingPageCss($theme)
    {
        $brandColors = ThemeSetting::getByGroup('brand_colors');

        $css = "/* Landing Page Theme CSS - Generated at " . now() . " */\n\n";

        $css .= ":root {\n";
        $css .= $this->generateBrandColorVariables($brandColors, $theme);
        $css .= "}\n\n";

        $css .= $this->generateLandingPageComponents($theme);

        return $css;
    }

    /**
     * Generate brand color CSS variables
     *
     * @param \Illuminate\Database\Eloquent\Collection $brandColors
     * @param string $theme
     * @return string
     */
    private function generateBrandColorVariables($brandColors, $theme)
    {
        $css = "  /* Brand Colors */\n";
        $colorMap = $this->formatColorsForCss($brandColors);

        foreach ($colorMap as $colorName => $colors) {
            $color = $colors[$theme] ?? $colors['light'] ?? '#000000';
            $css .= "  --brand-{$colorName}: {$color};\n";
            $css .= "  --brand-{$colorName}-rgb: " . $this->hexToRgb($color) . ";\n";

            // Generate lighter and darker variants
            $css .= "  --brand-{$colorName}-light: " . $this->lightenColor($color, 20) . ";\n";
            $css .= "  --brand-{$colorName}-dark: " . $this->darkenColor($color, 20) . ";\n";
        }

        return $css;
    }

    /**
     * Generate role color CSS variables
     *
     * @param \Illuminate\Database\Eloquent\Collection $roleColors
     * @param string $role
     * @param string $theme
     * @return string
     */
    private function generateRoleColorVariables($roleColors, $role, $theme)
    {
        $css = "  /* Role Colors */\n";
        $colorMap = $this->formatColorsForCss($roleColors);

        foreach ($colorMap as $roleName => $colors) {
            $color = $colors[$theme] ?? $colors['light'] ?? '#000000';
            $css .= "  --role-{$roleName}: {$color};\n";
            $css .= "  --role-{$roleName}-rgb: " . $this->hexToRgb($color) . ";\n";

            // Set primary role color
            if ($roleName === $role) {
                $css .= "  --primary-color: {$color};\n";
                $css .= "  --primary-color-rgb: " . $this->hexToRgb($color) . ";\n";
                $css .= "  --primary-color-light: " . $this->lightenColor($color, 20) . ";\n";
                $css .= "  --primary-color-dark: " . $this->darkenColor($color, 20) . ";\n";
            }
        }

        return $css;
    }

    /**
     * Generate role-specific CSS
     *
     * @param string $role
     * @param string $theme
     * @return string
     */
    private function generateRoleSpecificCss($role, $theme)
    {
        $css = "/* Role-specific styles for {$role} */\n";
        $css .= ".theme-{$role} {\n";
        $css .= "  --c1: var(--role-{$role});\n";
        $css .= "  --c1-rgb: var(--role-{$role}-rgb);\n";
        $css .= "}\n\n";

        // Navigation and sidebar colors
        $css .= ".theme-{$role} .navbar,\n";
        $css .= ".theme-{$role} .sidebar {\n";
        $css .= "  background-color: var(--role-{$role});\n";
        $css .= "}\n\n";

        // Button colors
        $css .= ".theme-{$role} .btn-primary {\n";
        $css .= "  background-color: var(--role-{$role});\n";
        $css .= "  border-color: var(--role-{$role});\n";
        $css .= "}\n\n";

        $css .= ".theme-{$role} .btn-primary:hover {\n";
        $css .= "  background-color: var(--role-{$role}-dark);\n";
        $css .= "  border-color: var(--role-{$role}-dark);\n";
        $css .= "}\n\n";

        return $css;
    }

    /**
     * Generate theme-specific overrides
     *
     * @param string $theme
     * @return string
     */
    private function generateThemeOverrides($theme)
    {
        if ($theme === 'dark') {
            return "/* Dark theme overrides */\n" .
                   "body.dark-theme {\n" .
                   "  background-color: #1a1a1a;\n" .
                   "  color: #ffffff;\n" .
                   "}\n\n";
        }

        return "/* Light theme overrides */\n" .
               "body.light-theme {\n" .
               "  background-color: #ffffff;\n" .
               "  color: #333333;\n" .
               "}\n\n";
    }

    /**
     * Generate component-specific CSS
     *
     * @return string
     */
    private function generateComponentCss()
    {
        return "/* Component-specific theme styles - High specificity overrides */\n" .

               "/* NAVBAR BACKGROUND FIX - Always use body background, never primary color */\n" .
               "body .iq-navbar,\n" .
               "html .iq-navbar,\n" .
               ".iq-navbar,\n" .
               "body .iq-navbar.navs-color,\n" .
               "html .iq-navbar.navs-color,\n" .
               ".iq-navbar.navs-color,\n" .
               "body header .navbar,\n" .
               "html header .navbar,\n" .
               "header .navbar {\n" .
               "  background-color: var(--bs-body-bg) !important;\n" .
               "  background: var(--bs-body-bg) !important;\n" .
               "}\n\n" .

               "/* Navbar text colors should adapt to theme */\n" .
               "body .iq-navbar .navbar-nav .nav-item .nav-link,\n" .
               "html .iq-navbar .navbar-nav .nav-item .nav-link,\n" .
               ".iq-navbar .navbar-nav .nav-item .nav-link,\n" .
               "body .iq-navbar.navs-color .navbar-nav .nav-item .nav-link,\n" .
               "html .iq-navbar.navs-color .navbar-nav .nav-item .nav-link,\n" .
               ".iq-navbar.navs-color .navbar-nav .nav-item .nav-link {\n" .
               "  color: var(--bs-body-color) !important;\n" .
               "}\n\n" .

               "/* Override Bootstrap and static CSS primary colors */\n" .
               "body .text-primary,\n" .
               "html .text-primary,\n" .
               ".text-primary {\n" .
               "  color: var(--primary-color) !important;\n" .
               "}\n\n" .

               "body .bg-primary,\n" .
               "html .bg-primary,\n" .
               ".bg-primary {\n" .
               "  background-color: var(--primary-color) !important;\n" .
               "}\n\n" .

               "body .btn-primary,\n" .
               "html .btn-primary,\n" .
               ".btn-primary {\n" .
               "  background-color: var(--primary-color) !important;\n" .
               "  border-color: var(--primary-color) !important;\n" .
               "}\n\n" .

               "body .btn-primary:hover,\n" .
               "html .btn-primary:hover,\n" .
               ".btn-primary:hover {\n" .
               "  background-color: var(--primary-color-dark) !important;\n" .
               "  border-color: var(--primary-color-dark) !important;\n" .
               "}\n\n" .

               "body .border-primary,\n" .
               "html .border-primary,\n" .
               ".border-primary {\n" .
               "  border-color: var(--primary-color) !important;\n" .
               "}\n\n" .

               "/* Override hardcoded Bootstrap CSS variables */\n" .
               ":root {\n" .
               "  --bs-primary: var(--primary-color) !important;\n" .
               "  --bs-primary-rgb: var(--primary-color-rgb) !important;\n" .
               "}\n\n" .

               "/* Card styling */\n" .
               ".card {\n" .
               "  border-color: rgba(var(--primary-color-rgb), 0.1);\n" .
               "}\n\n";
    }

    /**
     * Generate landing page component CSS
     *
     * @param string $theme
     * @return string
     */
    private function generateLandingPageComponents($theme)
    {
        return "/* Landing page components - High specificity overrides */\n" .

               "/* NAVBAR BACKGROUND FIX - Always use body background for landing pages */\n" .
               "body .iq-navbar,\n" .
               "html .iq-navbar,\n" .
               ".iq-navbar,\n" .
               "body .iq-navbar.navs-color,\n" .
               "html .iq-navbar.navs-color,\n" .
               ".iq-navbar.navs-color,\n" .
               "body header .navbar,\n" .
               "html header .navbar,\n" .
               "header .navbar {\n" .
               "  background-color: var(--bs-body-bg) !important;\n" .
               "  background: var(--bs-body-bg) !important;\n" .
               "}\n\n" .

               "/* Navbar text colors should adapt to theme */\n" .
               "body .iq-navbar .navbar-nav .nav-item .nav-link,\n" .
               "html .iq-navbar .navbar-nav .nav-item .nav-link,\n" .
               ".iq-navbar .navbar-nav .nav-item .nav-link,\n" .
               "body .iq-navbar.navs-color .navbar-nav .nav-item .nav-link,\n" .
               "html .iq-navbar.navs-color .navbar-nav .nav-item .nav-link,\n" .
               ".iq-navbar.navs-color .navbar-nav .nav-item .nav-link {\n" .
               "  color: var(--bs-body-color) !important;\n" .
               "}\n\n" .

               "/* Hero section with brand colors */\n" .
               ".hero-section {\n" .
               "  background: linear-gradient(135deg, var(--brand-blue), var(--brand-green));\n" .
               "}\n\n" .

               "/* CTA buttons */\n" .
               ".cta-button {\n" .
               "  background-color: var(--brand-yellow);\n" .
               "  color: #333;\n" .
               "}\n\n" .

               "/* Feature cards */\n" .
               ".feature-card:hover {\n" .
               "  border-color: var(--brand-blue);\n" .
               "}\n\n" .

               "/* Override hardcoded primary colors with high specificity */\n" .
               "body .bg-primary,\n" .
               "html .bg-primary,\n" .
               ".bg-primary {\n" .
               "  background-color: var(--brand-blue) !important;\n" .
               "}\n\n" .

               "body .text-primary,\n" .
               "html .text-primary,\n" .
               ".text-primary {\n" .
               "  color: var(--brand-blue) !important;\n" .
               "}\n\n" .

               "body .btn-primary,\n" .
               "html .btn-primary,\n" .
               ".btn-primary {\n" .
               "  background-color: var(--brand-blue) !important;\n" .
               "  border-color: var(--brand-blue) !important;\n" .
               "}\n\n" .

               "body .btn-primary:hover,\n" .
               "html .btn-primary:hover,\n" .
               ".btn-primary:hover {\n" .
               "  background-color: var(--brand-blue-dark) !important;\n" .
               "  border-color: var(--brand-blue-dark) !important;\n" .
               "}\n\n" .

               "/* Override Bootstrap CSS variables for landing pages */\n" .
               ":root {\n" .
               "  --bs-primary: var(--brand-blue) !important;\n" .
               "  --bs-primary-rgb: var(--brand-blue-rgb) !important;\n" .
               "}\n\n" .

               "/* Brand color rotating cards */\n" .
               "body .rotating-card-1,\n" .
               ".rotating-card-1 { background-color: var(--brand-yellow) !important; }\n" .

               "body .rotating-card-2,\n" .
               ".rotating-card-2 { background-color: var(--brand-red) !important; }\n" .

               "body .rotating-card-3,\n" .
               ".rotating-card-3 { background-color: var(--brand-green) !important; }\n" .

               "body .rotating-card-4,\n" .
               ".rotating-card-4 { background-color: var(--brand-blue) !important; }\n\n" .

               "/* Primary color class for landing pages */\n" .
               "body .primary-color,\n" .
               ".primary-color {\n" .
               "  color: var(--brand-blue) !important;\n" .
               "}\n\n";
    }

    /**
     * Format colors for CSS generation
     *
     * @param \Illuminate\Database\Eloquent\Collection $colors
     * @return array
     */
    private function formatColorsForCss($colors)
    {
        $formatted = [];

        foreach ($colors as $setting) {
            $parts = explode('_', $setting->setting_key);
            $colorName = $parts[0];
            $theme = $parts[1] ?? 'light';

            if (!isset($formatted[$colorName])) {
                $formatted[$colorName] = [];
            }

            $formatted[$colorName][$theme] = $setting->setting_value;
        }

        return $formatted;
    }

    /**
     * Convert hex color to RGB values
     *
     * @param string $hex
     * @return string
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    /**
     * Lighten a hex color
     *
     * @param string $hex
     * @param int $percent
     * @return string
     */
    private function lightenColor($hex, $percent)
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = min(255, $r + ($percent * 255 / 100));
        $g = min(255, $g + ($percent * 255 / 100));
        $b = min(255, $b + ($percent * 255 / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Darken a hex color
     *
     * @param string $hex
     * @param int $percent
     * @return string
     */
    private function darkenColor($hex, $percent)
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, $r - ($percent * 255 / 100));
        $g = max(0, $g - ($percent * 255 / 100));
        $b = max(0, $b - ($percent * 255 / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Clear theme CSS cache
     *
     * @return bool
     */
    public static function clearThemeCache()
    {
        // Clear specific cache patterns
        $roles = ['admin', 'provider', 'handyman', 'customer', 'default'];
        $themes = ['light', 'dark'];

        // Clear theme CSS cache for all role/theme combinations
        foreach ($roles as $role) {
            foreach ($themes as $theme) {
                $cacheKeys = [
                    "theme_css_{$role}_{$theme}_latest",
                    "theme_css_{$role}_{$theme}_*"
                ];

                foreach ($cacheKeys as $key) {
                    if (str_contains($key, '*')) {
                        // For wildcard patterns, we need to clear all versions
                        for ($i = 1; $i <= 100; $i++) {
                            Cache::forget(str_replace('*', $i, $key));
                        }
                    } else {
                        Cache::forget($key);
                    }
                }
            }
        }

        // Clear landing page CSS cache
        foreach ($themes as $theme) {
            Cache::forget("landing_css_{$theme}");
        }

        // Clear other theme-related caches
        Cache::forget('mobile_theme_colors');
        foreach ($roles as $role) {
            Cache::forget("mobile_theme_role_{$role}");
        }

        // Clear theme variables cache
        foreach ($roles as $role) {
            Cache::forget("theme_variables_{$role}");
        }

        // Clear theme version cache to force regeneration
        Cache::forget('theme_version');

        return true;
    }
}
