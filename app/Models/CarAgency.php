<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class CarAgency extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    public const MORPH_KEY = 'car_agency';

    public function packages(): MorphToMany
    {
        return $this->morphToMany(Package::class, 'packageable');
    }
}
