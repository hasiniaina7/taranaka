<?php

declare(strict_types=1);

use App\Livewire\Traits\AuthorizesPersonActions;
use App\Models\Lineage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use AuthorizesPersonActions;
    use Interactions;

    // -----------------------------------------------------------------------
    #[Locked]
    public ?int $lineageId = null;

    public ?string $name = null;

    public ?string $description = null;

    public ?string $origin = null;

    public bool $duplicateNameWarning = false;

    // -----------------------------------------------------------------------
    public function mount(?Lineage $lineage = null): void
    {
        if ($lineage?->exists) {
            $this->lineageId   = $lineage->id;
            $this->name        = $lineage->name;
            $this->description = $lineage->description;
            $this->origin      = $lineage->origin;
        }
    }

    public function updatedName(): void
    {
        $this->duplicateNameWarning = $this->name !== null
            && $this->name !== ''
            && Lineage::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($this->name)])
                ->when($this->lineageId, fn ($query) => $query->whereKeyNot($this->lineageId))
                ->exists();
    }

    public function save(): void
    {
        $this->authorizePermission($this->lineageId ? 'lineage:update' : 'lineage:create');

        $validated = $this->validate();

        if ($this->lineageId) {
            Lineage::query()->findOrFail($this->lineageId)->update($validated);
            $lineage = Lineage::query()->findOrFail($this->lineageId);
        } else {
            $lineage = Lineage::query()->create($validated);
        }

        $this->toast()->success(__('app.save'), __('app.saved'))->send();

        $this->redirect(route('lineages.show', $lineage), navigate: true);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:65535'],
            'origin'      => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name'        => __('lineage.name'),
            'description' => __('lineage.description'),
            'origin'      => __('lineage.origin'),
        ];
    }
};
