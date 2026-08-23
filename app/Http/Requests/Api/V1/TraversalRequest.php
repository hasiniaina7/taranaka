<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate recursive genealogy bounds before they reach the established query engine.
 *
 * Descendants and ancestors share pagination rules but retain their distinct web depth caps.
 * Controllers may rely on normalized positive integers and the public default depth of three.
 */
class TraversalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $maximumDepth = $this->routeIs('api.v1.persons.descendants') ? 10 : 128;

        return [
            'max_depth' => ['sometimes', 'integer', 'min:1', "max:{$maximumDepth}"],
            'page'      => ['sometimes', 'integer', 'min:1'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'max_depth.integer' => 'The max_depth parameter must be an integer.',
            'max_depth.min'     => 'The max_depth parameter must be at least 1.',
            'max_depth.max'     => 'The max_depth parameter exceeds this traversal endpoint limit.',
            'page.integer'      => 'The page parameter must be an integer.',
            'page.min'          => 'The page parameter must be at least 1.',
            'per_page.integer'  => 'The per_page parameter must be an integer.',
            'per_page.min'      => 'The per_page parameter must be at least 1.',
            'per_page.max'      => 'The per_page parameter may not exceed 500.',
        ];
    }

    public function maxDepth(): int
    {
        return $this->integer('max_depth', 3);
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 100);
    }

    public function page(): int
    {
        return $this->integer('page', 1);
    }
}
