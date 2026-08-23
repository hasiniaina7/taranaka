<div class="mx-auto max-w-xl p-4">
    <div class="rounded-sm bg-white p-4 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-neutral-700 dark:text-neutral-50">
        <div class="mb-2 text-lg font-medium">{{ __('contributions.propose_correction') }} — {{ $person->name }}</div>

        <x-ts-errors class="mb-2" close />

        <form wire:submit="submit" class="space-y-4">
            <x-ts-select.styled
                wire:model="field"
                :options="collect($this->fields())->map(fn ($field) => ['id' => $field, 'name' => __('person.' . $field)])"
                select="label:name|value:id"
                label="{{ __('contributions.field') }}"
            />

            <div class="text-sm text-gray-500 dark:text-neutral-400">
                {{ __('contributions.current_value') }}: {{ $person->getAttribute($field) ?? '—' }}
            </div>

            <x-ts-input wire:model="new_value" label="{{ __('contributions.new_value') }}" />

            <x-ts-textarea wire:model="justification" label="{{ __('contributions.justification') }}" />

            <x-ts-button type="submit">{{ __('contributions.submit_proposal') }}</x-ts-button>
        </form>
    </div>
</div>
