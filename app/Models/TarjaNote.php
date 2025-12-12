<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarjaNote extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'portuario.tarja_note';

    protected $fillable = [
        'cargo_item_id',
        'tarja_number',
        'tarja_date',
        'inspector_name',
        'observations',
        'condition',
        'photos',
        'created_by',
    ];

    protected $casts = [
        'tarja_date' => 'datetime',
        'photos' => 'array',
    ];

    public function cargoItem(): BelongsTo
    {
        return $this->belongsTo(CargoItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
