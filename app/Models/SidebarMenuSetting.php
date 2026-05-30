<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SidebarMenuSetting extends Model
{
    protected $fillable = [
        'menu_key',
        'menu_label',
        'menu_group',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];
}