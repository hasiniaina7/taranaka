<div class="mx-auto max-w-2xl p-4">
    <div class="rounded-sm bg-white p-4 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-neutral-700 dark:text-neutral-50">
        <div class="mb-2 text-lg font-medium">{{ __('contributions.review_proposal') }}</div>

        <x-ts-errors class="mb-2" close />

        @unless ($this->isStillApplicable())
            <x-ts-alert
                title="{{ __('contributions.target_changed') }}"
                text="{{ __('contributions.target_changed_hint') }}"
                color="orange"
                class="mb-4"
            />
        @endunless

        <div class="mb-4 text-sm text-gray-500 dark:text-neutral-400">
            {{ __('contributions.author') }}: {{ $contribution->author?->name }}
            — {{ $contribution->created_at?->diffForHumans() }}
        </div>

        @if ($contribution->justification)
            <p class="mb-4 text-sm">{{ $contribution->justification }}</p>
        @endif

        <div class="mb-4 grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-neutral-400">{{ __('contributions.current_value') }}</div>
                <div>{{ $contribution->old_value ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-neutral-400">{{ __('contributions.new_value') }}</div>
                <div>{{ $contribution->new_value }}</div>
            </div>
        </div>

        @if ($contribution->status === \App\Models\Contribution::STATUS_PENDING)
            <div class="flex flex-wrap items-end gap-4">
                <x-ts-button wire:click="accept" color="primary">
                    {{ __('contributions.accept') }}
                </x-ts-button>

                <div class="grow">
                    <x-ts-input wire:model="rejection_reason" label="{{ __('contributions.rejection_reason') }}" />
                </div>

                <x-ts-button wire:click="reject" color="red">
                    {{ __('contributions.reject') }}
                </x-ts-button>
            </div>
        @else
            <x-ts-badge
                text="{{ $contribution->status === \App\Models\Contribution::STATUS_ACCEPTED ? __('contributions.status_accepted') : __('contributions.status_rejected') }}"
                color="{{ $contribution->status === \App\Models\Contribution::STATUS_ACCEPTED ? 'green' : 'red' }}"
            />

            @if ($contribution->rejection_reason)
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $contribution->rejection_reason }}</p>
            @endif
        @endif
    </div>
</div>
