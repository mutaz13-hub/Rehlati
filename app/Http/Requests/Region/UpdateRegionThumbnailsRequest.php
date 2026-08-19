<?php

namespace App\Http\Requests\Region;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateRegionThumbnailsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
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
