<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */
            ['key' => 'dashboard.view', 'name' => 'View Dashboard', 'group' => 'Dashboard'],
            ['key' => 'dashboard.planning.view', 'name' => 'View Planning Dashboard', 'group' => 'Dashboard'],
            ['key' => 'dashboard.harvesting.view', 'name' => 'View Harvesting Dashboard', 'group' => 'Dashboard'],

            /*
            |--------------------------------------------------------------------------
            | Stock Fuel
            |--------------------------------------------------------------------------
            */
            ['key' => 'stock_fuel.view', 'name' => 'View Stock Fuel', 'group' => 'Stock Fuel'],
            ['key' => 'stock_fuel.create', 'name' => 'Add / Adjust Stock Fuel', 'group' => 'Stock Fuel'],
            ['key' => 'stock_fuel.edit', 'name' => 'Edit Stock Fuel', 'group' => 'Stock Fuel'],
            ['key' => 'stock_fuel.history', 'name' => 'View Fuel History', 'group' => 'Stock Fuel'],

            /*
            |--------------------------------------------------------------------------
            | Work Logs
            |--------------------------------------------------------------------------
            */
            ['key' => 'work_logs.view', 'name' => 'View Work Logs', 'group' => 'Work Logs'],
            ['key' => 'work_logs.create', 'name' => 'Add Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.edit', 'name' => 'Edit Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.delete', 'name' => 'Delete Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.map', 'name' => 'View Work Log Map', 'group' => 'Work Logs'],
            ['key' => 'work_logs.export', 'name' => 'Export Work Logs', 'group' => 'Work Logs'],

            ['key' => 'work_logs.planning.view', 'name' => 'View Planning Work Logs', 'group' => 'Work Logs'],
            ['key' => 'work_logs.planning.create', 'name' => 'Add Planning Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.planning.edit', 'name' => 'Edit Planning Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.planning.delete', 'name' => 'Delete Planning Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.planning.export', 'name' => 'Export Planning Work Logs', 'group' => 'Work Logs'],

            ['key' => 'work_logs.harvesting.view', 'name' => 'View Harvesting Work Logs', 'group' => 'Work Logs'],
            ['key' => 'work_logs.harvesting.create', 'name' => 'Add Harvesting Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.harvesting.edit', 'name' => 'Edit Harvesting Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.harvesting.delete', 'name' => 'Delete Harvesting Work Log', 'group' => 'Work Logs'],
            ['key' => 'work_logs.harvesting.export', 'name' => 'Export Harvesting Work Logs', 'group' => 'Work Logs'],

            /*
            |--------------------------------------------------------------------------
            | Tractors
            |--------------------------------------------------------------------------
            */
            ['key' => 'tractors.view', 'name' => 'View Tractors', 'group' => 'Tractors'],
            ['key' => 'tractors.create', 'name' => 'Add Tractor', 'group' => 'Tractors'],
            ['key' => 'tractors.edit', 'name' => 'Edit Tractor', 'group' => 'Tractors'],
            ['key' => 'tractors.delete', 'name' => 'Delete Tractor', 'group' => 'Tractors'],

            /*
            |--------------------------------------------------------------------------
            | Drivers
            |--------------------------------------------------------------------------
            */
            ['key' => 'drivers.view', 'name' => 'View Drivers', 'group' => 'Drivers'],
            ['key' => 'drivers.create', 'name' => 'Add Driver', 'group' => 'Drivers'],
            ['key' => 'drivers.edit', 'name' => 'Edit Driver', 'group' => 'Drivers'],
            ['key' => 'drivers.delete', 'name' => 'Delete Driver', 'group' => 'Drivers'],

            /*
            |--------------------------------------------------------------------------
            | Zones
            |--------------------------------------------------------------------------
            */
            ['key' => 'zones.view', 'name' => 'View Zones', 'group' => 'Zones'],
            ['key' => 'zones.create', 'name' => 'Add Zone', 'group' => 'Zones'],
            ['key' => 'zones.edit', 'name' => 'Edit Zone', 'group' => 'Zones'],
            ['key' => 'zones.delete', 'name' => 'Delete Zone', 'group' => 'Zones'],
            ['key' => 'zones.map', 'name' => 'Manage Zone Map', 'group' => 'Zones'],

            /*
            |--------------------------------------------------------------------------
            | Task
            |--------------------------------------------------------------------------
            */
            ['key' => 'task_categories.view', 'name' => 'View Task Categories', 'group' => 'Task'],
            ['key' => 'task_categories.create', 'name' => 'Add Task Category', 'group' => 'Task'],
            ['key' => 'task_categories.edit', 'name' => 'Edit Task Category', 'group' => 'Task'],
            ['key' => 'task_categories.delete', 'name' => 'Delete Task Category', 'group' => 'Task'],

            /*
            |--------------------------------------------------------------------------
            | Reports
            |--------------------------------------------------------------------------
            */
            ['key' => 'reports.fuel', 'name' => 'View Fuel Report', 'group' => 'Reports'],
            ['key' => 'reports.tractors', 'name' => 'View Tractor Report', 'group' => 'Reports'],
            ['key' => 'reports.drivers', 'name' => 'View Driver Report', 'group' => 'Reports'],
            ['key' => 'reports.zones', 'name' => 'View Zone Report', 'group' => 'Reports'],
            ['key' => 'reports.task_category_summary', 'name' => 'View Task Category Summary Report', 'group' => 'Reports'],
            ['key' => 'reports.export', 'name' => 'Export Reports', 'group' => 'Reports'],

            /*
            |--------------------------------------------------------------------------
            | Settings
            |--------------------------------------------------------------------------
            */
            ['key' => 'sidebar_settings.view', 'name' => 'View Sidebar Settings', 'group' => 'Settings'],
            ['key' => 'sidebar_settings.update', 'name' => 'Update Sidebar Settings', 'group' => 'Settings'],

            ['key' => 'ai_settings.view', 'name' => 'View AI Settings', 'group' => 'Settings'],
            ['key' => 'ai_settings.update', 'name' => 'Update AI Settings', 'group' => 'Settings'],

            /*
            |--------------------------------------------------------------------------
            | Users
            |--------------------------------------------------------------------------
            */
            ['key' => 'users.view', 'name' => 'View Users', 'group' => 'Users'],
            ['key' => 'users.create', 'name' => 'Add User', 'group' => 'Users'],
            ['key' => 'users.edit', 'name' => 'Edit User', 'group' => 'Users'],
            ['key' => 'users.delete', 'name' => 'Delete User', 'group' => 'Users'],

            /*
            |--------------------------------------------------------------------------
            | Roles
            |--------------------------------------------------------------------------
            */
            ['key' => 'roles.view', 'name' => 'View Roles', 'group' => 'Roles'],
            ['key' => 'roles.create', 'name' => 'Add Role', 'group' => 'Roles'],
            ['key' => 'roles.edit', 'name' => 'Edit Role', 'group' => 'Roles'],
            ['key' => 'roles.delete', 'name' => 'Delete Role', 'group' => 'Roles'],
            ['key' => 'roles.assign_permissions', 'name' => 'Assign Role Permissions', 'group' => 'Roles'],

            /*
            |--------------------------------------------------------------------------
            | Master Data
            |--------------------------------------------------------------------------
            */
            ['key' => 'zone_blocks.view', 'name' => 'View Zone Blocks', 'group' => 'Master Data'],
            ['key' => 'zone_blocks.create', 'name' => 'Create Zone Blocks', 'group' => 'Master Data'],
            ['key' => 'zone_blocks.edit', 'name' => 'Edit Zone Blocks', 'group' => 'Master Data'],
            ['key' => 'zone_blocks.delete', 'name' => 'Delete Zone Blocks', 'group' => 'Master Data'],

            ['key' => 'planting_cycle_types.view', 'name' => 'View Planting Cycle Types', 'group' => 'Master Data'],
            ['key' => 'planting_cycle_types.create', 'name' => 'Create Planting Cycle Types', 'group' => 'Master Data'],
            ['key' => 'planting_cycle_types.edit', 'name' => 'Edit Planting Cycle Types', 'group' => 'Master Data'],
            ['key' => 'planting_cycle_types.delete', 'name' => 'Delete Planting Cycle Types', 'group' => 'Master Data'],

            /*
            |--------------------------------------------------------------------------
            | Farm Operation
            |--------------------------------------------------------------------------
            */
            ['key' => 'block_registers.view', 'name' => 'View Block Registers', 'group' => 'Farm Operation'],
            ['key' => 'block_registers.create', 'name' => 'Create Block Registers', 'group' => 'Farm Operation'],
            ['key' => 'block_registers.edit', 'name' => 'Edit Block Registers', 'group' => 'Farm Operation'],
            ['key' => 'block_registers.delete', 'name' => 'Delete Block Registers', 'group' => 'Farm Operation'],

            ['key' => 'work_plans.view', 'name' => 'View Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.create', 'name' => 'Create Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.edit', 'name' => 'Edit Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.delete', 'name' => 'Delete Work Plans', 'group' => 'Farm Operation'],

            ['key' => 'work_plans.planning.view', 'name' => 'View Planning Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.planning.create', 'name' => 'Create Planning Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.planning.edit', 'name' => 'Edit Planning Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.planning.delete', 'name' => 'Delete Planning Work Plans', 'group' => 'Farm Operation'],

            ['key' => 'work_plans.harvesting.view', 'name' => 'View Harvesting Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.harvesting.create', 'name' => 'Create Harvesting Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.harvesting.edit', 'name' => 'Edit Harvesting Work Plans', 'group' => 'Farm Operation'],
            ['key' => 'work_plans.harvesting.delete', 'name' => 'Delete Harvesting Work Plans', 'group' => 'Farm Operation'],
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