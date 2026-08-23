<?php

declare(strict_types=1);

use App\Models\Contribution;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component
{
    public string $status = Contribution::STATUS_PENDING;

    /**
     * @return Collection<int, Contribution>
     */
    public function contributions(): Collection
    {
        return Contribution::query()
            ->with('author')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->get();
    }
};
