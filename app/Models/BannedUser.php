<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannedUser extends Model
{
    protected $fillable = ['user_id', 'banned_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
