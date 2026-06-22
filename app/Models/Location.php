<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'latitude',
        'longitude',
        'locatable_id',
        'locatable_type',
    ];

    public function locatable()
    {
        return $this->morphTo();
    }
}
