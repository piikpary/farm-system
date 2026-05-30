<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tractor extends Model
{
    protected $fillable = [
        'tractor_no',
        'name',
        'model',
        'plate_no',
        'fuel_capacity',
        'current_meter',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fuel_capacity' => 'decimal:2',
        'current_meter' => 'decimal:2',
    ];

    public function farmWorkLogs()
    {
        return $this->hasMany(FarmWorkLog::class);
    }

    public function fuelTransactions()
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class);
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