<?php

namespace App\Http\Requests\Admin\Season;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class AdminStoreSeasonRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('seasons', 'name_en')->where(function ($query) {
                return $query->where('seasonable_type', $this->input('seasonable_type'))
                    ->where('seasonable_id', $this->input('seasonable_id'));
            })],
            'name_ar' => ['nullable', 'string', 'max:255', Rule::unique('seasons', 'name_ar')->where(function ($query) {
                return $query->where('seasonable_type', $this->input('seasonable_type'))
                    ->where('seasonable_id', $this->input('seasonable_id'));
            })],
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'seasonable_type' => ['nullable', 'string', 'in:hotel,package,car_agency'],
            'seasonable_id' => ['nullable', 'integer'],
        ];
    }
}
