<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FarmWorkPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_date',
        'task_category_id',
        'plan_start',
        'plan_end',
        'zone_block_ids',
        'plan_area',
        'title',
        'request_l_per_hectare',
        'request_liters',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'plan_start' => 'date',
        'plan_end' => 'date',
        'zone_block_ids' => 'array',
        'plan_area' => 'decimal:2',
        'request_l_per_hectare' => 'decimal:2',
        'request_liters' => 'decimal:2',
    ];

    public function taskCategory()
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }
    public function workLogs()
    {
        return $this->hasMany(FarmWorkLog::class, 'farm_work_plan_id');
    }
    public function activities()
{
    return $this->hasMany(
        \App\Models\FarmWorkPlanActivity::class,
        'farm_work_plan_id'
    );
}
}