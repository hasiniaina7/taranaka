<form wire:submit="save">
    @csrf

    <div class="flex flex-col rounded-sm bg-white text-neutral-800 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] md:w-3xl dark:bg-neutral-700 dark:text-neutral-50">
        <div class="flex h-14 min-h-min flex-col rounded-t border-b-2 border-neutral-100 p-2 text-lg font-medium dark:border-neutral-600 dark:text-neutral-50">
            <div class="flex flex-wrap items-start justify-center gap-2">
                <div class="max-w-full min-w-max flex-1 grow">
                    {{ $lineageId ? __('lineage.edit') : __('lineage.create') }}
                </div>

                <div class="max-w-full min-w-max flex-1 grow text-end">
                    <x-ts-icon icon="tabler.git-branch" class="inline-block size-5" />
                </div>
            </div>
        </div>

        <div class="bg-neutral-200 p-4">
            <x-ts-errors class="mb-2" close />

            <div class="grid grid-cols-6 gap-5">
                {{-- name --}}
                <div class="col-span-6">
                    <x-ts-input
                        wire:model.live.debounce.400ms="name"
                        id="name"
                        label="{{ __('lineage.name') }} : *"
                        autofocus
                        required
                    />

                    @if ($duplicateNameWarning)
                        <p class="mt-2 flex items-center gap-1 text-sm text-amber-600 dark:text-amber-400">
                            <x-ts-icon icon="tabler.alert-triangle" class="size-4" />
                            {{ __('lineage.duplicate_name_warning') }}
                        </p>
                    @endif
                </div>

                {{-- origin --}}
                <div class="col-span-6">
                    <x-ts-input wire:model="origin" id="origin" label="{{ __('lineage.origin') }} :" />
                </div>

                {{-- description --}}
                <div class="col-span-6">
                    <x-ts-textarea
                        wire:model="description"
                        id="description"
                        label="{{ __('lineage.description') }} :"
                        maxlength="65535"
                        count
                    />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end rounded-b p-4">
            <x-ts-button type="submit" color="primary"> {{ __('app.save') }} </x-ts-button>
        </div>
    </div>
</form>
