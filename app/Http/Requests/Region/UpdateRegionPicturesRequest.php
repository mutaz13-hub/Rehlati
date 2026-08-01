<?php

namespace App\Http\Requests\Region;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateRegionPicturesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
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
