<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CargoItem extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'portuario.cargo_item';

    protected $fillable = [
        'manifest_id',
        'item_number',
        'description',
        'cargo_type',
        'container_number',
        'seal_number',
        'weight_kg',
        'volume_m3',
        'bl_number',
        'consignee',
        'yard_location_id',
        'status',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'volume_m3' => 'decimal:2',
    ];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(CargoManifest::class, 'manifest_id');
    }

    public function yardLocation(): BelongsTo
    {
        return $this->belongsTo(YardLocation::class);
    }

    public function tarjaNotes(): HasMany
    {
        return $this->hasMany(TarjaNote::class);
    }

    public function weighTickets(): HasMany
    {
        return $this->hasMany(WeighTicket::class);
    }

    public function accessPermits(): HasMany
    {
        return $this->hasMany(AccessPermit::class);
    }
}
