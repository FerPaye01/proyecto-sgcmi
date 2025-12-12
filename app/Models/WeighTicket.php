<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeighTicket extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'portuario.weigh_ticket';

    protected $fillable = [
        'cargo_item_id',
        'ticket_number',
        'weigh_date',
        'gross_weight_kg',
        'tare_weight_kg',
        'net_weight_kg',
        'scale_id',
        'operator_name',
    ];

    protected $casts = [
        'weigh_date' => 'datetime',
        'gross_weight_kg' => 'decimal:2',
        'tare_weight_kg' => 'decimal:2',
        'net_weight_kg' => 'decimal:2',
    ];

    /**
     * Automatically calculate net weight when setting gross or tare weight
     */
    protected static function booted(): void
    {
        static::saving(function (WeighTicket $ticket) {
            if ($ticket->gross_weight_kg !== null && $ticket->tare_weight_kg !== null) {
                $ticket->net_weight_kg = $ticket->gross_weight_kg - $ticket->tare_weight_kg;
            }
        });
    }

    public function cargoItem(): BelongsTo
    {
        return $this->belongsTo(CargoItem::class);
    }
}
