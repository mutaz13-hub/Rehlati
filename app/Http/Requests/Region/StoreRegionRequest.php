<?php

namespace App\Http\Requests\Region;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreRegionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', 'unique:regions,name_en'],
            'name_ar' => ['required', 'string', 'max:255', 'unique:regions,name_ar'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'description_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'pics' => ['nullable', 'array'],
            'pics.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
}
