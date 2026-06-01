<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TractorFieldSetting;

class TractorFieldSettingSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            [
                'field_key' => 'iot_device_id',
                'field_label' => 'IoT Device ID',
                'is_visible' => false,
                'sort_order' => 10,
            ],
            [
                'field_key' => 'current_meter',
                'field_label' => 'Current Meter',
                'is_visible' => false,
                'sort_order' => 20,
            ],
            [
                'field_key' => 'plow_width',
                'field_label' => 'Plow Width',
                'is_visible' => false,
                'sort_order' => 30,
            ],
        ];

        foreach ($fields as $field) {
            TractorFieldSetting::updateOrCreate(
                ['field_key' => $field['field_key']],
                $field
            );
        }
    }
}