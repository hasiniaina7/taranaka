<div class="mx-auto max-w-3xl p-4">
    <div class="mb-4 text-lg font-medium">{{ __('contributions.my_contributions') }}</div>

    <div class="space-y-2">
        @forelse ($this->contributions() as $contribution)
            <div wire:key="contribution-{{ $contribution->id }}" class="rounded-sm bg-white p-4 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-neutral-700 dark:text-neutral-50">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <span class="font-medium">{{ $contribution->field ?? $contribution->target_type }}</span>
                        : {{ $contribution->old_value }} &rarr; {{ $contribution->new_value }}
                    </div>

                    @if ($contribution->status === \App\Models\Contribution::STATUS_PENDING)
                        <x-ts-badge text="{{ __('contributions.status_pending') }}" color="amber" />
                    @elseif ($contribution->status === \App\Models\Contribution::STATUS_ACCEPTED)
                        <x-ts-badge text="{{ __('contributions.status_accepted') }}" color="green" />
                    @else
                        <x-ts-badge text="{{ __('contributions.status_rejected') }}" color="red" />
                    @endif
                </div>

                @if ($contribution->status === \App\Models\Contribution::STATUS_REJECTED && $contribution->rejection_reason)
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $contribution->rejection_reason }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-400 dark:text-neutral-500">{{ __('contributions.no_contributions') }}</p>
        @endforelse
    </div>
</div>
