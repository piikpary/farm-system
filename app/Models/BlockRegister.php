<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockRegister extends Model
{
    protected $fillable = [
        'zone_block_id',
        'variety',
        'planting_date',
        'planting_cycle_type_id',
        'expected_harvest_date',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'planting_date' => 'date',
        'expected_harvest_date' => 'date',
    ];

    public function zoneBlock()
    {
        return $this->belongsTo(ZoneBlock::class);
    }

    public function plantingCycleType()
    {
        return $this->belongsTo(PlantingCycleType::class);
    }
}