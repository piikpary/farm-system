<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiHelpTopic;

class AiHelpTopicSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            [
                'module' => 'tractors',
                'title' => 'How to create tractor',
                'keywords' => 'tractor, create tractor, add tractor, machine, plate number',
                'content' => '
Tractor module is used to store tractor or machine information.

To create a tractor:
1. Go to sidebar menu: Tractors.
2. Click Add Tractor.
3. Input Tractor No, Name, Model, Plate No, Fuel Capacity, and Current Meter.
4. Choose status Active.
5. Click Save Tractor.

Example:
Tractor No: T-01
Name: John Deere 5050D
Model: 2024
Plate No: 2AB-1234
Fuel Capacity: 60
Current Meter: 1200

After creating tractor, it can be selected when creating Farm Work Logs.
                ',
            ],
            [
                'module' => 'drivers',
                'title' => 'How to create driver',
                'keywords' => 'driver, create driver, add driver, phone, id card',
                'content' => '
Driver module is used to manage tractor drivers.

To create driver:
1. Go to Drivers.
2. Click Add Driver.
3. Input Name, Phone, ID Card No, Address.
4. Choose Active status.
5. Click Save Driver.

After creating driver, it can be selected in Work Log.
                ',
            ],
            [
                'module' => 'zones',
                'title' => 'How to create zone',
                'keywords' => 'zone, create zone, add zone, map, land boundary',
                'content' => '
Zone module is used to manage farm land areas.

To create zone:
1. Go to Zones.
2. Click Add Zone.
3. Input Zone Code, Name, Total Area, Location Note.
4. Save Zone.
5. Click Map to draw land boundary.
6. Click on map at least 3 points.
7. Click Save Map.

Example:
Zone Code: U-121
Name: Zone A
Total Area: 2.00
                ',
            ],
            [
                'module' => 'task_categories',
                'title' => 'How to create task category',
                'keywords' => 'task category, work type, standard fuel, hectare per hour',
                'content' => '
Task Category is used to define work type and fuel standard.

To create task category:
1. Go to Task Categories.
2. Click Add Task Category.
3. Input Name.
4. Input Standard Fuel / Hectare.
5. Input Standard Hectare / Hour.
6. Save.

Example:
Name: Plowing
Standard Fuel / Hectare: 10
Standard Hectare / Hour: 2

This standard is used to calculate request fuel and report comparison.
                ',
            ],
            [
                'module' => 'stock_fuel',
                'title' => 'How stock fuel works',
                'keywords' => 'stock fuel, fuel stock, add fuel, adjust fuel, diesel stock',
                'content' => '
Stock Fuel module controls diesel stock balance.

How to add stock:
1. Go to Stock Fuel.
2. Click Add / Adjust Fuel.
3. Choose Create New Stock or existing stock.
4. Input stock name, minimum alert, and fuel quantity.
5. Choose Stock In.
6. Save.

When user creates Work Log, diesel used will deduct from current fuel stock automatically.

Example:
Current stock: 1000 L
Work log diesel used: 20 L
New stock: 980 L
                ',
            ],
            [
                'module' => 'work_logs',
                'title' => 'How to create work logs',
                'keywords' => 'work log, add work log, diesel start, diesel refill, diesel end, excel style',
                'content' => '
Work Log records daily tractor work.

To create work logs:
1. Go to Add Work Log.
2. Click Add Row if user wants more rows.
3. Select Date, Tractor, Driver, Zone, Task.
4. Input Hour and Area.
5. Input Diesel Start, Diesel Refill, Diesel End.
6. System calculates Diesel Used automatically.
7. Click Save All Work Logs.

Formula:
Diesel Used = Diesel Start + Diesel Refill - Diesel End

Example:
Diesel Start: 10
Diesel Refill: 20
Diesel End: 10
Diesel Used: 20 L

When saved, stock fuel will decrease by diesel used.
                ',
            ],
            [
                'module' => 'roles',
                'title' => 'How role and permission works',
                'keywords' => 'role, permission, assign permission, user access',
                'content' => '
Role module controls user permission.

To create role:
1. Go to Roles.
2. Click Add Role.
3. Input role name.
4. Select permissions by group.
5. Save role.

Then assign role to user from Users module.

Example:
Role: Staff
Permissions:
- View Dashboard
- View Work Logs
- Add Work Log

If user does not have permission, system shows 403 permission denied.
                ',
            ],
            [
                'module' => 'sidebar_settings',
                'title' => 'How sidebar settings work',
                'keywords' => 'sidebar, menu, show menu, hide menu',
                'content' => '
Sidebar Settings controls which optional menu appears in sidebar.

Default hidden menus:
- Tractors
- Drivers
- Zones
- Task Categories
- Reports

To show menu:
1. Go to Sidebar Settings.
2. Find menu.
3. Click Show.
4. Menu appears in sidebar.

To hide menu:
1. Click Hide.
2. Menu disappears from sidebar.

Important: user also needs permission to access the page.
                ',
            ],
            [
                'module' => 'reports',
                'title' => 'How reports work',
                'keywords' => 'report, fuel report, tractor report, driver report, zone report, task category summary',
                'content' => '
Reports summarize farm operation.

Fuel Report:
Shows fuel usage and comparison.

Tractor Report:
Shows work performance by tractor.

Driver Report:
Shows work performance by driver.

Zone Report:
Shows progress by zone.

Task Category Summary:
Shows final summary by task category with date filter, area, fuel, and working hour.

Reports can be exported to Excel or CSV if user has export permission.
                ',
            ],
        ];

        foreach ($topics as $topic) {
            AiHelpTopic::updateOrCreate(
                [
                    'module' => $topic['module'],
                    'title' => $topic['title'],
                ],
                $topic
            );
        }
    }
}