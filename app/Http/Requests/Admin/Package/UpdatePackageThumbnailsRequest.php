<?php

namespace App\Http\Requests\Admin\Package;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Package;

class UpdatePackageThumbnailsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
        // return auth()->user()->can('update', [Package::class, $this->route('package')]);
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
