{{--
Only branches explicitly loaded by the visitor are queried server-side
(spec 005); family-chart renders the loaded branches and, since spec 012,
triggers a `toggleBranch` Livewire call itself when a visitor clicks a
not-yet-loaded parent slot (family-tree-updated event refreshes the canvas).
--}}
@php($tree = $this->tree())

<section aria-label="Arbre des ancêtres">
    @if ($tree !== [])
        <x-family-tree-canvas
            :tree="$tree"
            mode="ancestor"
            :profile-url-template="route('public.people.show', ['person' => '__ID__'])"
        />

        @if (! $tree['canExpand'] && $tree['children'] === [])
            <p class="mt-4 text-center text-sm text-neutral-500 dark:text-neutral-300">
                Aucun ancêtre enregistré.
            </p>
        @endif

        @if ($this->limitReached())
            <p class="mt-4 text-center text-sm font-medium text-amber-700 dark:text-amber-300">
                Limite de générations atteinte.
            </p>
        @endif
    @endif
</section>
