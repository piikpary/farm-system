<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function hasPermission($permissionKey)
    {
        return $this->permissions()
            ->where('permission_key', $permissionKey)
            ->where('status', 'active')
            ->exists();
    }
}