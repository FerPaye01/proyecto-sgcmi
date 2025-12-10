<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admin.users';

    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'is_active',
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'admin.user_roles', 'user_id', 'role_id');
    }

    /**
     * Get the company that the user belongs to (direct relationship).
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the companies associated with the user.
     * Note: This relationship requires the admin.user_companies pivot table
     * which will be added in a future migration (Sprint 3).
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'admin.user_companies', 'user_id', 'company_id');
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->roles()
            ->where('code', $roleCode)
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('code', $permission);
            })
            ->exists();
    }
}
