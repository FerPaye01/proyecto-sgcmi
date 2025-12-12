<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YardLocation extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'portuario.yard_location';

    protected $fillable = [
        'zone_code',
        'block_code',
        'row_code',
        'tier',
        'location_type',
        'capacity_teu',
        'occupied',
        'active',
    ];

    protected $casts = [
        'tier' => 'integer',
        'capacity_teu' => 'integer',
        'occupied' => 'boolean',
        'active' => 'boolean',
    ];

    public function cargoItems(): HasMany
    {
        return $this->hasMany(CargoItem::class);
    }

    /**
     * Scope to get available (not occupied) locations
     */
    public function scopeAvailable($query)
    {
        return $query->where('occupied', false)->where('active', true);
    }

    /**
     * Scope to filter by location type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('location_type', $type);
    }

    /**
     * Get full location code
     */
    public function getFullLocationCodeAttribute(): string
    {
        $parts = array_filter([
            $this->zone_code,
            $this->block_code,
            $this->row_code,
            $this->tier ? "T{$this->tier}" : null,
        ]);
        
        return implode('-', $parts);
    }
}
