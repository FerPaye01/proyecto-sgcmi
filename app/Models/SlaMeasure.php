<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaMeasure extends Model
{
    protected $table = 'analytics.sla_measure';

    protected $fillable = ['sla_id', 'actor_id', 'periodo', 'valor', 'cumplio', 'extra'];

    protected $casts = [
        'periodo' => 'date',
        'valor' => 'decimal:4',
        'cumplio' => 'boolean',
        'extra' => 'array',
    ];

    public function definition()
    {
        return $this->belongsTo(SlaDefinition::class, 'sla_id');
    }

    public function actor()
    {
        return $this->belongsTo(Actor::class);
    }
}
