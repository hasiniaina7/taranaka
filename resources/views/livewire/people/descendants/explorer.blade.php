{{--
The explorer owns the shared person header, presentation tabs, and depth control.
--}}
<section class="mx-auto w-full max-w-6xl grow space-y-5 p-4 dark:text-neutral-200">
    <header class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col items-center gap-4 sm:flex-row">
            @if ($this->photoUrl)
                <img
                    src="{{ $this->photoUrl }}"
                    alt="{{ $person->name }}"
                    class="size-20 rounded-full object-cover"
                />
            @else
                <div
                    class="flex size-20 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-neutral-700 dark:text-neutral-300"
                    aria-hidden="true"
                >
                    <x-ts-icon icon="tabler.user" class="size-10" />
                </div>
            @endif

            <div class="min-w-0 flex-1 text-center sm:text-left">
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-300">{{ __('person.descendants') }}</p>
                <h1 class="truncate text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    <a
                        href="{{ route('public.people.show', $person) }}"
                        class="rounded-sm hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >{{ $person->name }}</a>
                </h1>
            </div>

            <div>
                <label
                    for="descendant-max-depth"
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                >{{ __('descendant.generation_limit') }}</label>
                <select
                    id="descendant-max-depth"
                    wire:model.live="maxDepth"
                    class="rounded-md border-gray-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-700"
                >
                    @foreach (range(1, 10) as $depth)
                        <option value="{{ $depth }}">{{ $depth }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </header>

    <nav
        class="flex gap-2"
        aria-label="{{ __('descendant.views') }}"
    >
        <x-ts-button
            type="button"
            color="{{ $view === 'tree' ? 'primary' : 'secondary' }}"
            wire:click="showTree"
            aria-pressed="{{ $view === 'tree' ? 'true' : 'false' }}"
        >
            <x-ts-icon icon="tabler.binary-tree" class="inline-block size-5" aria-hidden="true" />
            {{ __('descendant.tree') }}
        </x-ts-button>

        <x-ts-button
            type="button"
            color="{{ $view === 'list' ? 'primary' : 'secondary' }}"
            wire:click="showList"
            aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"
        >
            <x-ts-icon icon="tabler.list" class="inline-block size-5" aria-hidden="true" />
            {{ __('descendant.list') }}
        </x-ts-button>
    </nav>

    <section aria-live="polite">
        @if ($view === 'tree')
            <livewire:people.descendants.tree
                :$person
                :$maxDepth
                :key="'descendant-tree-'.$person->id.'-'.$maxDepth"
            />
        @else
            <livewire:people.descendants.list-view
                :$person
                :$maxDepth
                :key="'descendant-list-'.$person->id.'-'.$maxDepth"
            />
        @endif
    </section>
</section>
