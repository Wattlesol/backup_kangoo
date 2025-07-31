<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $themeSettings = [
            // Brand Colors for Landing Pages
            [
                'setting_group' => 'brand_colors',
                'setting_key' => 'yellow_light',
                'setting_value' => '#F0B521',
                'setting_name' => 'Brand Yellow (Light)',
                'description' => 'Primary yellow color for light theme',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'setting_group' => 'brand_colors',
                'setting_key' => 'yellow_dark',
                'setting_value' => '#8D6710',
                'setting_name' => 'Brand Yellow (Dark)',
                'description' => 'Primary yellow color for dark theme',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'setting_group' => 'brand_colors',
                'setting_key' => 'red_light',
                'setting_value' => '#EF5535',
                'setting_name' => 'Brand Red (Light)',
                'description' => 'Primary red color for light theme',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'setting_group' => 'brand_colors',
                'setting_key' => 'red_dark',
                'setting_value' => '#9B1F0B',
                'setting_name' => 'Brand Red (Dark)',
                'description' => 'Primary red color for dark theme',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'setting_group' => 'brand_colors',
                'setting_key' => 'green_light',
                'setting_value' => '#2DB665',
                'setting_name' => 'Brand Green (Light)',
                'description' => 'Primary green color for light theme',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'setting_group' => 'brand_colors',
                'setting_key' => 'green_dark',
                'setting_value' => '#005F2D',
                'setting_name' => 'Brand Green (Dark)',
                'description' => 'Primary green color for dark theme',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'setting_group' => 'brand_colors',
                'setting_key' => 'blue_light',
                'setting_value' => '#4A75FB',
                'setting_name' => 'Brand Blue (Light)',
                'description' => 'Primary blue color for light theme',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'setting_group' => 'brand_colors',
                'setting_key' => 'blue_dark',
                'setting_value' => '#004CB2',
                'setting_name' => 'Brand Blue (Dark)',
                'description' => 'Primary blue color for dark theme',
                'sort_order' => 8,
                'is_active' => true,
            ],

            // Role-based Colors for Dashboards
            [
                'setting_group' => 'role_colors',
                'setting_key' => 'admin_light',
                'setting_value' => '#5F60B9',
                'setting_name' => 'Admin Theme (Light)',
                'description' => 'Primary color for admin dashboard in light theme',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'setting_group' => 'role_colors',
                'setting_key' => 'admin_dark',
                'setting_value' => '#4153b3',
                'setting_name' => 'Admin Theme (Dark)',
                'description' => 'Primary color for admin dashboard in dark theme',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'setting_group' => 'role_colors',
                'setting_key' => 'provider_light',
                'setting_value' => '#EF5535',
                'setting_name' => 'Provider Theme (Light)',
                'description' => 'Primary color for provider dashboard in light theme',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'setting_group' => 'role_colors',
                'setting_key' => 'provider_dark',
                'setting_value' => '#9B1F0B',
                'setting_name' => 'Provider Theme (Dark)',
                'description' => 'Primary color for provider dashboard in dark theme',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'setting_group' => 'role_colors',
                'setting_key' => 'handyman_light',
                'setting_value' => '#2DB665',
                'setting_name' => 'Handyman Theme (Light)',
                'description' => 'Primary color for handyman dashboard in light theme',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'setting_group' => 'role_colors',
                'setting_key' => 'handyman_dark',
                'setting_value' => '#005F2D',
                'setting_name' => 'Handyman Theme (Dark)',
                'description' => 'Primary color for handyman dashboard in dark theme',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'setting_group' => 'role_colors',
                'setting_key' => 'customer_light',
                'setting_value' => '#4A75FB',
                'setting_name' => 'Customer Theme (Light)',
                'description' => 'Primary color for customer dashboard in light theme',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'setting_group' => 'role_colors',
                'setting_key' => 'customer_dark',
                'setting_value' => '#004CB2',
                'setting_name' => 'Customer Theme (Dark)',
                'description' => 'Primary color for customer dashboard in dark theme',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($themeSettings as $setting) {
            DB::table('theme_settings')->updateOrInsert(
                [
                    'setting_group' => $setting['setting_group'],
                    'setting_key' => $setting['setting_key']
                ],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}
