<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessPermit extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'terrestre.access_permit';

    protected $fillable = [
        'digital_pass_id',
        'permit_type',
        'cargo_item_id',
        'authorized_by',
        'authorized_at',
        'used_at',
        'status',
    ];

    protected $casts = [
        'authorized_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Check if the permit is currently valid
     */
    public function isValid(): bool
    {
        return $this->status === 'PENDIENTE' 
            && $this->authorized_at !== null
            && $this->used_at === null;
    }

    /**
     * Mark the permit as used
     */
    public function markAsUsed(): void
    {
        $this->update([
            'status' => 'USADO',
            'used_at' => now(),
        ]);
    }

    /**
     * Mark the permit as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'VENCIDO']);
    }

    /**
     * Relationships
     */
    public function digitalPass(): BelongsTo
    {
        return $this->belongsTo(DigitalPass::class);
    }

    public function cargoItem(): BelongsTo
    {
        return $this->belongsTo(CargoItem::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDIENTE');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'USADO');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'VENCIDO');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('permit_type', $type);
    }
}
