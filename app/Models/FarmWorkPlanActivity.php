<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmWorkPlanActivity extends Model
{
    protected $fillable = [
        'farm_work_plan_id',
        'task_category_id',
        'fuel_per_hectare',
    ];

    protected $casts = [
        'fuel_per_hectare' => 'decimal:2',
    ];

    public function workPlan()
    {
        return $this->belongsTo(
            FarmWorkPlan::class,
            'farm_work_plan_id'
        );
    }

    public function taskCategory()
    {
        return $this->belongsTo(TaskCategory::class);
    }
}