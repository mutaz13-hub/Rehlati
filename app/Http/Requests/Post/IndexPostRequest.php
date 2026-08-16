<?php

namespace App\Http\Requests\Post;

use App\Http\Requests\Api\ApiFormRequest;

class IndexPostRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'sort' => ['nullable', 'in:top,latest'],
            'page' => ['nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('sort')) {
            $this->merge(['sort' => 'top']);
        }
    }
}
