<?php

namespace App\Http\Requests\Admin\TouristGuide;

use App\Enums\Weekday;
use App\Http\Requests\Api\ApiFormRequest;
use App\Models\TouristGuide;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTouristGuideRequest extends ApiFormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('create', TouristGuide::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('tourist_guides', 'email')],
            'phone' => ['required', 'string', 'max:30', Rule::unique('tourist_guides', 'phone')],
            'availability' => ['nullable', 'array'],
            'availability.*' => ['required', 'string', 'distinct', Rule::enum(Weekday::class)],
            'price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
