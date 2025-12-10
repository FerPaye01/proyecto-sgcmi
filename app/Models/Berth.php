<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Berth extends Model
{
    use HasFactory;
    protected $table = 'portuario.berth';

    protected $fillable = ['code', 'name', 'capacity_teorica', 'active'];

    protected $casts = [
        'active' => 'boolean',
        'capacity_teorica' => 'integer',
    ];

    public function vesselCalls()
    {
        return $this->hasMany(VesselCall::class);
    }
}
