<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmWorkLog extends Model
{
    protected $fillable = [
        'work_date',
        'tractor_id',
        'driver_id',
        'zone_id',
        'task_category_id',

        'working_duration',
        'working_area',

        'diesel_start',
        'diesel_refill',
        'diesel_end',

        'diesel_consumed',
        'diesel_per_hectare',
        'hectare_per_hour',

        'request_fuel_per_hectare',
        'request_fuel',
        'variance_fuel',

        'note',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'work_date' => 'date',
        'working_duration' => 'decimal:2',
        'working_area' => 'decimal:2',
        'diesel_start' => 'decimal:2',
        'diesel_refill' => 'decimal:2',
        'diesel_end' => 'decimal:2',
        'diesel_consumed' => 'decimal:2',
        'diesel_per_hectare' => 'decimal:2',
        'hectare_per_hour' => 'decimal:2',
        'request_fuel_per_hectare' => 'decimal:2',
        'request_fuel' => 'decimal:2',
        'variance_fuel' => 'decimal:2',
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
}