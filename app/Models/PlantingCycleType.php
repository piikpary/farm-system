<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantingCycleType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function blockRegisters()
    {
        return $this->hasMany(BlockRegister::class);
    }
}