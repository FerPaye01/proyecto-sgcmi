<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tramite extends Model
{
    use HasFactory;

    protected $table = 'aduanas.tramite';

    protected $fillable = [
        'tramite_ext_id',
        'vessel_call_id',
        'regimen',
        'subpartida',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'entidad_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function vesselCall()
    {
        return $this->belongsTo(VesselCall::class);
    }

    public function entidad()
    {
        return $this->belongsTo(Entidad::class);
    }

    public function events()
    {
        return $this->hasMany(TramiteEvent::class);
    }
}
