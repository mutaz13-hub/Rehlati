<?php

namespace App\Http\Requests\Admin\City;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\City;

class UpdateCityPicturesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('update', [City::class, $this->route('city')]);
    }

    public function rules(): array
    {
        return [
            'deleted' => ['nullable', 'array'],
            'deleted.*' => ['numeric', 'integer', 'exists:media,id'],
            'added' => ['nullable', 'array'],
            'added.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
}
