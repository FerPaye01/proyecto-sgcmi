<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    protected $table = 'analytics.actor';

    protected $fillable = ['ref_table', 'ref_id', 'tipo', 'name'];

    public function slaMeasures()
    {
        return $this->hasMany(SlaMeasure::class);
    }
}
