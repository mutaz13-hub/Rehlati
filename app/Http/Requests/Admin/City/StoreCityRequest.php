<?php

namespace App\Http\Requests\Admin\City;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\City;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCityRequest extends ApiFormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('create', City::class);
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('cities', 'name_en')],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('cities', 'name_ar')],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
            'pics' => ['nullable', 'array'],
            'pics.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
}
