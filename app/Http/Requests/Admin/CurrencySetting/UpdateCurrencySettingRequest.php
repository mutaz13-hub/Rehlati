<?php

namespace App\Http\Requests\Admin\CurrencySetting;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrencySettingRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'active_currency' => ['required', 'string', Rule::in(['USD', 'SYP'])],
        ];
    }
}
