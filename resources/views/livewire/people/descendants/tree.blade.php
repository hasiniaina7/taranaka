{{--
The full bounded tree is computed server-side (BuildDescendantNodes) and
handed to family-chart as one payload; expand/collapse and pan/zoom are then
handled entirely client-side (spec 012).
--}}
<div>
    <h2 class="sr-only">{{ __('descendant.tree') }}</h2>

    @if (empty($this->tree['children']))
        <p class="rounded-md bg-gray-50 p-4 text-sm text-gray-600 dark:bg-neutral-700 dark:text-gray-300">
            {{ __('descendant.empty') }}
        </p>
    @else
        <x-family-tree-canvas
            :tree="$this->tree"
            mode="descendant"
            :profile-url-template="route('public.people.show', ['person' => '__ID__'])"
        />

        @if ($this->limitReached())
            <p class="mt-4 rounded-md bg-amber-50 p-4 text-sm font-medium text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                {{ __('descendant.limit_reached') }}
            </p>
        @endif
    @endif
</div>
