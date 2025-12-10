<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VesselCall extends Model
{
    use HasFactory;
    protected $table = 'portuario.vessel_call';

    protected $fillable = [
        'vessel_id',
        'viaje_id',
        'berth_id',
        'eta',
        'etb',
        'ata',
        'atb',
        'atd',
        'estado_llamada',
        'motivo_demora',
    ];

    protected $casts = [
        'eta' => 'datetime',
        'etb' => 'datetime',
        'ata' => 'datetime',
        'atb' => 'datetime',
        'atd' => 'datetime',
    ];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function berth()
    {
        return $this->belongsTo(Berth::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function tramites()
    {
        return $this->hasMany(Tramite::class);
    }

    public function shipParticulars()
    {
        return $this->hasOne(ShipParticulars::class);
    }

    public function loadingPlans()
    {
        return $this->hasMany(LoadingPlan::class);
    }

    public function resourceAllocations()
    {
        return $this->hasMany(ResourceAllocation::class);
    }
}
