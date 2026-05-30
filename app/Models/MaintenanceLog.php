<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    protected $fillable = [
        'tractor_id',
        'maintenance_date',
        'maintenance_type',
        'meter_reading',
        'cost',
        'description',
        'next_maintenance_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'meter_reading' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}