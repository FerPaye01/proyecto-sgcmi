<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceAllocation extends Model
{
    use HasFactory;

    protected $table = 'portuario.resource_allocation';

    protected $fillable = [
        'vessel_call_id',
        'resource_type',
        'resource_name',
        'quantity',
        'shift',
        'allocated_at',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'allocated_at' => 'datetime',
    ];

    public function vesselCall(): BelongsTo
    {
        return $this->belongsTo(VesselCall::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
