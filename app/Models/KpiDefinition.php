<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiDefinition extends Model
{
    protected $table = 'analytics.kpi_definition';

    protected $fillable = ['code', 'name', 'description'];

    public function values()
    {
        return $this->hasMany(KpiValue::class, 'kpi_id');
    }
}
