<?php

namespace App\Http\Requests\Admin\ExchangeRate;

use App\Http\Requests\Api\ApiFormRequest;

class AdminUpdateExchangeRateRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'max:3'],
            'rate_to_syp' => ['required', 'numeric', 'min:0'],
        ];
    }
}
