<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiValue extends Model
{
    protected $table = 'analytics.kpi_value';

    protected $fillable = ['kpi_id', 'periodo', 'valor', 'meta', 'fuente', 'extra'];

    protected $casts = [
        'periodo' => 'date',
        'valor' => 'decimal:4',
        'meta' => 'decimal:4',
        'extra' => 'array',
    ];

    public function definition()
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_id');
    }
}
