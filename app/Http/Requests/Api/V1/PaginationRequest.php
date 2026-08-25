<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Enforce the common bounded pagination contract for public read collections.
 *
 * Keeping the cap here prevents list controllers from drifting or accepting unbounded values;
 * callers may rely on a default of 100 and a hard maximum of 500 records per page.
 */
class PaginationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'page'     => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'page.integer'     => 'The page parameter must be an integer.',
            'page.min'         => 'The page parameter must be at least 1.',
            'per_page.integer' => 'The per_page parameter must be an integer.',
            'per_page.min'     => 'The per_page parameter must be at least 1.',
            'per_page.max'     => 'The per_page parameter may not exceed 500.',
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 100);
    }
}
