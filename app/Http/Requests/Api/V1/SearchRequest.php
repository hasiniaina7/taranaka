<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Normalize and bound public API search input before the shared search Actions execute.
 *
 * This Request mirrors the web surface's two-character/100-character contract and applies the
 * API list cap. Controllers may rely on a stripped query and a positive bounded page size.
 */
class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'q'        => ['required', 'string', 'min:2', 'max:100'],
            'page'     => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'q.required'       => 'The q parameter is required.',
            'q.string'         => 'The q parameter must be a string.',
            'q.min'            => 'The q parameter must contain at least 2 characters.',
            'q.max'            => 'The q parameter may not exceed 100 characters.',
            'page.integer'     => 'The page parameter must be an integer.',
            'page.min'         => 'The page parameter must be at least 1.',
            'per_page.integer' => 'The per_page parameter must be an integer.',
            'per_page.min'     => 'The per_page parameter must be at least 1.',
            'per_page.max'     => 'The per_page parameter may not exceed 500.',
        ];
    }

    public function queryText(): string
    {
        return mb_trim(strip_tags($this->string('q')->toString()));
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 10);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('q')) {
            return;
        }

        $query = $this->input('q');

        if (is_string($query)) {
            $this->merge(['q' => mb_trim(strip_tags($query))]);
        }
    }
}
