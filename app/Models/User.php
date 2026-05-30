<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission($permissionKey)
    {
        if (!$this->role) {
            return false;
        }

        return $this->role
            ->permissions()
            ->where('permission_key', $permissionKey)
            ->where('status', 'active')
            ->exists();
    }

    public function createdFarmWorkLogs()
    {
        return $this->hasMany(FarmWorkLog::class, 'created_by');
    }

    public function updatedFarmWorkLogs()
    {
        return $this->hasMany(FarmWorkLog::class, 'updated_by');
    }
}