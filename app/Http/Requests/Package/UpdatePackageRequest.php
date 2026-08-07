<?php

namespace App\Http\Requests\Package;

use App\Enums\Status;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        $packageId = $this->route('package')?->id;

        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('packages', 'name_en')->ignore($packageId)],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('packages', 'name_ar')->ignore($packageId)],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
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
        ];
    }
}
