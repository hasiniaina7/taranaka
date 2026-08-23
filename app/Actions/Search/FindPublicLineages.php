<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Models\Lineage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * Finds lineage summaries for both public search surfaces.
 *
 * Keeping the query here makes the escaped Lineage search contract and result shape identical
 * for autocomplete and paginated results. Callers rely on public, read-only summary fields.
 */
class FindPublicLineages
{
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginated(string $query, int $perPage, string $pageName): LengthAwarePaginator
    {
        $lineages = $this->query($query)->paginate($perPage, ['*'], $pageName);

        return new Paginator(
            items: $lineages->getCollection()->map(fn (Lineage $lineage): array => $this->project($lineage)),
            total: $lineages->total(),
            perPage: $lineages->perPage(),
            currentPage: $lineages->currentPage(),
            options: $lineages->getOptions(),
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function limited(string $query, int $limit): Collection
    {
        return $this->query($query)
            ->limit($limit)
            ->get()
            ->map(fn (Lineage $lineage): array => $this->project($lineage));
    }

    /** @return Builder<Lineage> */
    protected function query(string $query): Builder
    {
        return Lineage::query()
            ->search($query)
            ->withCount('people')
            ->orderBy('name')
            ->orderBy('id');
    }

    /** @return array<string, mixed> */
    protected function project(Lineage $lineage): array
    {
        return [
            'id'           => $lineage->id,
            'name'         => $lineage->name,
            'url'          => route('lineages.show', $lineage),
            'member_count' => $lineage->people_count,
        ];
    }
}
