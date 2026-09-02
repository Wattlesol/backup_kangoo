<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThemeController extends Controller
{
    /**
     * Get theme colors for mobile apps
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getThemeColors()
    {
        try {
            // Cache the theme colors for 1 hour
            $themeData = Cache::remember('mobile_theme_colors', 3600, function () {
                return $this->buildThemeData();
            });

            return response()->json([
                'success' => true,
                'data' => $themeData,
                'version' => $this->getThemeVersion(),
                'last_updated' => $this->getLastUpdated()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch theme colors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get theme colors for specific role
     * 
     * @param string $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoleTheme($role)
    {
        try {
            $validRoles = ['admin', 'provider', 'handyman', 'customer'];
            
            if (!in_array($role, $validRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role specified'
                ], 400);
            }

            $themeData = Cache::remember("mobile_theme_role_{$role}", 3600, function () use ($role) {
                return $this->buildRoleThemeData($role);
            });

            return response()->json([
                'success' => true,
                'data' => $themeData,
                'role' => $role,
                'version' => $this->getThemeVersion()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role theme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if theme has been updated since given version
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkThemeUpdate(Request $request)
    {
        $clientVersion = $request->input('version');
        $currentVersion = $this->getThemeVersion();

        return response()->json([
            'success' => true,
            'has_update' => $clientVersion !== $currentVersion,
            'current_version' => $currentVersion,
            'client_version' => $clientVersion
        ]);
    }

    /**
     * Build complete theme data structure
     * 
     * @return array
     */
    private function buildThemeData()
    {
        $brandColors = ThemeSetting::getByGroup('brand_colors');
        $roleColors = ThemeSetting::getByGroup('role_colors');

        return [
            'brand_colors' => $this->formatColorsForMobile($brandColors, 'brand'),
            'role_colors' => $this->formatColorsForMobile($roleColors, 'role'),
            'theme_metadata' => $this->getThemeMetadata()
        ];
    }

    /**
     * Build theme data for specific role
     * 
     * @param string $role
     * @return array
     */
    private function buildRoleThemeData($role)
    {
        $brandColors = ThemeSetting::getByGroup('brand_colors');
        $roleColors = ThemeSetting::where('setting_group', 'role_colors')
            ->where('setting_key', 'like', $role . '_%')
            ->get();

        return [
            'brand_colors' => $this->formatColorsForMobile($brandColors, 'brand'),
            'role_colors' => $this->formatColorsForMobile($roleColors, 'role'),
            'primary_role' => $role,
            'role_metadata' => $this->getRoleMetadata($role)
        ];
    }

    /**
     * Format colors for mobile app consumption
     * 
     * @param \Illuminate\Database\Eloquent\Collection $colors
     * @param string $type
     * @return array
     */
    private function formatColorsForMobile($colors, $type)
    {
        $formatted = [];
        $metadata = $this->getColorMetadata();

        foreach ($colors as $setting) {
            $parts = explode('_', $setting->setting_key);
            $colorName = $parts[0];
            $theme = $parts[1] ?? 'light';

            if (!isset($formatted[$colorName])) {
                $formatted[$colorName] = [
                    'name' => $colorName,
                    'display_name' => $metadata[$type][$colorName]['display_name'] ?? ucfirst($colorName),
                    'description' => $metadata[$type][$colorName]['description'] ?? '',
                    'usage' => $metadata[$type][$colorName]['usage'] ?? '',
                    'light' => '#000000',
                    'dark' => '#000000'
                ];
            }

            $formatted[$colorName][$theme] = $setting->setting_value;
        }

        return array_values($formatted);
    }

    /**
     * Get theme version for cache busting
     * 
     * @return string
     */
    private function getThemeVersion()
    {
        $lastUpdate = ThemeSetting::max('updated_at');
        return md5($lastUpdate ?? 'default');
    }

    /**
     * Get last updated timestamp
     * 
     * @return string|null
     */
    private function getLastUpdated()
    {
        return ThemeSetting::max('updated_at');
    }

    /**
     * Get theme metadata
     * 
     * @return array
     */
    private function getThemeMetadata()
    {
        return [
            'app_name' => config('app.name'),
            'supported_themes' => ['light', 'dark'],
            'default_theme' => 'light',
            'color_format' => 'hex',
            'api_version' => '1.0'
        ];
    }

    /**
     * Get role-specific metadata
     * 
     * @param string $role
     * @return array
     */
    private function getRoleMetadata($role)
    {
        $metadata = [
            'admin' => [
                'display_name' => 'Administrator',
                'icon' => 'admin_panel_settings',
                'primary_color_usage' => 'Navigation, buttons, highlights'
            ],
            'provider' => [
                'display_name' => 'Service Provider',
                'icon' => 'store',
                'primary_color_usage' => 'Service cards, action buttons'
            ],
            'handyman' => [
                'display_name' => 'Handyman',
                'icon' => 'build',
                'primary_color_usage' => 'Job cards, status indicators'
            ],
            'customer' => [
                'display_name' => 'Customer',
                'icon' => 'person',
                'primary_color_usage' => 'Booking flow, primary actions'
            ]
        ];

        return $metadata[$role] ?? [];
    }

    /**
     * Get color metadata for descriptions
     * 
     * @return array
     */
    private function getColorMetadata()
    {
        return [
            'brand' => [
                'yellow' => [
                    'display_name' => 'Yellow',
                    'description' => 'Warm, energetic brand color',
                    'usage' => 'Landing page highlights, call-to-action buttons'
                ],
                'red' => [
                    'display_name' => 'Red',
                    'description' => 'Bold, attention-grabbing color',
                    'usage' => 'Urgent notifications, error states'
                ],
                'green' => [
                    'display_name' => 'Green',
                    'description' => 'Success and growth color',
                    'usage' => 'Success messages, completed states'
                ],
                'blue' => [
                    'display_name' => 'Blue',
                    'description' => 'Trust and reliability color',
                    'usage' => 'Primary actions, links, information'
                ]
            ],
            'role' => [
                'admin' => [
                    'display_name' => 'Admin Purple',
                    'description' => 'Administrative interface color',
                    'usage' => 'Admin dashboard, management tools'
                ],
                'provider' => [
                    'display_name' => 'Provider Red',
                    'description' => 'Service provider interface color',
                    'usage' => 'Provider dashboard, service management'
                ],
                'handyman' => [
                    'display_name' => 'Handyman Green',
                    'description' => 'Handyman interface color',
                    'usage' => 'Job management, task completion'
                ],
                'customer' => [
                    'display_name' => 'Customer Blue',
                    'description' => 'Customer interface color',
                    'usage' => 'Booking interface, customer portal'
                ]
            ]
        ];
    }
}
