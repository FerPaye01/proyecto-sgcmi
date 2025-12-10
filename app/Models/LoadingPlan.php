<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingPlan extends Model
{
    use HasFactory;

    protected $table = 'portuario.loading_plan';

    protected $fillable = [
        'vessel_call_id',
        'operation_type',
        'sequence_order',
        'estimated_duration_h',
        'equipment_required',
        'crew_required',
        'status',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'estimated_duration_h' => 'decimal:2',
        'crew_required' => 'integer',
        'equipment_required' => 'array',
    ];

    public function vesselCall(): BelongsTo
    {
        return $this->belongsTo(VesselCall::class);
    }
}
