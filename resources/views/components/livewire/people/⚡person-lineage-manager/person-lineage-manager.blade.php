<div class="flex flex-col rounded-sm bg-white text-neutral-800 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-neutral-700 dark:text-neutral-50">
    <div class="flex h-14 min-h-min flex-col rounded-t border-b-2 border-neutral-100 p-2 text-lg font-medium dark:border-neutral-600 dark:text-neutral-50">
        <div class="flex flex-wrap items-start justify-center gap-2">
            <div class="max-w-full min-w-max flex-1 grow">{{ __('lineage.my_lineages') }}</div>

            <div class="max-w-full min-w-max flex-1 grow text-end">
                <x-ts-icon icon="tabler.git-branch" class="inline-block size-5" />
            </div>
        </div>
    </div>

    <div class="bg-neutral-200 p-4">
        <div class="flex flex-wrap gap-2">
            @forelse ($person->lineages as $lineage)
                <span
                    wire:key="attached-lineage-{{ $lineage->id }}"
                    class="inline-flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-700"
                >
                    {{ $lineage->name }}

                    <button
                        type="button"
                        wire:click="detach({{ $lineage->id }})"
                        wire:confirm="{{ __('lineage.detach_confirm') }}"
                        title="{{ __('lineage.detach') }}"
                        class="text-indigo-500 hover:text-indigo-800"
                    >
                        &times;
                    </button>
                </span>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('lineage.no_lineages') }}</p>
            @endforelse
        </div>

        <div class="mt-4 flex items-end gap-2">
            <div class="flex-1">
                <x-ts-select.styled
                    wire:model="selectedLineageId"
                    id="selectedLineageId"
                    label="{{ __('lineage.attach') }} :"
                    :options="$this->availableLineages()"
                    select="label:name|value:id"
                    placeholder="{{ __('app.select') }} ..."
                    searchable
                />
            </div>

            <x-ts-button
                type="button"
                color="primary"
                wire:click="attach"
                :disabled="! $selectedLineageId"
            >
                {{ __('app.add') }}
            </x-ts-button>
        </div>
    </div>
</div>
