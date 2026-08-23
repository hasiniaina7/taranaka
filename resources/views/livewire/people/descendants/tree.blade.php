{{--
The tree reveals one branch at a time while retaining the bounded query result.
--}}
<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
    <h2 class="sr-only">{{ __('descendant.tree') }}</h2>

    <ul class="min-w-max space-y-3" role="tree">
        @include('livewire.people.descendants.partials.node', ['node' => $this->tree])
    </ul>

    @if (empty($this->tree['children']))
        <p class="mt-4 rounded-md bg-gray-50 p-4 text-sm text-gray-600 dark:bg-neutral-700 dark:text-gray-300">
            {{ __('descendant.empty') }}
        </p>
    @endif
</div>
