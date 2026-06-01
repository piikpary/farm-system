<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class FarmWorkLog extends Model
{
    protected $fillable = [
    'work_date',
    'work_status',
    'started_at',
    'finished_at',

    'tractor_id',
    'driver_id',
    'zone_id',
    'task_category_id',

    'working_duration',
    'working_area',

    'gps_distance_meters',
    'estimated_plowed_area',
    'gps_progress_percent',
    'driver_access_token',
    'diesel_start',
    'diesel_refill',
    'diesel_end',
    'diesel_consumed',
    'diesel_per_hectare',
    'hectare_per_hour',
    'request_fuel_per_hectare',
    'request_fuel',
    'variance_fuel',
    'zone_block_id',
    'note',
    'created_by',
    'updated_by',
];

protected $casts = [
    'work_date' => 'date',
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
    'working_duration' => 'decimal:2',
    'working_area' => 'decimal:2',
    'gps_distance_meters' => 'decimal:2',
    'estimated_plowed_area' => 'decimal:4',
    'gps_progress_percent' => 'decimal:2',
];

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function taskCategory()
    {
        return $this->belongsTo(TaskCategory::class);
    }

    public function fuelTransactions()
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function gpsTracks()
    {
        return $this->hasMany(DriverGpsTrack::class);
    }
    public function workActions()
    {
        return $this->hasMany(DriverWorkAction::class);
    }
    public function zoneBlock()
{
    return $this->belongsTo(\App\Models\ZoneBlock::class);
}
    protected static function booted()
{
    static::creating(function ($log) {
        if (empty($log->driver_access_token)) {
            $log->driver_access_token = Str::random(64);
        }

        if (empty($log->work_status)) {
            $log->work_status = 'pending';
        }
    });
}
    
}