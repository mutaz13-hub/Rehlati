<?php

namespace App\Http\Requests\Admin\Region;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Region;

class UpdateRegionThumbnailsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('update', [Region::class, $this->route('region')]);
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
