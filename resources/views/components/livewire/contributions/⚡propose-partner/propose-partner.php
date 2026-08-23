<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    // -----------------------------------------------------------------------
    #[Locked]
    public Person $person;

    public string $search = '';

    #[Validate]
    public ?int $partner_id = null;

    public ?string $justification = null;

    // -----------------------------------------------------------------------
    public function mount(Person $person): void
    {
        Gate::authorize('propose', Contribution::class);

        $this->person = $person;
    }

    /**
     * @return Collection<int, Person>
     */
    public function results(): Collection
    {
        if (mb_trim($this->search) === '') {
            return new Collection;
        }

        return Person::search($this->search)
            ->where('id', '!=', $this->person->id)
            ->limit(10)
            ->get();
    }

    public function submit(): void
    {
        Gate::authorize('propose', Contribution::class);

        $this->validate();

        Contribution::create([
            'author_id'   => auth()->id(),
            'target_type' => Contribution::TARGET_RELATIONSHIP_NEW,
            'target_id'   => null,
            'field'       => null,
            'old_value'   => null,
            'new_value'   => json_encode([
                'person1_id' => $this->person->id,
                'person2_id' => $this->partner_id,
            ]),
            'justification' => $this->justification,
        ]);

        $this->toast()->success(__('contributions.proposed'), __('contributions.proposed_hint'))->send();

        $this->reset(['search', 'partner_id', 'justification']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'partner_id' => ['required', 'integer'],
        ];
    }
};
