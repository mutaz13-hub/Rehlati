<?php

namespace App\Http\Requests\Package;

use App\Enums\Status;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('packages', 'name_en')],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('packages', 'name_ar')],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            // 'duration_days' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', Rule::in(['SYP', 'USD', 'EUR'])],
            'status' => ['required', 'string', Rule::in(Status::values())],
            'regions' => ['nullable', 'array'],
            'regions.*' => ['integer', 'exists:regions,id'],
            'cities' => ['nullable', 'array'],
            'cities.*' => ['integer', 'exists:cities,id'],
            'hotels' => ['nullable', 'array'],
            'hotels.*' => ['integer', 'exists:hotels,id'],
            'car_agencies' => ['nullable', 'array'],
            'car_agencies.*' => ['integer', 'exists:car_agencies,id'],
            'tourist_guides' => ['nullable', 'array'],
            'tourist_guides.*' => ['integer', 'exists:tourist_guides,id'],
        ];
    }
}
