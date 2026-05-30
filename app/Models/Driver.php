<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'id_card_no',
        'status',
        'created_by',
        'updated_by',
    ];

    public function farmWorkLogs()
    {
        return $this->hasMany(FarmWorkLog::class);
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