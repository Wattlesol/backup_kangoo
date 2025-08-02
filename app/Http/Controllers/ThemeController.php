<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class ThemeController extends Controller
{
    /**
     * Display theme settings page
     */
    public function index(Request $request)
    {
        try {
            $auth_user = authSession();
            $pageTitle = __('messages.theme_colors');
            $page = $request->page ?? 'theme-colors';

            // Get brand colors
            $brandColors = ThemeSetting::getByGroup('brand_colors');
            if ($brandColors->isEmpty()) {
                $this->createDefaultBrandColors();
                $brandColors = ThemeSetting::getByGroup('brand_colors');
            }
            $brandColorsFormatted = $this->formatColorsForDisplay($brandColors);

            // Get role colors
            $roleColors = ThemeSetting::getByGroup('role_colors');
            if ($roleColors->isEmpty()) {
                $this->createDefaultRoleColors();
                $roleColors = ThemeSetting::getByGroup('role_colors');
            }
            $roleColorsFormatted = $this->formatColorsForDisplay($roleColors);

            // Create a dummy model for form binding
            $themeColors = new \stdClass();

            return view('setting.theme-colors', compact(
                'pageTitle',
                'page',
                'auth_user',
                'brandColorsFormatted',
                'roleColorsFormatted',
                'themeColors'
            ));
        } catch (\Exception $e) {
            \Log::error('Theme Colors Index Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to load theme colors: ' . $e->getMessage());
        }
    }

    /**
     * Get brand colors tab content
     */
    public function brandColors()
    {
        try {
            $brandColors = ThemeSetting::getByGroup('brand_colors');

            // If no brand colors exist, create default ones
            if ($brandColors->isEmpty()) {
                $this->createDefaultBrandColors();
                $brandColors = ThemeSetting::getByGroup('brand_colors');
            }

            $brandColorsFormatted = $this->formatColorsForDisplay($brandColors);

            // Debug: Log the data being passed
            \Log::info('Brand Colors Data:', ['count' => count($brandColorsFormatted), 'data' => $brandColorsFormatted]);

            return view('setting.theme-brand-colors', compact('brandColorsFormatted'));
        } catch (\Exception $e) {
            \Log::error('Brand Colors Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to load brand colors: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get role colors tab content
     */
    public function roleColors()
    {
        try {
            $roleColors = ThemeSetting::getByGroup('role_colors');

            // If no role colors exist, create default ones
            if ($roleColors->isEmpty()) {
                $this->createDefaultRoleColors();
                $roleColors = ThemeSetting::getByGroup('role_colors');
            }

            $roleColorsFormatted = $this->formatColorsForDisplay($roleColors);

            return view('setting.theme-role-colors', compact('roleColorsFormatted'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load role colors: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get preview tab content
     */
    public function previewTab()
    {
        try {
            $brandColors = ThemeSetting::getByGroup('brand_colors');
            $roleColors = ThemeSetting::getByGroup('role_colors');

            $brandColorsFormatted = $this->formatColorsForDisplay($brandColors);
            $roleColorsFormatted = $this->formatColorsForDisplay($roleColors);

            return view('setting.theme-preview', compact('brandColorsFormatted', 'roleColorsFormatted'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load preview: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update theme colors
     */
    public function updateColors(Request $request)
    {
        if (demoUserPermission()) {
            return response()->json(['success' => false, 'message' => trans('messages.demo_permission_denied')]);
        }

        try {
            $colors = $request->input('colors', []);

            foreach ($colors as $group => $groupColors) {
                foreach ($groupColors as $key => $value) {
                    // Validate hex color
                    if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                        return response()->json(['success' => false, 'message' => "Invalid color format for {$key}"]);
                    }

                    ThemeSetting::updateSetting($group, $key, $value);
                }
            }

            // Clear cache
            ThemeSetting::clearCache();

            // Clear dynamic CSS cache for real-time updates
            \App\Http\Controllers\DynamicCssController::clearThemeCache();

            return response()->json(['success' => true, 'message' => trans('messages.update_form', ['form' => trans('messages.theme_colors')])]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update theme colors: ' . $e->getMessage()]);
        }
    }

    /**
     * Add new brand color
     */
    public function addBrandColor(Request $request)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        $validator = Validator::make($request->all(), [
            'color_name' => 'required|string|max:50|alpha',
            'light_color' => 'required|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'dark_color' => 'required|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            $colorName = strtolower($request->color_name);
            
            // Check if color already exists
            $existingLight = ThemeSetting::where('setting_group', 'brand_colors')
                ->where('setting_key', $colorName . '_light')
                ->exists();

            if ($existingLight) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Color with this name already exists'
                ]);
            }

            // Get next sort order
            $maxSort = ThemeSetting::where('setting_group', 'brand_colors')->max('sort_order');

            // Create light and dark variants
            ThemeSetting::create([
                'setting_group' => 'brand_colors',
                'setting_key' => $colorName . '_light',
                'setting_value' => $request->light_color,
                'setting_name' => ucfirst($colorName) . ' (Light)',
                'description' => 'Brand ' . $colorName . ' color for light theme',
                'sort_order' => $maxSort + 1,
                'is_active' => true
            ]);

            ThemeSetting::create([
                'setting_group' => 'brand_colors',
                'setting_key' => $colorName . '_dark',
                'setting_value' => $request->dark_color,
                'setting_name' => ucfirst($colorName) . ' (Dark)',
                'description' => 'Brand ' . $colorName . ' color for dark theme',
                'sort_order' => $maxSort + 2,
                'is_active' => true
            ]);

            ThemeSetting::clearCache();
            \App\Http\Controllers\DynamicCssController::clearThemeCache();

            return response()->json([
                'success' => true,
                'message' => 'Brand color added successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to add brand color: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete brand color
     */
    public function deleteBrandColor(Request $request)
    {
        if (demoUserPermission()) {
            return response()->json(['success' => false, 'message' => trans('messages.demo_permission_denied')]);
        }

        try {
            $colorName = $request->color_name;
            
            ThemeSetting::where('setting_group', 'brand_colors')
                ->whereIn('setting_key', [$colorName . '_light', $colorName . '_dark'])
                ->delete();

            ThemeSetting::clearCache();
            \App\Http\Controllers\DynamicCssController::clearThemeCache();

            return response()->json([
                'success' => true,
                'message' => 'Brand color deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to delete brand color: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate dynamic CSS
     */
    public function generateCSS()
    {
        $css = ThemeSetting::generateCSSVariables();
        
        return response($css)
            ->header('Content-Type', 'text/css')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Preview theme colors
     */
    public function preview(Request $request)
    {
        $colors = $request->input('colors', []);
        
        // Generate preview CSS
        $css = ":root {\n";
        foreach ($colors as $colorData) {
            $group = $colorData['group'];
            $key = $colorData['key'];
            $value = $colorData['value'];
            
            if ($group === 'brand_colors') {
                $css .= "  --brand-{$key}: {$value};\n";
            } elseif ($group === 'role_colors') {
                $css .= "  --role-{$key}: {$value};\n";
            }
        }
        $css .= "}\n";
        
        return response()->json(['css' => $css]);
    }

    /**
     * Reset to default colors
     */
    public function resetToDefaults()
    {
        if (demoUserPermission()) {
            return response()->json(['success' => false, 'message' => trans('messages.demo_permission_denied')]);
        }

        try {
            // Run the seeder to reset colors
            \Artisan::call('db:seed', ['--class' => 'ThemeSettingsSeeder']);

            ThemeSetting::clearCache();
            \App\Http\Controllers\DynamicCssController::clearThemeCache();

            return response()->json(['success' => true, 'message' => 'Theme colors reset to defaults successfully']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reset colors: ' . $e->getMessage()]);
        }
    }

    /**
     * Create default colors
     */
    public function createDefaults()
    {
        if (demoUserPermission()) {
            return response()->json(['success' => false, 'message' => trans('messages.demo_permission_denied')]);
        }

        try {
            // Create default brand colors if they don't exist
            $brandColors = ThemeSetting::getByGroup('brand_colors');
            if ($brandColors->isEmpty()) {
                $this->createDefaultBrandColors();
            }

            // Create default role colors if they don't exist
            $roleColors = ThemeSetting::getByGroup('role_colors');
            if ($roleColors->isEmpty()) {
                $this->createDefaultRoleColors();
            }

            ThemeSetting::clearCache();
            \App\Http\Controllers\DynamicCssController::clearThemeCache();

            return response()->json(['success' => true, 'message' => 'Default colors created successfully']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create default colors: ' . $e->getMessage()]);
        }
    }

    /**
     * Format colors for display in admin interface
     */
    private function formatColorsForDisplay($colors)
    {
        $formatted = [];

        // Role metadata for display
        $roleMetadata = [
            'admin' => [
                'display_name' => 'Admin',
                'icon' => 'fa-user-shield'
            ],
            'provider' => [
                'display_name' => 'Provider',
                'icon' => 'fa-store'
            ],
            'handyman' => [
                'display_name' => 'Handyman',
                'icon' => 'fa-tools'
            ],
            'customer' => [
                'display_name' => 'Customer',
                'icon' => 'fa-user'
            ]
        ];

        foreach ($colors as $setting) {
            $parts = explode('_', $setting->setting_key);
            $colorName = $parts[0];
            $theme = $parts[1] ?? 'light';

            if (!isset($formatted[$colorName])) {
                $baseData = [
                    'name' => ucfirst($colorName),
                    'light' => '',
                    'dark' => ''
                ];

                // Add role metadata if this is a role color
                if (isset($roleMetadata[$colorName])) {
                    $baseData = array_merge($baseData, $roleMetadata[$colorName]);
                }

                $formatted[$colorName] = $baseData;
            }

            $formatted[$colorName][$theme] = $setting->setting_value;
        }

        return $formatted;
    }

    /**
     * Create default brand colors if none exist
     */
    private function createDefaultBrandColors()
    {
        $defaultBrandColors = [
            ['key' => 'yellow_light', 'value' => '#F0B521', 'name' => 'Brand Yellow (Light)', 'order' => 1],
            ['key' => 'yellow_dark', 'value' => '#8D6710', 'name' => 'Brand Yellow (Dark)', 'order' => 2],
            ['key' => 'red_light', 'value' => '#EF5535', 'name' => 'Brand Red (Light)', 'order' => 3],
            ['key' => 'red_dark', 'value' => '#9B1F0B', 'name' => 'Brand Red (Dark)', 'order' => 4],
            ['key' => 'green_light', 'value' => '#2DB665', 'name' => 'Brand Green (Light)', 'order' => 5],
            ['key' => 'green_dark', 'value' => '#005F2D', 'name' => 'Brand Green (Dark)', 'order' => 6],
            ['key' => 'blue_light', 'value' => '#4A75FB', 'name' => 'Brand Blue (Light)', 'order' => 7],
            ['key' => 'blue_dark', 'value' => '#004CB2', 'name' => 'Brand Blue (Dark)', 'order' => 8],
        ];

        foreach ($defaultBrandColors as $color) {
            ThemeSetting::create([
                'setting_group' => 'brand_colors',
                'setting_key' => $color['key'],
                'setting_value' => $color['value'],
                'setting_name' => $color['name'],
                'description' => 'Default brand color',
                'sort_order' => $color['order'],
                'is_active' => true
            ]);
        }
    }

    /**
     * Create default role colors if none exist
     */
    private function createDefaultRoleColors()
    {
        $defaultRoleColors = [
            ['key' => 'admin_light', 'value' => '#5F60B9', 'name' => 'Admin Theme (Light)', 'order' => 1],
            ['key' => 'admin_dark', 'value' => '#4153b3', 'name' => 'Admin Theme (Dark)', 'order' => 2],
            ['key' => 'provider_light', 'value' => '#EF5535', 'name' => 'Provider Theme (Light)', 'order' => 3],
            ['key' => 'provider_dark', 'value' => '#9B1F0B', 'name' => 'Provider Theme (Dark)', 'order' => 4],
            ['key' => 'handyman_light', 'value' => '#2DB665', 'name' => 'Handyman Theme (Light)', 'order' => 5],
            ['key' => 'handyman_dark', 'value' => '#005F2D', 'name' => 'Handyman Theme (Dark)', 'order' => 6],
            ['key' => 'customer_light', 'value' => '#4A75FB', 'name' => 'Customer Theme (Light)', 'order' => 7],
            ['key' => 'customer_dark', 'value' => '#004CB2', 'name' => 'Customer Theme (Dark)', 'order' => 8],
        ];

        foreach ($defaultRoleColors as $color) {
            ThemeSetting::create([
                'setting_group' => 'role_colors',
                'setting_key' => $color['key'],
                'setting_value' => $color['value'],
                'setting_name' => $color['name'],
                'description' => 'Default role color',
                'sort_order' => $color['order'],
                'is_active' => true
            ]);
        }
    }

    /**
     * Get theme colors for API
     */
    public function getThemeColors(Request $request)
    {
        try {
            $brandColors = ThemeSetting::getByGroup('brand_colors');
            $roleColors = ThemeSetting::getByGroup('role_colors');

            $data = [
                'brand_colors' => $this->formatColorsForDisplay($brandColors),
                'role_colors' => $this->formatColorsForDisplay($roleColors)
            ];

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => 'Theme colors fetched successfully',
                    'data' => $data,
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $data,
                'message' => 'Theme colors fetched successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Get theme colors error: ' . $e->getMessage());

            if($request->is('api/*')) {
                return comman_message_response('Failed to fetch theme colors: ' . $e->getMessage());
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch theme colors: ' . $e->getMessage()
            ]);
        }
    }
}
