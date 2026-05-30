<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelTransaction extends Model
{
    protected $fillable = [
        'fuel_stock_id',
        'transaction_date',
        'type',
        'tractor_id',
        'farm_work_log_id',
        'quantity',
        'balance_after',
        'reference_no',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function fuelStock()
    {
        return $this->belongsTo(FuelStock::class);
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function farmWorkLog()
    {
        return $this->belongsTo(FarmWorkLog::class);
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