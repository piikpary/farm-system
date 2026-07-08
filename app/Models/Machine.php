<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $fillable = [
        'name',
        'machine_no',
        'brand',
        'description',
        'status',
    ];
}