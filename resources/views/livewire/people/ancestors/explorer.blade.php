{{--
The explorer shell keeps tree and list navigation consistent.
--}}
<section class="mx-auto w-full max-w-7xl space-y-4" aria-labelledby="ancestor-explorer-title">
    <header class="rounded-sm border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-600 dark:bg-neutral-700">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="w-16 shrink-0">
                    <x-image.photo :person="$person" />
                </div>

                <div>
                    <h1 id="ancestor-explorer-title" class="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">
                        Ancêtres de {{ $person->name }}
                    </h1>
                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Remontez les générations depuis cette personne.
                    </p>
                </div>
            </div>

            <div class="w-full sm:w-48">
                <label
                    for="ancestor-max-depth"
                    class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200"
                >Générations maximum</label>
                <input
                    id="ancestor-max-depth"
                    type="number"
                    min="1"
                    max="128"
                    wire:model.live.debounce.300ms="maxDepth"
                    class="w-full rounded-sm border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100"
                />
            </div>
        </div>

        <nav class="mt-4 flex gap-2" aria-label="Vue des ancêtres">
            <x-ts-button
                type="button"
                color="{{ $view === 'tree' ? 'primary' : 'secondary' }}"
                wire:click="showTree"
                aria-pressed="{{ $view === 'tree' ? 'true' : 'false' }}"
            >Arbre</x-ts-button>
            <x-ts-button
                type="button"
                color="{{ $view === 'list' ? 'primary' : 'secondary' }}"
                wire:click="showList"
                aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"
            >Liste</x-ts-button>
        </nav>
    </header>

    @if ($view === 'tree')
        <livewire:people.ancestors.tree
            :person-id="$personId"
            :max-depth="$maxDepth"
            :key="'ancestor-tree-'.$personId.'-'.$maxDepth"
        />
    @else
        <livewire:people.ancestors.list-view
            :person-id="$personId"
            :max-depth="$maxDepth"
            :key="'ancestor-list-'.$personId.'-'.$maxDepth"
        />
    @endif
</section>
