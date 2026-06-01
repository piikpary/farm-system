<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneBlock extends Model
{
    protected $fillable = [
        'zone_id',
        'block_code',
        'name',
        'area',
        'center_lat',
        'center_lng',
        'polygon_coordinates',
        'location_note',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'center_lat' => 'decimal:8',
        'center_lng' => 'decimal:8',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function blockRegisters()
    {
        return $this->hasMany(BlockRegister::class);
    }

    public function activeRegister()
    {
        return $this->hasOne(BlockRegister::class)->where('status', 'active')->latest();
    }
}