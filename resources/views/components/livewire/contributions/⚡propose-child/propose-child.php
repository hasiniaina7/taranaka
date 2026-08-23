<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Person;
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

    #[Validate]
    public string $firstname = '';

    #[Validate]
    public string $surname = '';

    #[Validate]
    public string $sex = 'm';

    public ?string $justification = null;

    // -----------------------------------------------------------------------
    public function mount(Person $person): void
    {
        Gate::authorize('propose', Contribution::class);

        $this->person = $person;
    }

    public function submit(): void
    {
        Gate::authorize('propose', Contribution::class);

        $this->validate();

        Contribution::create([
            'author_id'   => auth()->id(),
            'target_type' => Contribution::TARGET_PERSON_NEW,
            'target_id'   => null,
            'field'       => $this->person->sex === 'm' ? 'father_id' : 'mother_id',
            'old_value'   => null,
            'new_value'   => json_encode([
                'parent_id' => $this->person->id,
                'firstname' => $this->firstname,
                'surname'   => $this->surname,
                'sex'       => $this->sex,
            ]),
            'justification' => $this->justification,
        ]);

        $this->toast()->success(__('contributions.proposed'), __('contributions.proposed_hint'))->send();

        $this->reset(['firstname', 'surname', 'sex', 'justification']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'surname'   => ['required', 'string', 'max:255'],
            'sex'       => ['required', 'string', 'in:m,f'],
        ];
    }
};
