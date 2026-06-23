<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskCategoryGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_type',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function taskCategories()
    {
        return $this->hasMany(TaskCategory::class);
    }
}