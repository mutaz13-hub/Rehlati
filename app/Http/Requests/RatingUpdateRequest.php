<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatingUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'rate' => 'nullable|integer|min:1|max:5',
            'body' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,aac,m4a,ogg|max:10240',
            'photo' => 'nullable|image|max:5120',
        ];
    }
}
