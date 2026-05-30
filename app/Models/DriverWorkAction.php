<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverWorkAction extends Model
{
    protected $fillable = [
        'farm_work_log_id',
        'driver_id',
        'tractor_id',
        'zone_id',
        'action_type',
        'lat',
        'lng',
        'note',
        'action_at',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'action_at' => 'datetime',
    ];

    public function farmWorkLog()
    {
        return $this->belongsTo(FarmWorkLog::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}