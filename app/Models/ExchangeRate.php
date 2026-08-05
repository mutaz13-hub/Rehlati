<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency',
        'rate_to_syp',
    ];

    protected $casts = [
        'rate_to_syp' => 'decimal:2',
    ];
}
