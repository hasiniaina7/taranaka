{{--
The list offers accessible filters over the same bounded node set as the tree.
--}}
<div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label
                for="descendant-generation-filter"
                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
            >{{ __('descendant.filter_generation') }}</label>
            <select
                id="descendant-generation-filter"
                wire:model.live="generationFilter"
                class="w-full rounded-md border-gray-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-700"
            >
                <option value="">{{ __('descendant.all_generations') }}</option>
                @foreach ($this->generations as $generation)
                    <option value="{{ $generation }}">{{ __('descendant.generation', ['number' => $generation]) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label
                for="descendant-name-filter"
                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
            >{{ __('descendant.filter_name') }}</label>
            <input
                id="descendant-name-filter"
                type="search"
                wire:model.live.debounce.250ms="nameFilter"
                class="w-full rounded-md border-gray-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-700"
            />
        </div>
    </div>

    @php
        $headers = [
            ['index' => 'generation_label', 'label' => __('descendant.generation_heading')],
            ['index' => 'name', 'label' => __('person.name')],
            ['index' => 'lineage_label', 'label' => __('lineage.lineage')],
            ['index' => 'birth_year', 'label' => __('person.yob')],
        ];

        $rows = $this->filteredDescendants
            ->map(fn (array $descendant): array => [
                ...$descendant,
                'generation_label' => __('descendant.generation', ['number' => $descendant['degree']]),
                'lineage_label'     => implode(', ', $descendant['lineages']) ?: '—',
                'birth_year'        => $descendant['birth_year'] ?? '—',
                'profile_url'       => route('public.people.show', $descendant['id']),
            ])
            ->all();
    @endphp

    @if ($rows !== [])
        <x-ts-table :$headers :$rows striped>
            @interact('column_name', $row)
                <a
                    href="{{ $row['profile_url'] }}"
                    class="rounded-sm font-medium text-indigo-600 hover:text-indigo-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:text-indigo-300"
                >{{ $row['name'] }}</a>
                @if ($row['is_living'])
                    <span class="sr-only">{{ __('person.profile_private') }}</span>
                @endif
            @endinteract
        </x-ts-table>
    @else
        <p class="rounded-md bg-gray-50 p-4 text-sm text-gray-600 dark:bg-neutral-700 dark:text-gray-300">
            {{ $this->descendants->isEmpty() ? __('descendant.empty') : __('app.no_result') }}
        </p>
    @endif

    @if ($this->descendants->contains('degree', $maxDepth))
        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">
            {{ __('descendant.limit_reached') }}
        </p>
    @endif
</div>
