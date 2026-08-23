<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Search\FindPublicLineages;
use App\Actions\Search\FindPublicPeople;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Keeps public discovery one action away and previews privacy-safe matches while typing.
 *
 * The component owns only transient input state. Callers rely on it to defer broad queries until
 * two characters are present and to redirect submissions to the canonical public search route.
 */
class SearchBar extends Component
{
    public const int AUTOCOMPLETE_LIMIT = 5;

    public const int MINIMUM_QUERY_LENGTH = 2;

    public string $query = '';

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function people(): Collection
    {
        if (! $this->isSearchable()) {
            return collect();
        }

        return app(FindPublicPeople::class)->limited($this->normalizedQuery(), self::AUTOCOMPLETE_LIMIT);
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function lineages(): Collection
    {
        if (! $this->isSearchable()) {
            return collect();
        }

        return app(FindPublicLineages::class)->limited($this->normalizedQuery(), self::AUTOCOMPLETE_LIMIT);
    }

    public function updatedQuery(): void
    {
        $this->query = mb_substr(strip_tags($this->query), 0, 100);

        unset($this->people, $this->lineages);
    }

    public function submit(): void
    {
        $this->redirectRoute('public.search', [
            'q' => $this->normalizedQuery(),
        ]);
    }

    public function render(): View
    {
        return view('livewire.search-bar');
    }

    protected function isSearchable(): bool
    {
        return mb_strlen($this->normalizedQuery()) >= self::MINIMUM_QUERY_LENGTH;
    }

    protected function normalizedQuery(): string
    {
        return mb_trim(strip_tags($this->query));
    }
}
