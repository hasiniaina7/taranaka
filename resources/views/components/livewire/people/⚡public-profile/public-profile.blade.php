<div>
    @section('title')
        &vert; {{ $person->name }}
    @endsection

    @php($fields = $this->fields())
    @php($visible = $this->isPubliclyVisible())
    @php($photoUrl = $this->photoUrl())

    <div class="mx-auto max-w-4xl grow overflow-x-auto p-2 dark:text-neutral-200">
        <x-privacy-banner :shown="! $visible" />

        <div class="rounded-lg border bg-white p-4 dark:bg-neutral-700">
            <div class="flex flex-col items-center gap-4 md:flex-row md:items-start">
                <div class="shrink-0">
                    @if ($photoUrl)
                        <img
                            src="{{ $photoUrl }}"
                            alt="{{ $person->name }}"
                            class="size-32 rounded-full object-cover"
                        />
                    @else
                        <div
                            data-person-photo-placeholder
                            class="flex size-32 items-center justify-center rounded-full bg-gray-200 text-gray-500 dark:bg-neutral-600 dark:text-neutral-300"
                        >
                            <x-ts-icon icon="tabler.user" class="size-16" />
                        </div>
                    @endif
                </div>

                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $person->name }}</h1>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $fields['yob'] ?? '?' }} — {{ $fields['yod'] ?? __('person.living') }}
                    </p>

                    @if ($fields['summary'])
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $fields['summary'] }}</p>
                    @endif

                    <div class="mt-3 flex flex-wrap justify-center gap-2 md:justify-start">
                        @forelse ($fields['lineages'] as $lineage)
                            <a href="{{ route('lineages.show', $lineage) }}">
                                <x-ts-badge text="{{ $lineage->name }}" color="indigo" />
                            </a>
                        @empty
                            <span class="text-xs text-gray-400 dark:text-neutral-500">{{ __('lineage.no_lineages') }}</span>
                        @endforelse
                    </div>

                    <div class="mt-4 flex flex-wrap justify-center gap-2 md:justify-start">
                        <x-ts-button href="/people/{{ $person->id }}/ancestors" color="secondary" class="text-sm">
                            <x-ts-icon icon="tabler.binary-tree" class="inline-block size-5 rotate-180" />
                            {{ __('person.explore_ancestors') }}
                        </x-ts-button>

                        <x-ts-button href="/people/{{ $person->id }}/descendants" color="secondary" class="text-sm">
                            <x-ts-icon icon="tabler.binary-tree" class="inline-block size-5" />
                            {{ __('person.explore_descendants') }}
                        </x-ts-button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border bg-white p-4 dark:bg-neutral-700">
                <h2 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('person.parents') }}</h2>

                @if ($fields['father'] || $fields['mother'])
                    <ul class="space-y-1">
                        @if ($fields['father'])
                            <li>
                                <a
                                    href="{{ route('public.people.show', $fields['father']) }}"
                                    class="text-indigo-600 hover:text-yellow-500"
                                >{{ $fields['father']->name }}</a>
                            </li>
                        @endif

                        @if ($fields['mother'])
                            <li>
                                <a
                                    href="{{ route('public.people.show', $fields['mother']) }}"
                                    class="text-indigo-600 hover:text-yellow-500"
                                >{{ $fields['mother']->name }}</a>
                            </li>
                        @endif
                    </ul>
                @else
                    <p class="text-sm text-gray-400 dark:text-neutral-500">{{ __('person.no_parents_known') }}</p>
                @endif
            </div>

            <div class="rounded-lg border bg-white p-4 dark:bg-neutral-700">
                <h2 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('person.partners') }}</h2>

                @forelse ($fields['partners'] as $partner)
                    <ul class="space-y-1">
                        <li wire:key="partner-{{ $partner->id }}">
                            <a
                                href="{{ route('public.people.show', $partner) }}"
                                class="text-indigo-600 hover:text-yellow-500"
                            >{{ $partner->name }}</a>
                        </li>
                    </ul>
                @empty
                    <p class="text-sm text-gray-400 dark:text-neutral-500">{{ __('person.no_partners_recorded') }}</p>
                @endforelse
            </div>

            <div class="rounded-lg border bg-white p-4 dark:bg-neutral-700">
                <h2 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('person.children') }}</h2>

                @forelse ($fields['children'] as $child)
                    <ul class="space-y-1">
                        <li wire:key="child-{{ $child->id }}">
                            <a
                                href="{{ route('public.people.show', $child) }}"
                                class="text-indigo-600 hover:text-yellow-500"
                            >{{ $child->name }}</a>
                        </li>
                    </ul>
                @empty
                    <p class="text-sm text-gray-400 dark:text-neutral-500">{{ __('person.no_children_recorded') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
