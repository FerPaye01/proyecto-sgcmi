<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationsMeeting extends Model
{
    use HasFactory;

    protected $table = 'portuario.operations_meeting';

    protected $fillable = [
        'meeting_date',
        'meeting_time',
        'attendees',
        'agreements',
        'next_24h_schedule',
        'created_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'attendees' => 'array',
        'next_24h_schedule' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
