<?php

namespace App\Http\Requests\Region;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regionId = $this->route('region')->id;
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('regions', 'name_en')->where('city_id', $this->route('city')->id)->ignore($regionId)],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('regions', 'name_ar')->where('city_id', $this->route('city')->id)->ignore($regionId)],
            'city_id' => ['sometimes', 'required', 'integer', 'exists:cities,id'],
            'description_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'longitude' => ['nullable', 'numeric'],
            'latitude' => ['nullable', 'numeric'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
