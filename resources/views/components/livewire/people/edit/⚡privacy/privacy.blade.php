<div class="rounded-sm bg-white p-4 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-neutral-700 dark:text-neutral-50">
    <div class="mb-2 text-lg font-medium">{{ __('person.privacy') }}</div>

    @if ($this->isLiving())
        <x-ts-toggle
            wire:model="is_publicly_visible"
            wire:click="toggle"
            label="{{ __('person.make_public') }}"
        />
        <p class="mt-1 text-sm text-gray-400 dark:text-neutral-400">{{ __('person.make_public_hint') }}</p>
    @else
        <x-ts-toggle wire:model="is_publicly_visible" disabled label="{{ __('person.make_public') }}" />
        <p class="mt-1 text-sm text-gray-400 dark:text-neutral-400">{{ __('person.now_public_deceased') }}</p>
    @endif
</div>
