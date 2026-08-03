<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactDetails extends Model
{
    protected $table = 'contact_details';

    protected $fillable = [
        'contactable_type',
        'contactable_id',
        'email',
        'phone',
    ];

    public function contactable()
    {
        return $this->morphTo();
    }
}
