<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Search\FindPublicLineages;
use App\Actions\Search\FindPublicPeople;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Presents independently paginated public person and lineage search results.
 *
 * Querying lives here because every Livewire page change is a new request. Callers rely on named
 * paginators, explicit empty states, and the privacy-safe projections returned by the search Actions.
 */
class SearchResults extends Component
{
    use WithPagination;

    public const int MINIMUM_QUERY_LENGTH = 2;

    public const int RESULTS_PER_PAGE = 10;

    #[Url(as: 'q', except: '')]
    public string $query = '';

    public function mount(string $query = ''): void
    {
        $this->query = $this->normalize($query);
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    #[Computed]
    public function people(): LengthAwarePaginator
    {
        if (! $this->isSearchable()) {
            return $this->emptyPaginator('peoplePage');
        }

        return app(FindPublicPeople::class)->paginated(
            $this->query,
            self::RESULTS_PER_PAGE,
            'peoplePage',
        );
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    #[Computed]
    public function lineages(): LengthAwarePaginator
    {
        if (! $this->isSearchable()) {
            return $this->emptyPaginator('lineagesPage');
        }

        return app(FindPublicLineages::class)->paginated(
            $this->query,
            self::RESULTS_PER_PAGE,
            'lineagesPage',
        );
    }

    public function updatedQuery(): void
    {
        $this->query = $this->normalize($this->query);
        $this->resetPage(pageName: 'peoplePage');
        $this->resetPage(pageName: 'lineagesPage');

        unset($this->people, $this->lineages);
    }

    public function render(): View
    {
        return view('livewire.search-results');
    }

    protected function isSearchable(): bool
    {
        return mb_strlen($this->query) >= self::MINIMUM_QUERY_LENGTH;
    }

    protected function normalize(string $query): string
    {
        return mb_substr(mb_trim(strip_tags($query)), 0, 100);
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    protected function emptyPaginator(string $pageName): LengthAwarePaginator
    {
        return new Paginator(
            items: [],
            total: 0,
            perPage: self::RESULTS_PER_PAGE,
            currentPage: 1,
            options: [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );
    }
}
