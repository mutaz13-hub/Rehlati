<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatingStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'rateable_type' => 'required|string',
            'rateable_id' => 'required|integer',
            'rate' => 'required|integer|min:1|max:5',
            'type' => 'required|in:text,audio',
            'body' => 'required_if:type,text|nullable|string',
            'audio' => 'required_if:type,audio|nullable|file|mimes:mp3,wav,aac,m4a,ogg|max:10240',
            'photo' => 'nullable|image|max:5120',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $exists = \App\Models\Rating::where('user_id', auth()->id())
                ->where('rateable_type', $this->rateable_type)
                ->where('rateable_id', $this->rateable_id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('user_id', __('You have already rated this item.'));
            }
        });
    }
}
