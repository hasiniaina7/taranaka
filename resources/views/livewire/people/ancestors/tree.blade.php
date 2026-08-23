{{--
The tree renders only branches explicitly loaded by the visitor.
--}}
@php($tree = $this->tree())

<section
    class="overflow-x-auto rounded-sm border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-600 dark:bg-neutral-700"
    aria-label="Arbre des ancêtres"
>
    @if ($tree !== [])
        <ul class="min-w-max" role="tree">
            @include('livewire.people.ancestors.partials.tree-node', ['node' => $tree])
        </ul>

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
