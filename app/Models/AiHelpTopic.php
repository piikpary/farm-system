<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiHelpTopic extends Model
{
    protected $fillable = [
        'module',
        'title',
        'keywords',
        'content',
        'status',
    ];
}