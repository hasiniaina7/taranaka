{{--
The full search view distinguishes people from lineages and preserves living-person privacy.
--}}
<div class="mx-auto w-full max-w-6xl space-y-6 p-4 dark:text-neutral-200">
    <header class="rounded-lg border border-gray-200 bg-white p-5 dark:border-neutral-600 dark:bg-neutral-700">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Recherche globale</h1>

        <label
            for="search-results-query"
            class="mt-4 block text-sm font-medium text-gray-700 dark:text-gray-200"
        >
            Nom d’une personne ou d’une lignée
        </label>

        <input
            id="search-results-query"
            type="search"
            wire:model.live.debounce.300ms="query"
            placeholder="Ex. Jean Rakoto"
            autocomplete="off"
            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm outline-hidden transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-gray-100"
        />
    </header>

    <div
        wire:loading.delay
        wire:target="query,peoplePage,lineagesPage"
        role="status"
        class="rounded-lg bg-white p-5 text-gray-500 dark:bg-neutral-700 dark:text-gray-300"
    >
        Recherche…
    </div>

    <div
        wire:loading.remove
        wire:target="query,peoplePage,lineagesPage"
        class="space-y-6"
    >
        @if ($query === '')
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center dark:border-neutral-600 dark:bg-neutral-700">
                <p class="text-gray-600 dark:text-gray-300">Saisissez un nom pour commencer votre recherche.</p>
            </div>
        @elseif (mb_strlen($query) < $this::MINIMUM_QUERY_LENGTH)
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center dark:border-neutral-600 dark:bg-neutral-700">
                <p class="text-gray-600 dark:text-gray-300">Continuez à saisir pour lancer la recherche.</p>
            </div>
        @elseif ($this->people->isEmpty() && $this->lineages->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center dark:border-neutral-600 dark:bg-neutral-700">
                <p class="text-gray-600 dark:text-gray-300">Aucun résultat pour « {{ $query }} ».</p>
            </div>
        @else
            <section
                aria-labelledby="people-results-heading"
                class="rounded-lg border border-gray-200 bg-white p-5 dark:border-neutral-600 dark:bg-neutral-700"
            >
                <h2
                    id="people-results-heading"
                    class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                >
                    Personnes
                </h2>

                @if ($this->people->isEmpty())
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Aucune personne correspondante.</p>
                @else
                    <ul class="mt-4 divide-y divide-gray-200 dark:divide-neutral-600">
                        @foreach ($this->people as $person)
                            <li
                                wire:key="search-result-person-{{ $person['id'] }}"
                                class="py-4"
                            >
                                <a
                                    href="{{ $person['url'] }}"
                                    class="block rounded-md p-2 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-emerald-600 dark:hover:bg-neutral-800"
                                >
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $person['name'] }}</h3>

                                        @if ($person['private'])
                                            <x-ts-badge text="Profil privé" color="amber" />
                                        @elseif ($person['lifespan'])
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $person['lifespan'] }}</span>
                                        @endif
                                    </div>

                                    @if (! $person['private'] && $person['lineages'] !== [])
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($person['lineages'] as $lineage)
                                                <x-ts-badge text="{{ $lineage['name'] }}" color="indigo" />
                                            @endforeach
                                        </div>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if ($this->people->total() > $this::RESULTS_PER_PAGE)
                        <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">Affiner votre recherche pour réduire les résultats.</p>
                    @endif

                    @if ($this->people->hasPages())
                        <nav
                            aria-label="Pagination des personnes"
                            class="mt-4"
                        >
                            {{ $this->people->links('components/pagination/tailwind') }}
                        </nav>
                    @endif
                @endif
            </section>

            <section
                aria-labelledby="lineage-results-heading"
                class="rounded-lg border border-gray-200 bg-white p-5 dark:border-neutral-600 dark:bg-neutral-700"
            >
                <h2
                    id="lineage-results-heading"
                    class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                >
                    Lignées
                </h2>

                @if ($this->lineages->isEmpty())
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Aucune lignée correspondante.</p>
                @else
                    <ul class="mt-4 divide-y divide-gray-200 dark:divide-neutral-600">
                        @foreach ($this->lineages as $lineage)
                            <li
                                wire:key="search-result-lineage-{{ $lineage['id'] }}"
                                class="py-4"
                            >
                                <a
                                    href="{{ $lineage['url'] }}"
                                    class="flex items-center justify-between gap-3 rounded-md p-2 text-gray-900 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-emerald-600 dark:text-gray-100 dark:hover:bg-neutral-800"
                                >
                                    <span class="font-medium">{{ $lineage['name'] }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $lineage['member_count'] }} membre{{ $lineage['member_count'] === 1 ? '' : 's' }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if ($this->lineages->total() > $this::RESULTS_PER_PAGE)
                        <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">Affiner votre recherche pour réduire les résultats.</p>
                    @endif

                    @if ($this->lineages->hasPages())
                        <nav
                            aria-label="Pagination des lignées"
                            class="mt-4"
                        >
                            {{ $this->lineages->links('components/pagination/tailwind') }}
                        </nav>
                    @endif
                @endif
            </section>
        @endif
    </div>
</div>
