{{--
Each recursive node exposes only public-safe summary fields and branch controls.
--}}
<li
    wire:key="descendant-node-{{ $node['sequence'] }}"
    role="treeitem"
    aria-expanded="{{ ! empty($node['children']) ? (in_array($node['id'], $expandedNodeIds, true) ? 'true' : 'false') : 'false' }}"
>
    <div class="flex items-center gap-2">
        <a
            href="{{ route('public.people.show', $node['id']) }}"
            class="min-w-56 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 shadow-sm transition hover:border-indigo-400 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:border-neutral-600 dark:bg-neutral-700 dark:hover:border-indigo-400"
        >
            <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $node['name'] }}</span>
            <span class="block text-sm text-gray-500 dark:text-gray-300">
                {{ $node['birth_year'] ?? '?' }} — {{ $node['is_living'] ? __('person.living') : ($node['death_year'] ?? '?') }}
            </span>

            @if ($node['is_living'])
                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                    <x-ts-icon icon="tabler.lock" class="size-3.5" aria-hidden="true" />
                    {{ __('person.profile_private') }}
                </span>
            @endif
        </a>

        @if (! empty($node['children']))
            <button
                type="button"
                wire:click="toggleNode({{ $node['id'] }})"
                wire:loading.attr="disabled"
                wire:target="toggleNode({{ $node['id'] }})"
                class="rounded-full border border-gray-300 p-2 text-gray-600 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:border-neutral-600 dark:text-gray-200 dark:hover:bg-neutral-700"
                aria-label="{{ in_array($node['id'], $expandedNodeIds, true) ? __('descendant.collapse', ['name' => $node['name']]) : __('descendant.expand', ['name' => $node['name']]) }}"
                aria-expanded="{{ in_array($node['id'], $expandedNodeIds, true) ? 'true' : 'false' }}"
            >
                <x-ts-icon
                    wire:loading.remove
                    wire:target="toggleNode({{ $node['id'] }})"
                    icon="{{ in_array($node['id'], $expandedNodeIds, true) ? 'tabler.chevron-down' : 'tabler.chevron-right' }}"
                    class="size-5"
                    aria-hidden="true"
                />
                <x-ts-icon
                    wire:loading
                    wire:target="toggleNode({{ $node['id'] }})"
                    icon="tabler.loader-2"
                    class="size-5 animate-spin"
                    aria-hidden="true"
                />
            </button>
        @endif
    </div>

    @if ($node['degree'] === $maxDepth)
        <p class="mt-2 text-sm font-medium text-amber-700 dark:text-amber-300">
            {{ __('descendant.limit_reached') }}
        </p>
    @endif

    @if (! empty($node['children']) && in_array($node['id'], $expandedNodeIds, true))
        <ul class="mt-3 ml-8 space-y-3 border-l border-gray-200 pl-5 dark:border-neutral-600" role="group">
            @foreach ($node['children'] as $childNode)
                @include('livewire.people.descendants.partials.node', ['node' => $childNode])
            @endforeach
        </ul>
    @endif
</li>
