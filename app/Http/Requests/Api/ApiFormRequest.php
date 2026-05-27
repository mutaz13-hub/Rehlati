<?php

namespace App\Http\Requests\Api;

use App\Traits\JsonResponseTrait;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class ApiFormRequest extends FormRequest
{
    use JsonResponseTrait;

     protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

     protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator
        );
    }
}
