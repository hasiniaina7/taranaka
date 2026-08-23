<div>
    @section('title')
        &vert; {{ $lineage->name }}
    @endsection

    <div class="max-w-7xl grow overflow-x-auto p-2 dark:text-neutral-200">
        <div class="space-y-6">
            <div class="rounded-lg border bg-white p-4 dark:bg-neutral-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $lineage->name }}</h2>

                @if ($lineage->origin)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $lineage->origin }}</p>
                @endif

                @if ($lineage->description)
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $lineage->description }}</p>
                @endif
            </div>

            <div class="overflow-hidden rounded-lg bg-white dark:bg-neutral-700">
                <div class="border-b border-gray-200 p-4 text-lg font-medium dark:border-neutral-600">
                    {{ __('lineage.members') }}
                </div>

                @php($members = $this->visibleMembers())

                @if ($members->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <x-ts-icon icon="tabler.users" class="mx-auto size-12 text-gray-400" />
                        <h3 class="mt-4 font-medium text-gray-900 dark:text-gray-100">
                            {{ __('lineage.no_members') }}
                        </h3>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-600">
                                @foreach ($members as $person)
                                    <tr wire:key="member-{{ $person->id }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $person->name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                            {{ $person->birthYear }} - {{ $person->deathYear }}
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-ts-link href="{{ route('people.show', $person) }}">
                                                {{ __('app.show') }}
                                            </x-ts-link>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
