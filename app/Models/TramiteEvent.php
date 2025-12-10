<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TramiteEvent extends Model
{
    protected $table = 'aduanas.tramite_event';

    public $timestamps = false;

    protected $fillable = [
        'tramite_id',
        'event_ts',
        'estado',
        'motivo',
    ];

    protected $casts = [
        'event_ts' => 'datetime',
    ];

    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }
}
