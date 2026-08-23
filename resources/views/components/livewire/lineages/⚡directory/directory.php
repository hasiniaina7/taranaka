<?php

declare(strict_types=1);

use App\Models\Lineage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    // -----------------------------------------------------------------------
    #[Url]
    public string $search = '';

    public int $perPage = 12;

    // -----------------------------------------------------------------------
    /**
     * @return LengthAwarePaginator<int, Lineage>
     */
    #[Computed]
    public function lineages(): LengthAwarePaginator
    {
        return Lineage::query()
            ->when(
                $this->search !== '',
                fn ($query) => $query->where(DB::raw('LOWER(name)'), 'like', '%' . mb_strtolower($this->search) . '%'),
            )
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
};
