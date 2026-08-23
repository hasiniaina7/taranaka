<?php

declare(strict_types=1);

use App\Livewire\Traits\AuthorizesPersonActions;
use App\Models\Lineage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use AuthorizesPersonActions;
    use Interactions;
    use WithPagination;

    // -----------------------------------------------------------------------
    public string $search = '';

    public int $perPage = 10;

    // -----------------------------------------------------------------------
    /**
     * @return LengthAwarePaginator<int, Lineage>
     */
    #[Computed]
    public function lineages(): LengthAwarePaginator
    {
        return Lineage::query()
            ->withCount('people')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function delete(int $lineageId): void
    {
        $this->authorizePermission('lineage:delete');

        $lineage = Lineage::query()->findOrFail($lineageId);

        if (! $lineage->isDeletable()) {
            $this->toast()->error(__('app.error'), __('lineage.delete_blocked'))->send();

            return;
        }

        $lineage->delete();

        $this->toast()->success(__('app.delete'), __('app.deleted'))->send();
    }
};
