<?php

declare(strict_types=1);

namespace App\Livewire\Developer;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Present the version-one API contract inside the existing developer-only application shell.
 *
 * Documentation is kept in a component so routes, examples, and the privacy-safe response shape
 * ship with the implementation. Callers receive six grouped endpoint descriptions.
 */
class ApiDocsPage extends Component
{
    /**
     * @return list<array{title:string, endpoints:list<array{method:string, path:string, description:string, parameters:list<string>, example_request:string, example_response:string}>}>
     */
    public function endpointGroups(): array
    {
        $privatePerson = $this->json([
            'data' => [
                'id'         => 42,
                'name'       => 'Jean Rakoto',
                'lifespan'   => '1990–living',
                'photo_url'  => null,
                'lineages'   => [],
                'is_private' => true,
                'summary'    => null,
                'parents'    => [],
                'partners'   => [],
                'children'   => [],
            ],
        ]);
        $paginatedPerson = $this->json([
            'data' => [[
                'id'         => 43,
                'name'       => 'Alice Rakoto',
                'lifespan'   => '1920–2001',
                'lineages'   => [['id' => 7, 'name' => 'Rakoto', 'slug' => 'rakoto']],
                'is_private' => false,
                'degree'     => 1,
            ]],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 100, 'total' => 1],
        ]);

        return [
            $this->group('Person', [[
                'method'           => 'GET',
                'path'             => '/api/v1/persons/{person}',
                'description'      => 'Privacy-filtered public profile and minimal family references.',
                'parameters'       => ['person — numeric person identifier'],
                'example_request'  => route('api.v1.persons.show', 42, absolute: false),
                'example_response' => $privatePerson,
            ]]),
            $this->group('Descendants', [[
                'method'           => 'GET',
                'path'             => '/api/v1/persons/{person}/descendants',
                'description'      => 'Cross-team descendant set, excluding the root and capped at ten generations.',
                'parameters'       => ['max_depth — 1 to 10, default 3', 'page — positive integer', 'per_page — 1 to 500, default 100'],
                'example_request'  => route('api.v1.persons.descendants', ['person' => 42, 'max_depth' => 2], absolute: false),
                'example_response' => $paginatedPerson,
            ]]),
            $this->group('Ancestors', [[
                'method'           => 'GET',
                'path'             => '/api/v1/persons/{person}/ancestors',
                'description'      => 'Cross-team ancestor set, excluding the root and capped at 128 generations.',
                'parameters'       => ['max_depth — 1 to 128, default 3', 'page — positive integer', 'per_page — 1 to 500, default 100'],
                'example_request'  => route('api.v1.persons.ancestors', ['person' => 42, 'max_depth' => 2], absolute: false),
                'example_response' => $paginatedPerson,
            ]]),
            $this->group('Lineage', [
                [
                    'method'           => 'GET',
                    'path'             => '/api/v1/lineages/{lineage}',
                    'description'      => 'Public lineage summary.',
                    'parameters'       => ['lineage — numeric lineage identifier'],
                    'example_request'  => route('api.v1.lineages.show', 7, absolute: false),
                    'example_response' => $this->json(['data' => ['id' => 7, 'name' => 'Rakoto', 'slug' => 'rakoto', 'description' => null, 'member_count' => 42]]),
                ],
                [
                    'method'           => 'GET',
                    'path'             => '/api/v1/lineages/{lineage}/members',
                    'description'      => 'Paginated members; hidden living people are excluded as on the public lineage page.',
                    'parameters'       => ['page — positive integer', 'per_page — 1 to 500, default 100'],
                    'example_request'  => route('api.v1.lineages.members', ['lineage' => 7, 'page' => 1], absolute: false),
                    'example_response' => $paginatedPerson,
                ],
            ]),
            $this->group('Search', [[
                'method'           => 'GET',
                'path'             => '/api/v1/search',
                'description'      => 'People and lineages using the same projections as public web search.',
                'parameters'       => ['q — 2 to 100 characters', 'page — positive integer', 'per_page — 1 to 500, default 10'],
                'example_request'  => route('api.v1.search', ['q' => 'Rakoto'], absolute: false),
                'example_response' => $this->json([
                    'people'   => ['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 10, 'total' => 0]],
                    'lineages' => ['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 10, 'total' => 0]],
                ]),
            ]]),
        ];
    }

    public function render(): View
    {
        return view('livewire.developer.api-docs-page');
    }

    /**
     * @param  list<array{method:string, path:string, description:string, parameters:list<string>, example_request:string, example_response:string}>  $endpoints
     * @return array{title:string, endpoints:list<array{method:string, path:string, description:string, parameters:list<string>, example_request:string, example_response:string}>}
     */
    protected function group(string $title, array $endpoints): array
    {
        return ['title' => $title, 'endpoints' => $endpoints];
    }

    /** @param array<string, mixed> $payload */
    protected function json(array $payload): string
    {
        return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
