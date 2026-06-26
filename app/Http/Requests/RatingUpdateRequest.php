<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RatingUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'rate' => ['nullable', 'numeric', 'integer', 'min:1', 'max:5'],
            'type' => ['nullable', 'string', 'in:text,audio'],
            'body' => [Rule::requiredIf($this->type === 'text'), Rule::prohibitedIf($this->type === 'audio'), 'nullable', 'string', 'max:255'],
            'audio' => [Rule::prohibitedIf($this->type === 'text'), Rule::requiredIf($this->type === 'audio'), 'nullable', 'file', 'mimes:mp3,wav,aac,m4a,ogg', 'max:10240'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'delete_photo' => ['nullable', 'boolean'],
        ];
    }
}
