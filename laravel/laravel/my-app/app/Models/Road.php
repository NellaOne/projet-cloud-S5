<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Road extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'longitude',
        'latitude',
        'area',
    ];

    public function roadworks()
    {
        return $this->hasMany(Roadwork::class);
    }
}