<div>
    @section('title')
        &vert; {{ __('lineage.lineages') }}
    @endsection

    <div class="max-w-7xl grow overflow-x-auto p-2 dark:text-neutral-200">
        <div class="space-y-6">
            <div class="flex items-center justify-between rounded-lg border bg-white p-4 dark:bg-neutral-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('lineage.lineages') }}</h2>

                <x-ts-link href="{{ route('lineages.create') }}">
                    <x-ts-button color="primary">{{ __('lineage.create') }}</x-ts-button>
                </x-ts-link>
            </div>

            <div class="rounded-lg bg-white p-4 dark:bg-neutral-700">
                <x-ts-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('app.search') }} ..."
                    class="w-full max-w-md"
                />
            </div>

            <div class="overflow-hidden rounded-lg bg-white dark:bg-neutral-700">
                @if ($this->lineages->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        {{ __('lineage.name') }}
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        {{ __('lineage.members') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        {{ __('backup.actions') }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-600">
                                @foreach ($this->lineages as $lineage)
                                    <tr wire:key="manage-lineage-{{ $lineage->id }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $lineage->name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                            {{ $lineage->people_count }}
                                        </td>
                                        <td class="space-x-4 px-6 py-4 text-right whitespace-nowrap">
                                            <x-ts-link href="{{ route('lineages.edit', $lineage) }}">
                                                {{ __('app.edit') }}
                                            </x-ts-link>

                                            @if ($lineage->people_count > 0)
                                                <span
                                                    class="cursor-not-allowed text-gray-400"
                                                    title="{{ __('lineage.delete_blocked') }}"
                                                >
                                                    {{ __('app.delete') }}
                                                </span>
                                            @else
                                                <button
                                                    wire:click="delete({{ $lineage->id }})"
                                                    wire:confirm="{{ __('app.confirm') }}"
                                                    class="text-red-600 hover:text-red-800"
                                                >
                                                    {{ __('app.delete') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <x-ts-icon icon="tabler.git-branch" class="mx-auto size-12 text-gray-400" />
                        <h3 class="mt-4 font-medium text-gray-900 dark:text-gray-100">{{ __('lineage.no_lineages') }}</h3>
                    </div>
                @endif
            </div>

            @if ($this->lineages->hasPages())
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-neutral-600 dark:bg-neutral-700">
                    {{ $this->lineages->links('components/pagination/tailwind') }}
                </div>
            @endif
        </div>
    </div>
</div>
