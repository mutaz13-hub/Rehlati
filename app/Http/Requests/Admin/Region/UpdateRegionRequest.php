<?php

namespace App\Http\Requests\Admin\Region;

use App\Http\Requests\Api\ApiFormRequest;

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
            'name_en' => ['sometimes', 'required', 'string', 'max:255', 'unique:regions,name_en,' . $regionId],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255', 'unique:regions,name_ar,' . $regionId],
            'city_id' => ['sometimes', 'required', 'integer', 'exists:cities,id'],
            'description_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
        ];
    }
}
