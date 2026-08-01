<?php

namespace App\Http\Requests\Admin\Region;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Region;

class UpdateRegionPicturesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('update', [Region::class, $this->route('region')]);
    }

    public function rules(): array
    {
        return [
            'deleted' => ['nullable', 'array'],
            'deleted.*' => ['integer', 'exists:media,id'],
            'added' => ['nullable', 'array'],
            'added.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
}
