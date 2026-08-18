<?php

namespace App\Http\Requests\Admin\Price;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class AdminUpdatePriceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_type' => ['required', 'string', Rule::in(['base_price', 'child_price', 'extra_bed_price', 'package_price'])],
            'nationality_category' => ['required', 'string', Rule::in(['syrian', 'expat', 'foreigner'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'season_id' => ['nullable', 'exists:seasons,id'],
        ];
    }
}
