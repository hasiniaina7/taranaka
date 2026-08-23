{{--
The global search bar previews privacy-safe people and lineage matches.
--}}
<div class="relative border-t border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-800">
    <form
        wire:submit="submit"
        role="search"
        class="mx-auto max-w-3xl"
    >
        <label
            for="global-search"
            class="sr-only"
        >
            Rechercher une personne ou une lignée
        </label>

        <div class="relative">
            <x-ts-icon
                icon="tabler.search"
                class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-gray-400"
                aria-hidden="true"
            />

            <input
                id="global-search"
                type="search"
                wire:model.live.debounce.300ms="query"
                placeholder="Rechercher une personne ou une lignée…"
                autocomplete="off"
                aria-controls="global-search-results"
                aria-expanded="{{ mb_strlen(mb_trim($query)) >= $this::MINIMUM_QUERY_LENGTH ? 'true' : 'false' }}"
                class="w-full rounded-lg border border-gray-300 bg-white py-2 pe-24 ps-10 text-sm text-gray-900 shadow-sm outline-hidden transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-100"
            />

            <button
                type="submit"
                class="absolute end-1.5 top-1/2 -translate-y-1/2 rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
            >
                Rechercher
            </button>
        </div>

        <div
            wire:loading.delay
            wire:target="query"
            class="mt-2 text-sm text-gray-500 dark:text-gray-400"
            role="status"
        >
            Recherche…
        </div>

        @if ($query !== '')
            <div
                id="global-search-results"
                wire:loading.remove
                wire:target="query"
                class="absolute inset-x-3 z-40 mt-2 max-h-96 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3 shadow-xl dark:border-neutral-600 dark:bg-neutral-800"
            >
                @if (mb_strlen(mb_trim($query)) < $this::MINIMUM_QUERY_LENGTH)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Continuez à saisir pour lancer la recherche.
                    </p>
                @elseif ($this->people->isEmpty() && $this->lineages->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Aucun résultat pour « {{ $query }} ».
                    </p>
                @else
                    @if ($this->people->isNotEmpty())
                        <section aria-labelledby="global-search-people-heading">
                            <h2
                                id="global-search-people-heading"
                                class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Personnes
                            </h2>

                            <ul class="space-y-1">
                                @foreach ($this->people as $person)
                                    <li wire:key="search-bar-person-{{ $person['id'] }}">
                                        <a
                                            href="{{ $person['url'] }}"
                                            class="flex items-center justify-between gap-3 rounded-md px-2 py-2 text-sm text-gray-800 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-emerald-600 dark:text-gray-100 dark:hover:bg-neutral-700"
                                        >
                                            <span>{{ $person['name'] }}</span>

                                            @if ($person['private'])
                                                <x-ts-badge text="Profil privé" color="amber" />
                                            @elseif ($person['lifespan'])
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $person['lifespan'] }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($this->lineages->isNotEmpty())
                        <section
                            aria-labelledby="global-search-lineages-heading"
                            class="mt-3 border-t border-gray-200 pt-3 dark:border-neutral-600"
                        >
                            <h2
                                id="global-search-lineages-heading"
                                class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Lignées
                            </h2>

                            <ul class="space-y-1">
                                @foreach ($this->lineages as $lineage)
                                    <li wire:key="search-bar-lineage-{{ $lineage['id'] }}">
                                        <a
                                            href="{{ $lineage['url'] }}"
                                            class="block rounded-md px-2 py-2 text-sm text-gray-800 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-emerald-600 dark:text-gray-100 dark:hover:bg-neutral-700"
                                        >
                                            {{ $lineage['name'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                @endif
            </div>
        @endif
    </form>
</div>
