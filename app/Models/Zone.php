<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = [
        'zone_code',
        'name',
        'total_area',
        'center_lat',
        'center_lng',
        'polygon_coordinates',
        'location_note',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_area' => 'decimal:2',
        'center_lat' => 'decimal:7',
        'center_lng' => 'decimal:7',
        'polygon_coordinates' => 'array',
    ];

    public function farmWorkLogs()
    {
        return $this->hasMany(FarmWorkLog::class);
    }

    public function gpsTracks()
    {
        return $this->hasMany(DriverGpsTrack::class);
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