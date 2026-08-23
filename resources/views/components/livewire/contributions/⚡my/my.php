<?php

declare(strict_types=1);

use App\Models\Contribution;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component
{
    /**
     * @return Collection<int, Contribution>
     */
    public function contributions(): Collection
    {
        return Contribution::query()
            ->where('author_id', auth()->id())
            ->latest()
            ->get();
    }
};
