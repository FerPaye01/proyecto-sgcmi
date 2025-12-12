<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;
    
    protected $table = 'terrestre.appointment';

    protected $fillable = [
        'truck_id',
        'company_id',
        'vessel_call_id',
        'hora_programada',
        'hora_llegada',
        'estado',
        'motivo',
    ];

    protected $casts = [
        'hora_programada' => 'datetime',
        'hora_llegada' => 'datetime',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vesselCall()
    {
        return $this->belongsTo(VesselCall::class);
    }

    public function gateEvents()
    {
        return $this->hasMany(GateEvent::class, 'cita_id');
    }

    public function antepuertoQueues()
    {
        return $this->hasMany(AntepuertoQueue::class);
    }
}
