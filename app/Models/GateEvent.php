<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateEvent extends Model
{
    use HasFactory;
    
    protected $table = 'terrestre.gate_event';

    public $timestamps = false;

    protected $fillable = [
        'gate_id',
        'truck_id',
        'action',
        'event_ts',
        'cita_id',
        'extra',
    ];

    protected $casts = [
        'event_ts' => 'datetime',
        'extra' => 'array',
    ];

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'cita_id');
    }
}
