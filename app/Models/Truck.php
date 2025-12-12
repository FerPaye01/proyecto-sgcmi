<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    use HasFactory;
    
    protected $table = 'terrestre.truck';

    protected $fillable = ['placa', 'company_id', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function gateEvents()
    {
        return $this->hasMany(GateEvent::class);
    }

    public function digitalPasses()
    {
        return $this->hasMany(DigitalPass::class);
    }

    public function antepuertoQueues()
    {
        return $this->hasMany(AntepuertoQueue::class);
    }
}
