<?php

namespace App\Http\Requests\Admin\Season;

use App\Http\Requests\Api\ApiFormRequest;

class AdminUpdateSeasonRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date', 'before_or_equal:end_date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'seasonable_type' => ['nullable', 'string'],
            'seasonable_id' => ['nullable', 'integer'],
        ];
    }
}
