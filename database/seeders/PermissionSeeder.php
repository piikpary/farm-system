<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['key' => 'dashboard.view', 'name' => 'View Dashboard', 'group' => 'dashboard'],

            ['key' => 'stock_fuel.view', 'name' => 'View Stock Fuel', 'group' => 'stock_fuel'],
            ['key' => 'stock_fuel.create', 'name' => 'Add / Adjust Stock Fuel', 'group' => 'stock_fuel'],
            ['key' => 'stock_fuel.edit', 'name' => 'Edit Stock Fuel', 'group' => 'stock_fuel'],
            ['key' => 'stock_fuel.history', 'name' => 'View Fuel History', 'group' => 'stock_fuel'],

            ['key' => 'work_logs.view', 'name' => 'View Work Logs', 'group' => 'work_logs'],
            ['key' => 'work_logs.create', 'name' => 'Add Work Log', 'group' => 'work_logs'],
            ['key' => 'work_logs.edit', 'name' => 'Edit Work Log', 'group' => 'work_logs'],
            ['key' => 'work_logs.delete', 'name' => 'Delete Work Log', 'group' => 'work_logs'],
            ['key' => 'work_logs.map', 'name' => 'View Work Log Map', 'group' => 'work_logs'],
            ['key' => 'work_logs.export', 'name' => 'Export Work Logs', 'group' => 'work_logs'],

            ['key' => 'tractors.view', 'name' => 'View Tractors', 'group' => 'tractors'],
            ['key' => 'tractors.create', 'name' => 'Add Tractor', 'group' => 'tractors'],
            ['key' => 'tractors.edit', 'name' => 'Edit Tractor', 'group' => 'tractors'],
            ['key' => 'tractors.delete', 'name' => 'Delete Tractor', 'group' => 'tractors'],

            ['key' => 'drivers.view', 'name' => 'View Drivers', 'group' => 'drivers'],
            ['key' => 'drivers.create', 'name' => 'Add Driver', 'group' => 'drivers'],
            ['key' => 'drivers.edit', 'name' => 'Edit Driver', 'group' => 'drivers'],
            ['key' => 'drivers.delete', 'name' => 'Delete Driver', 'group' => 'drivers'],

            ['key' => 'zones.view', 'name' => 'View Zones', 'group' => 'zones'],
            ['key' => 'zones.create', 'name' => 'Add Zone', 'group' => 'zones'],
            ['key' => 'zones.edit', 'name' => 'Edit Zone', 'group' => 'zones'],
            ['key' => 'zones.delete', 'name' => 'Delete Zone', 'group' => 'zones'],
            ['key' => 'zones.map', 'name' => 'Manage Zone Map', 'group' => 'zones'],

            ['key' => 'task_categories.view', 'name' => 'View Task Categories', 'group' => 'task_categories'],
            ['key' => 'task_categories.create', 'name' => 'Add Task Category', 'group' => 'task_categories'],
            ['key' => 'task_categories.edit', 'name' => 'Edit Task Category', 'group' => 'task_categories'],
            ['key' => 'task_categories.delete', 'name' => 'Delete Task Category', 'group' => 'task_categories'],

            ['key' => 'reports.fuel', 'name' => 'View Fuel Report', 'group' => 'reports'],
            ['key' => 'reports.tractors', 'name' => 'View Tractor Report', 'group' => 'reports'],
            ['key' => 'reports.drivers', 'name' => 'View Driver Report', 'group' => 'reports'],
            ['key' => 'reports.zones', 'name' => 'View Zone Report', 'group' => 'reports'],
            ['key' => 'reports.task_category_summary', 'name' => 'View Task Category Summary Report', 'group' => 'reports'],
            ['key' => 'reports.export', 'name' => 'Export Reports', 'group' => 'reports'],

            ['key' => 'sidebar_settings.view', 'name' => 'View Sidebar Settings', 'group' => 'settings'],
            ['key' => 'sidebar_settings.update', 'name' => 'Update Sidebar Settings', 'group' => 'settings'],

            ['key' => 'users.view', 'name' => 'View Users', 'group' => 'users'],
            ['key' => 'users.create', 'name' => 'Add User', 'group' => 'users'],
            ['key' => 'users.edit', 'name' => 'Edit User', 'group' => 'users'],
            ['key' => 'users.delete', 'name' => 'Delete User', 'group' => 'users'],

            ['key' => 'roles.view', 'name' => 'View Roles', 'group' => 'roles'],
            ['key' => 'roles.create', 'name' => 'Add Role', 'group' => 'roles'],
            ['key' => 'roles.edit', 'name' => 'Edit Role', 'group' => 'roles'],
            ['key' => 'roles.delete', 'name' => 'Delete Role', 'group' => 'roles'],
            ['key' => 'roles.assign_permissions', 'name' => 'Assign Role Permissions', 'group' => 'roles'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['permission_key' => $permission['key']],
                [
                    'name' => $permission['name'],
                    'group_name' => $permission['group'],
                    'status' => 'active',
                ]
            );
        }
    }
}