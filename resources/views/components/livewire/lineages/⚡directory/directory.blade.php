<div>
    @section('title')
        &vert; {{ __('lineage.lineages') }}
    @endsection

    <div class="max-w-7xl grow overflow-x-auto p-2 dark:text-neutral-200">
        <div class="space-y-6">
            <div class="rounded-lg border bg-white p-4 dark:bg-neutral-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('lineage.lineages') }}</h2>
            </div>

            <div class="rounded-lg bg-white p-4 dark:bg-neutral-700">
                <x-ts-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('app.search') }} ..."
                    class="w-full max-w-md"
                />
            </div>

            <div wire:loading class="space-y-3">
                @for ($i = 0; $i < 3; $i++)
                    <div class="h-16 animate-pulse rounded-lg bg-gray-200 dark:bg-neutral-600"></div>
                @endfor
            </div>

            <div wire:loading.remove class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @forelse ($this->lineages as $lineage)
                    <a
                        wire:key="lineage-{{ $lineage->id }}"
                        href="{{ route('lineages.show', $lineage) }}"
                        class="block rounded-lg border bg-white p-4 transition hover:shadow-md dark:bg-neutral-700"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $lineage->name }}</h3>

                        @if ($lineage->origin)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $lineage->origin }}</p>
                        @endif

                        @if ($lineage->description)
                            <p class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $lineage->description }}
                            </p>
                        @endif
                    </a>
                @empty
                    <div class="col-span-full px-6 py-12 text-center">
                        <x-ts-icon icon="tabler.git-branch" class="mx-auto size-12 text-gray-400" />
                        <h3 class="mt-4 font-medium text-gray-900 dark:text-gray-100">{{ __('lineage.no_lineages') }}</h3>
                    </div>
                @endforelse
            </div>

            @if ($this->lineages->hasPages())
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-neutral-600 dark:bg-neutral-700">
                    {{ $this->lineages->links('components/pagination/tailwind') }}
                </div>
            @endif
        </div>
    </div>
</div>
