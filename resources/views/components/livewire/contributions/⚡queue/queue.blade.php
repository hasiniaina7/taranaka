<div class="mx-auto max-w-4xl p-4">
    <div class="mb-4 flex items-center justify-between">
        <div class="text-lg font-medium">{{ __('contributions.moderation_queue') }}</div>

        <x-ts-select.styled
            wire:model.live="status"
            :options="[
                ['id' => \App\Models\Contribution::STATUS_PENDING, 'name' => __('contributions.status_pending')],
                ['id' => \App\Models\Contribution::STATUS_ACCEPTED, 'name' => __('contributions.status_accepted')],
                ['id' => \App\Models\Contribution::STATUS_REJECTED, 'name' => __('contributions.status_rejected')],
                ['id' => '', 'name' => __('contributions.status_all')],
            ]"
            select="label:name|value:id"
        />
    </div>

    <div class="space-y-2">
        @forelse ($this->contributions() as $contribution)
            <a
                wire:key="contribution-{{ $contribution->id }}"
                href="{{ route('contributions.review', $contribution) }}"
                class="block rounded-sm bg-white p-4 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-neutral-700 dark:text-neutral-50"
            >
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <span class="font-medium">{{ $contribution->author?->name }}</span>
                        — {{ $contribution->field ?? $contribution->target_type }}
                        — {{ $contribution->created_at?->diffForHumans() }}
                    </div>

                    @unless ($contribution->isStillApplicable())
                        <x-ts-badge text="{{ __('contributions.target_changed') }}" color="orange" />
                    @endunless
                </div>
            </a>
        @empty
            <p class="text-sm text-gray-400 dark:text-neutral-500">{{ __('contributions.no_contributions') }}</p>
        @endforelse
    </div>
</div>
