<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entidad extends Model
{
    use HasFactory;

    protected $table = 'aduanas.entidad';

    protected $fillable = ['code', 'name'];

    public function tramites()
    {
        return $this->hasMany(Tramite::class);
    }
}
