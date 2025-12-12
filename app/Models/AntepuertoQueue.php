<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntepuertoQueue extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'terrestre.antepuerto_queue';

    protected $fillable = [
        'truck_id',
        'appointment_id',
        'entry_time',
        'exit_time',
        'zone',
        'status',
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    /**
     * Calculate waiting time in minutes
     */
    public function getWaitingTimeMinutes(): ?int
    {
        if (!$this->entry_time) {
            return null;
        }

        $endTime = $this->exit_time ?? now();
        return (int) $this->entry_time->diffInMinutes($endTime);
    }

    /**
     * Check if truck is currently in queue
     */
    public function isInQueue(): bool
    {
        return $this->entry_time !== null 
            && $this->exit_time === null
            && $this->status === 'EN_ESPERA';
    }

    /**
     * Authorize entry to terminal
     */
    public function authorize(): void
    {
        $this->update([
            'status' => 'AUTORIZADO',
            'exit_time' => now(),
        ]);
    }

    /**
     * Reject entry
     */
    public function reject(): void
    {
        $this->update([
            'status' => 'RECHAZADO',
            'exit_time' => now(),
        ]);
    }

    /**
     * Relationships
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Scopes
     */
    public function scopeInQueue($query)
    {
        return $query->where('status', 'EN_ESPERA')
            ->whereNotNull('entry_time')
            ->whereNull('exit_time');
    }

    public function scopeAuthorized($query)
    {
        return $query->where('status', 'AUTORIZADO');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'RECHAZADO');
    }

    public function scopeByZone($query, string $zone)
    {
        return $query->where('zone', $zone);
    }

    public function scopeAntepuerto($query)
    {
        return $query->where('zone', 'ANTEPUERTO');
    }

    public function scopeZoe($query)
    {
        return $query->where('zone', 'ZOE');
    }
}
