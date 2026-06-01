<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tractor extends Model
{
    protected $fillable = [
        'tractor_no',
        'iot_device_id',
        'name',
        'model',
        'plate_no',
        'fuel_capacity',
        'current_meter',
        'plow_width',
        'last_lat',
        'last_lng',
        'last_seen_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fuel_capacity' => 'decimal:2',
        'current_meter' => 'decimal:2',
        'plow_width' => 'decimal:2',
        'last_lat' => 'decimal:7',
        'last_lng' => 'decimal:7',
        'last_seen_at' => 'datetime',
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
    public function workLogs()
    {
        return $this->hasMany(FarmWorkLog::class);
    }

    public function gpsTracks()
    {
        return $this->hasMany(DriverGpsTrack::class);
    }
}