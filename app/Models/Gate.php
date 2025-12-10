<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gate extends Model
{
    use HasFactory;
    
    protected $table = 'terrestre.gate';

    protected $fillable = [
        'code',
        'name',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function gateEvents()
    {
        return $this->hasMany(GateEvent::class);
    }
}
