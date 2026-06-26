<?php

namespace App\Http\Requests\City;

use Illuminate\Foundation\Http\FormRequest;

class ShowCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cursor' => 'nullable|string',
        ];
    }
}
