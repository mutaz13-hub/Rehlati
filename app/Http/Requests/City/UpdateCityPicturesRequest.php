<?php

namespace App\Http\Requests\City;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateCityPicturesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deleted' => ['nullable', 'array'],
            'deleted.*' => ['numeric', 'integer', 'exists:media,id'],
            'added' => ['nullable', 'array'],
            'added.*' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ];
    }
}
