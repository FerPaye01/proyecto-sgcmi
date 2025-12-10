<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;
    
    protected $table = 'admin.permissions';

    protected $fillable = ['code', 'name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'admin.role_permissions', 'permission_id', 'role_id');
    }
}
