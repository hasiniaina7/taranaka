<div class="mx-auto max-w-xl p-4">
    <div class="rounded-sm bg-white p-4 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-neutral-700 dark:text-neutral-50">
        <div class="mb-2 text-lg font-medium">{{ __('contributions.propose_partner') }} — {{ $person->name }}</div>

        <x-ts-errors class="mb-2" close />

        <form wire:submit="submit" class="space-y-4">
            <x-ts-input wire:model.live.debounce.300ms="search" label="{{ __('person.search_similar') }}" />

            <div class="space-y-1">
                @foreach ($this->results() as $result)
                    <label wire:key="result-{{ $result->id }}" class="flex items-center gap-2">
                        <input type="radio" wire:model="partner_id" value="{{ $result->id }}" />
                        {{ $result->name }}
                    </label>
                @endforeach
            </div>

            <x-ts-textarea wire:model="justification" label="{{ __('contributions.justification') }}" />

            <x-ts-button type="submit">{{ __('contributions.submit_proposal') }}</x-ts-button>
        </form>
    </div>
</div>
