<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    
    protected $table = 'terrestre.company';

    protected $fillable = ['ruc', 'name', 'tipo', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function trucks()
    {
        return $this->hasMany(Truck::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
