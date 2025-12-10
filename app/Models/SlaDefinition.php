<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaDefinition extends Model
{
    protected $table = 'analytics.sla_definition';

    protected $fillable = ['code', 'name', 'umbral', 'comparador'];

    protected $casts = [
        'umbral' => 'decimal:4',
    ];

    public function measures()
    {
        return $this->hasMany(SlaMeasure::class, 'sla_id');
    }
}
