<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelStock extends Model
{
    protected $fillable = [
        'name',
        'opening_stock',
        'current_stock',
        'minimum_stock_alert',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_stock' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'minimum_stock_alert' => 'decimal:2',
    ];

    public function transactions()
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
}