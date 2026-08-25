<?php

declare(strict_types=1);

use App\Actions\People\FindDuplicatePersonCandidates;
use App\Actions\People\RecordDuplicateResolution;
use App\Livewire\Forms\People\PersonForm;
use App\Livewire\Traits\AuthorizesPersonActions;
use App\Livewire\Traits\HandlesPhotoUploads;
use App\Livewire\Traits\SavesPersonPhotos;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Person as PersonModel;
use App\Rules\DobValid;
use App\Rules\YobValid;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use AuthorizesPersonActions;
    use HandlesPhotoUploads, SavesPersonPhotos;
    use Interactions, WithFileUploads;
    use TrimStringsAndConvertEmptyStringsToNull;

    public PersonForm $form;

    public ?string $duplicateAcknowledgmentFingerprint = null;

    /**
     * @return list<array{id: int, name: string, lifespan: ?string, lineages: list<string>, private: bool, score: float, percentage: int, high_confidence: bool, url: string}>
     */
    #[Computed]
    public function duplicateCandidates(): array
    {
        $nameFields = [
            $this->form->firstname,
            $this->form->surname,
            $this->form->birthname,
            $this->form->nickname,
        ];

        return app(FindDuplicatePersonCandidates::class)->execute($nameFields, $this->enteredBirthYear());
    }

    #[Computed]
    public function requiresDuplicateAcknowledgment(): bool
    {
        $highConfidenceCandidates = $this->highConfidenceCandidates();

        if ($highConfidenceCandidates === []) {
            return false;
        }

        return ! hash_equals(
            $this->acknowledgmentToken($highConfidenceCandidates),
            $this->duplicateAcknowledgmentFingerprint ?? '',
        );
    }

    #[On('duplicate-candidates-acknowledged')]
    public function acknowledgeDuplicateCandidates(): void
    {
        $this->authorizePermission('person:create');

        $highConfidenceCandidates = $this->highConfidenceCandidates();

        abort_if($highConfidenceCandidates === [], 422);

        $this->duplicateAcknowledgmentFingerprint = $this->acknowledgmentToken($highConfidenceCandidates);

        unset($this->requiresDuplicateAcknowledgment);
    }

    #[On('duplicate-existing-selected')]
    public function reuseExistingPerson(int $candidateId): void
    {
        $this->authorizePermission('person:create');

        $candidate = collect($this->duplicateCandidates)->firstWhere('id', $candidateId);

        abort_unless(is_array($candidate), 404);

        $user   = auth()->user();
        $person = PersonModel::withoutGlobalScope('team')->findOrFail($candidateId);

        abort_unless($user && $user->currentTeam, 403);

        app(RecordDuplicateResolution::class)->linkedAsSame($user, $person, $candidate['score']);

        $this->redirect($candidate['url']);
    }

    public function updated(string $property): void
    {
        if (! in_array($property, [
            'form.firstname',
            'form.surname',
            'form.birthname',
            'form.nickname',
            'form.yob',
            'form.dob',
        ], true)) {
            return;
        }

        $this->duplicateAcknowledgmentFingerprint = null;

        unset($this->duplicateCandidates, $this->requiresDuplicateAcknowledgment);
    }

    public function savePerson(): void
    {
        $this->authorizePermission('person:create');

        $user = auth()->user();

        if (! $user || ! $user->currentTeam) {
            return;
        }

        if ($this->requiresDuplicateAcknowledgment) {
            $this->addError('duplicateAcknowledgment', __('person.duplicate_acknowledgment_required'));

            return;
        }

        $validated           = $this->validate($this->rules());
        $duplicateCandidates = collect($this->duplicateCandidates)
            ->map(fn (array $candidate): array => [
                'id'    => $candidate['id'],
                'score' => $candidate['score'],
            ])
            ->values()
            ->all();

        $newPerson = PersonModel::create([
            'firstname' => $validated['form']['firstname'],
            'surname'   => $validated['form']['surname'],
            'birthname' => $validated['form']['birthname'],
            'nickname'  => $validated['form']['nickname'],
            'sex'       => $validated['form']['sex'],
            'gender_id' => $validated['form']['gender_id'] ?? null,
            'yob'       => $validated['form']['yob'],
            'dob'       => $validated['form']['dob'],
            'pob'       => $validated['form']['pob'],
            'team_id'   => $user->currentTeam->id,
        ]);

        // Handle photo uploads if present, using SavesPersonPhotos trait
        if (! empty($this->form->uploads)) {
            $this->savePersonPhotos($newPerson, 'person');
        }

        if ($duplicateCandidates !== []) {
            app(RecordDuplicateResolution::class)->confirmedDistinct($user, $newPerson, $duplicateCandidates);
        }

        $this->toast()->success(__('app.create'), e($newPerson->name) . ' ' . __('app.created'))->send();

        $this->redirectRoute('people.show', ['person' => $newPerson]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return array_merge([
            'form.firstname' => ['nullable', 'string', 'max:255'],
            'form.surname'   => ['required', 'string', 'max:255'],
            'form.birthname' => ['nullable', 'string', 'max:255'],
            'form.nickname'  => ['nullable', 'string', 'max:255'],
            'form.sex'       => ['required', 'string', 'max:1', 'in:m,f'],
            'form.gender_id' => ['nullable', 'integer'],
            'form.yob'       => ['nullable', 'integer', 'min:1', 'max:' . date('Y'), new YobValid],
            'form.dob'       => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', new DobValid],
            'form.pob'       => ['nullable', 'string', 'max:255'],
        ], $this->getPhotoUploadRules());
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return $this->getPhotoUploadMessages();
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return array_merge([
            'form.firstname' => __('person.firstname'),
            'form.surname'   => __('person.surname'),
            'form.birthname' => __('person.birthname'),
            'form.nickname'  => __('person.nickname'),
            'form.sex'       => __('person.sex'),
            'form.gender_id' => __('person.gender'),
            'form.yob'       => __('person.yob'),
            'form.dob'       => __('person.dob'),
            'form.pob'       => __('person.pob'),
        ], $this->getPhotoUploadAttributes());
    }

    /** @return list<array{id: int, score: float}> */
    protected function highConfidenceCandidates(): array
    {
        return collect($this->duplicateCandidates)
            ->where('high_confidence', true)
            ->map(fn (array $candidate): array => [
                'id'    => $candidate['id'],
                'score' => $candidate['score'],
            ])
            ->values()
            ->all();
    }

    protected function enteredBirthYear(): ?int
    {
        if (is_string($this->form->dob) && preg_match('/^(\d{4})-/', $this->form->dob, $matches) === 1) {
            return (int) $matches[1];
        }

        if ($this->form->yob === null || ! is_numeric($this->form->yob)) {
            return null;
        }

        $birthYear = (int) $this->form->yob;

        return $birthYear > 0 ? $birthYear : null;
    }

    /** @param list<array{id: int, score: float}> $candidates */
    protected function acknowledgmentToken(array $candidates): string
    {
        return hash_hmac(
            'sha256',
            json_encode([
                'firstname'  => $this->form->firstname,
                'surname'    => $this->form->surname,
                'birthname'  => $this->form->birthname,
                'nickname'   => $this->form->nickname,
                'birthYear'  => $this->enteredBirthYear(),
                'candidates' => $candidates,
            ], JSON_THROW_ON_ERROR),
            (string) config('app.key'),
        );
    }
};
