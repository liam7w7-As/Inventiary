<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'photo',
        'role_id',
        'branch_id',
        'bypass_schedule',
        'is_active',
        'can_view_profits',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'bypass_schedule' => 'boolean',
            'is_active' => 'boolean',
            'can_view_profits' => 'boolean',
        ];
    }

    public function role() { return $this->belongsTo(Role::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    
    public function isRole($roleName) {
        return $this->role && $this->role->name === $roleName;
    }
}