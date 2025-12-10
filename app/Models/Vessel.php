<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
    use HasFactory;
    protected $table = 'portuario.vessel';

    protected $fillable = ['imo', 'name', 'flag_country', 'type'];

    public function vesselCalls()
    {
        return $this->hasMany(VesselCall::class);
    }
}
