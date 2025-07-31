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

            return response($css)
                ->header('Content-Type', 'text/css')
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('ETag', md5($css));

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

            return response($css)
                ->header('Content-Type', 'text/css')
                ->header('Cache-Control', 'public, max-age=3600');

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
        return "/* Component-specific theme styles */\n" .
               ".card {\n" .
               "  border-color: rgba(var(--primary-color-rgb), 0.1);\n" .
               "}\n\n" .
               ".text-primary {\n" .
               "  color: var(--primary-color) !important;\n" .
               "}\n\n" .
               ".bg-primary {\n" .
               "  background-color: var(--primary-color) !important;\n" .
               "}\n\n" .
               ".border-primary {\n" .
               "  border-color: var(--primary-color) !important;\n" .
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
        return "/* Landing page components */\n" .
               ".hero-section {\n" .
               "  background: linear-gradient(135deg, var(--brand-blue), var(--brand-green));\n" .
               "}\n\n" .
               ".cta-button {\n" .
               "  background-color: var(--brand-yellow);\n" .
               "  color: #333;\n" .
               "}\n\n" .
               ".feature-card:hover {\n" .
               "  border-color: var(--brand-blue);\n" .
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
        $keys = [
            'theme_css_*',
            'landing_css_*',
            'mobile_theme_colors',
            'mobile_theme_role_*'
        ];

        foreach ($keys as $pattern) {
            Cache::forget($pattern);
        }

        return true;
    }
}
