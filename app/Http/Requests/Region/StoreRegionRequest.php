<?php

namespace App\Http\Requests\Region;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreRegionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('regions', 'name_en')->where('city_id', $this->route('city')->id)],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('regions', 'name_ar')->where('city_id', $this->route('city')->id)],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'description_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'pics' => ['nullable', 'array'],
            'pics.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'longitude' => ['nullable', 'numeric'],
            'latitude' => ['nullable', 'numeric'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
