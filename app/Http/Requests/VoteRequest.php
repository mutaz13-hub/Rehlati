<?php

namespace App\Http\Requests;

use App\Enums\VoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class VoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'vote' => ['required', new Enum(VoteType::class)],
        ];
    }
}
