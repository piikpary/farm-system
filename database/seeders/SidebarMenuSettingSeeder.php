<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SidebarMenuSetting;

class SidebarMenuSettingSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'menu_key' => 'tractors',
                'menu_label' => 'Tractors',
                'menu_group' => 'master_data',
                'is_visible' => false,
                'sort_order' => 10,
            ],
            [
                'menu_key' => 'drivers',
                'menu_label' => 'Drivers',
                'menu_group' => 'master_data',
                'is_visible' => false,
                'sort_order' => 20,
            ],
            [
                'menu_key' => 'zones',
                'menu_label' => 'Zones',
                'menu_group' => 'master_data',
                'is_visible' => false,
                'sort_order' => 30,
            ],
            [
                'menu_key' => 'task_categories',
                'menu_label' => 'Task Categories',
                'menu_group' => 'master_data',
                'is_visible' => false,
                'sort_order' => 40,
            ],

            [
                'menu_key' => 'fuel_report',
                'menu_label' => 'Fuel Report',
                'menu_group' => 'reports',
                'is_visible' => false,
                'sort_order' => 50,
            ],
            [
                'menu_key' => 'tractor_report',
                'menu_label' => 'Tractor Report',
                'menu_group' => 'reports',
                'is_visible' => false,
                'sort_order' => 60,
            ],
            [
                'menu_key' => 'driver_report',
                'menu_label' => 'Driver Report',
                'menu_group' => 'reports',
                'is_visible' => false,
                'sort_order' => 70,
            ],
            [
                'menu_key' => 'zone_report',
                'menu_label' => 'Zone Report',
                'menu_group' => 'reports',
                'is_visible' => false,
                'sort_order' => 80,
            ],
            [
                'menu_key' => 'task_category_summary_report',
                'menu_label' => 'Task Category Summary',
                'menu_group' => 'reports',
                'is_visible' => false,
                'sort_order' => 90,
            ],
        ];

        foreach ($menus as $menu) {
            $setting = SidebarMenuSetting::firstOrCreate(
                ['menu_key' => $menu['menu_key']],
                $menu
            );

            // Update text/order only, but do not reset user show/hide setting
            $setting->update([
                'menu_label' => $menu['menu_label'],
                'menu_group' => $menu['menu_group'],
                'sort_order' => $menu['sort_order'],
            ]);
        }
    }
}