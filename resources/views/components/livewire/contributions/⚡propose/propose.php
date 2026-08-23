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

    public const array PROPOSABLE_FIELDS = [
        'firstname', 'surname', 'birthname', 'nickname',
        'dob', 'yob', 'pob', 'dod', 'yod', 'pod',
        'summary',
        'street', 'number', 'postal_code', 'city', 'province', 'state', 'country', 'phone',
    ];

    // -----------------------------------------------------------------------
    #[Locked]
    public Person $person;

    #[Validate]
    public string $field = 'summary';

    #[Validate]
    public string $new_value = '';

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
            'author_id'     => auth()->id(),
            'target_type'   => Contribution::TARGET_PERSON,
            'target_id'     => $this->person->id,
            'field'         => $this->field,
            'old_value'     => (string) $this->person->getAttribute($this->field),
            'new_value'     => $this->new_value,
            'justification' => $this->justification,
        ]);

        $this->toast()->success(__('contributions.proposed'), __('contributions.proposed_hint'))->send();

        $this->reset(['new_value', 'justification']);
    }

    /**
     * @return array<int, string>
     */
    public function fields(): array
    {
        return self::PROPOSABLE_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'field'     => ['required', 'string', 'in:' . implode(',', self::PROPOSABLE_FIELDS)],
            'new_value' => ['required', 'string', 'max:65535'],
        ];
    }
};
