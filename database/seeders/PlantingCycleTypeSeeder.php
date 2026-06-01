<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlantingCycleType;

class PlantingCycleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'code' => 'PC',
                'name' => 'Plant Cane',
                'description' => 'First planting from cane setts',
            ],
            [
                'code' => 'R1',
                'name' => '1st Ratoon',
                'description' => 'First regrowth after harvest',
            ],
            [
                'code' => 'R2',
                'name' => '2nd Ratoon',
                'description' => 'Second regrowth after harvest',
            ],
            [
                'code' => 'R3',
                'name' => '3rd Ratoon',
                'description' => 'Third regrowth after harvest',
            ],
            [
                'code' => 'RP',
                'name' => 'Replant',
                'description' => 'Old field removed and replanted',
            ],
        ];

        foreach ($items as $item) {
            PlantingCycleType::updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'status' => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );
        }
    }
}