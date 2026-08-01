<?php

namespace App\Http\Requests\Admin\City;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\City;

class UpdateCityThumbnailsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('update', [City::class, $this->route('city')]);
    }

    public function rules(): array
    {
        return [
            'deleted' => ['nullable', 'array', 'max:3'],
            'deleted.*' => ['integer', 'exists:media,id'],
            'added' => ['nullable', 'array', 'max:3'],
            'added.*' => ['integer', 'exists:media,id'],
        ];
    }
}
