<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CargoManifest extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'portuario.cargo_manifest';

    protected $fillable = [
        'vessel_call_id',
        'manifest_number',
        'manifest_date',
        'total_items',
        'total_weight_kg',
        'document_url',
    ];

    protected $casts = [
        'manifest_date' => 'date',
        'total_items' => 'integer',
        'total_weight_kg' => 'decimal:2',
    ];

    public function vesselCall(): BelongsTo
    {
        return $this->belongsTo(VesselCall::class);
    }

    public function cargoItems(): HasMany
    {
        return $this->hasMany(CargoItem::class, 'manifest_id');
    }
}
