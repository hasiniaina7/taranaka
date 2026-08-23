{{--
Each recursive tree node preserves the two-parent layout and accessible controls.
--}}
<li class="flex flex-col items-center" role="treeitem">
    <article class="w-56 rounded-sm border border-neutral-300 bg-neutral-50 p-3 text-center dark:border-neutral-500 dark:bg-neutral-800">
        <a
            href="{{ route('public.people.show', $node['id']) }}"
            class="font-medium text-indigo-700 underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 dark:text-indigo-300"
        >{{ $node['name'] }}</a>

        @if ($node['birth_year'] ?? $node['yob'] ?? null)
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-300">
                {{ $node['birth_year'] ?? $node['yob'] }}
            </p>
        @endif

        @if ($node['living'])
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-300">Personne vivante — détails privés</p>
        @endif

        @if ($node['canExpand'])
            <button
                type="button"
                wire:click="toggleBranch({{ $node['id'] }})"
                aria-expanded="{{ $node['expanded'] ? 'true' : 'false' }}"
                class="mt-2 rounded-sm border border-indigo-500 px-2 py-1 text-xs text-indigo-700 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 dark:text-indigo-300 dark:hover:bg-neutral-700"
            >{{ $node['expanded'] ? 'Replier' : 'Développer' }}</button>
        @endif
    </article>

    @if ($node['expanded'] && $node['children'] !== [])
        <ul class="mt-4 grid grid-cols-2 gap-4 border-t border-neutral-300 pt-4 dark:border-neutral-500" role="group">
            @foreach ($node['children'] as $child)
                @if ($child['unknown'] ?? false)
                    <li role="treeitem">
                        <article class="flex h-full w-56 items-center justify-center rounded-sm border border-dashed border-neutral-400 p-3 text-center text-sm text-neutral-500 dark:border-neutral-500 dark:text-neutral-300">
                            Parent inconnu
                        </article>
                    </li>
                @else
                    @include('livewire.people.ancestors.partials.tree-node', ['node' => $child])
                @endif
            @endforeach
        </ul>
    @endif
</li>
