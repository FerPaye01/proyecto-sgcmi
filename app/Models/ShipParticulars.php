<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipParticulars extends Model
{
    use HasFactory;

    protected $table = 'portuario.ship_particulars';

    protected $fillable = [
        'vessel_call_id',
        'loa',
        'beam',
        'draft',
        'grt',
        'nrt',
        'dwt',
        'ballast_report',
        'dangerous_cargo',
    ];

    protected $casts = [
        'loa' => 'decimal:2',
        'beam' => 'decimal:2',
        'draft' => 'decimal:2',
        'grt' => 'decimal:2',
        'nrt' => 'decimal:2',
        'dwt' => 'decimal:2',
        'ballast_report' => 'array',
        'dangerous_cargo' => 'array',
    ];

    public function vesselCall(): BelongsTo
    {
        return $this->belongsTo(VesselCall::class);
    }
}
