<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatingIndexRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'sort' => 'nullable|in:top,latest',
        ];
    }

    protected function prepareForValidation()
    {
        if (! $this->has('sort')) {
            $this->merge(['sort' => 'top']);
        }
    }
}
