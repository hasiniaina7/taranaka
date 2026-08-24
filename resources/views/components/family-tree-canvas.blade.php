@props([
    'tree',
    'mode',
    'profileUrlTemplate',
])

<div
    wire:ignore
    x-data="familyTreeCanvas({
        mode: @js($mode),
        tree: @js($tree),
        profileUrlTemplate: @js($profileUrlTemplate),
    })"
    x-on:family-tree-updated.window="update($event.detail.tree)"
    class="family-tree-canvas relative h-[70vh] min-h-[420px] w-full touch-pan-x touch-pan-y overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-neutral-700 dark:bg-neutral-900"
>
    <div x-ref="canvas" class="f3 h-full w-full"></div>
</div>
