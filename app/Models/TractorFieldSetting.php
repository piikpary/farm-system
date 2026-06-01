<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TractorFieldSetting extends Model
{
    protected $fillable = [
        'field_key',
        'field_label',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}