<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ThemeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_group',
        'setting_key',
        'setting_value',
        'setting_name',
        'description',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Cache key for theme settings
     */
    const CACHE_KEY = 'theme_settings_cache';
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Boot method to clear cache when model is updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function () {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /**
     * Get all theme settings grouped by setting_group
     */
    public static function getAllGrouped()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            return self::where('is_active', true)
                ->orderBy('setting_group')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('setting_group');
        });
    }

    /**
     * Get settings by group
     */
    public static function getByGroup($group)
    {
        $allSettings = self::getAllGrouped();
        return $allSettings->get($group, collect());
    }

    /**
     * Get brand colors formatted for frontend
     */
    public static function getBrandColors()
    {
        $brandColors = self::getByGroup('brand_colors');
        $colors = [];

        foreach ($brandColors as $setting) {
            $parts = explode('_', $setting->setting_key);
            $colorName = $parts[0];
            $theme = $parts[1] ?? 'light';

            if (!isset($colors[$colorName])) {
                $colors[$colorName] = [];
            }
            $colors[$colorName][$theme] = $setting->setting_value;
        }

        return $colors;
    }

    /**
     * Get role colors formatted for frontend
     */
    public static function getRoleColors()
    {
        $roleColors = self::getByGroup('role_colors');
        $colors = [];

        foreach ($roleColors as $setting) {
            $parts = explode('_', $setting->setting_key);
            $roleName = $parts[0];
            $theme = $parts[1] ?? 'light';

            if (!isset($colors[$roleName])) {
                $colors[$roleName] = [];
            }
            $colors[$roleName][$theme] = $setting->setting_value;
        }

        return $colors;
    }

    /**
     * Get specific color value
     */
    public static function getColor($group, $key, $default = '#000000')
    {
        $setting = self::where('setting_group', $group)
            ->where('setting_key', $key)
            ->where('is_active', true)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Update or create a theme setting
     */
    public static function updateSetting($group, $key, $value, $name = null, $description = null)
    {
        return self::updateOrCreate(
            [
                'setting_group' => $group,
                'setting_key' => $key
            ],
            [
                'setting_value' => $value,
                'setting_name' => $name,
                'description' => $description,
                'is_active' => true
            ]
        );
    }

    /**
     * Validation rules for color values
     */
    public static function getValidationRules()
    {
        return [
            'setting_group' => 'required|string|max:50',
            'setting_key' => 'required|string|max:50',
            'setting_value' => 'required|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'setting_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get available setting groups
     */
    public static function getAvailableGroups()
    {
        return [
            'brand_colors' => 'Brand Colors (Landing Pages)',
            'role_colors' => 'Role Colors (Dashboards)'
        ];
    }

    /**
     * Clear theme cache
     */
    public static function clearCache()
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Generate CSS variables from theme settings
     */
    public static function generateCSSVariables()
    {
        $brandColors = self::getBrandColors();
        $roleColors = self::getRoleColors();
        
        $css = ":root {\n";
        
        // Brand colors
        foreach ($brandColors as $colorName => $themes) {
            foreach ($themes as $theme => $value) {
                $css .= "  --brand-{$colorName}-{$theme}: {$value};\n";
            }
        }
        
        // Role colors
        foreach ($roleColors as $roleName => $themes) {
            foreach ($themes as $theme => $value) {
                $css .= "  --role-{$roleName}-{$theme}: {$value};\n";
            }
        }
        
        $css .= "}\n";
        
        return $css;
    }
}
