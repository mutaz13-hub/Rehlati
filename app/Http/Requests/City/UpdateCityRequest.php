<?php

namespace App\Http\Requests\City;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $cityId = $this->route('city')->id;
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('cities', 'name_en')->ignore($cityId)],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('cities', 'name_ar')->ignore($cityId)],
            'description_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
        ];
    }
}
