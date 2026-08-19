<?php

namespace App\Http\Requests\Admin\Package;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Package;

class UpdatePackagePicturesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
        // return auth()->user()->can('update', [Package::class, $this->route('package')]);
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
