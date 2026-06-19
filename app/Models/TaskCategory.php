<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'standard_fuel_per_hectare',
        'standard_hectare_per_hour',
        'status',
        'created_by',
        'updated_by',
        'task_category_group_id',
    ];

    protected $casts = [
        'standard_fuel_per_hectare' => 'decimal:2',
        'standard_hectare_per_hour' => 'decimal:2',
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
    public function group()
{
    return $this->belongsTo(
        TaskCategoryGroup::class,
        'task_category_group_id'
    );
}
}