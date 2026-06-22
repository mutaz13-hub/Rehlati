<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    public function AmenityHotels()
    {
        return $this->hasMany(AmenityHotel::class);
    }
}
