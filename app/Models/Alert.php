<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $table = 'analytics.alerts';

    protected $fillable = [
        'alert_id',
        'tipo',
        'nivel',
        'entity_id',
        'entity_type',
        'entity_name',
        'valor',
        'umbral',
        'unidad',
        'descripción',
        'acciones_recomendadas',
        'citas_afectadas',
        'detected_at',
        'resolved_at',
        'estado',
    ];

    protected $casts = [
        'valor' => 'decimal:4',
        'umbral' => 'decimal:4',
        'acciones_recomendadas' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
