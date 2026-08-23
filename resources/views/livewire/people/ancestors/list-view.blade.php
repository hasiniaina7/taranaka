{{--
The table lists the bounded traversal and offers an explicit generation filter.
--}}
@php
    $headers = [
        ['index' => 'generation', 'label' => 'Génération'],
        ['index' => 'name', 'label' => 'Nom'],
        ['index' => 'lineage', 'label' => 'Lignée'],
        ['index' => 'birth_year', 'label' => 'Année de naissance'],
    ];
@endphp

<section
    class="space-y-4 rounded-sm border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-600 dark:bg-neutral-700"
    aria-label="Liste des ancêtres"
>
    <div class="max-w-xs">
        <x-ts-select.styled
            id="ancestor-generation-filter"
            label="Filtrer par génération"
            wire:model.live="generationFilter"
            :options="$this->generationOptions()"
            select="label:label|value:value"
        />
    </div>

    <x-ts-table
        :$headers
        :rows="$this->rows()"
        link="{url}"
        striped
    >
        @interact('column_name', $row)
            <a
                href="{{ $row['url'] }}"
                class="font-medium text-indigo-700 underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 dark:text-indigo-300"
            >{{ $row['name'] }}</a>
            @if ($row['living'])
                <span class="sr-only">Personne vivante — détails privés</span>
            @endif
        @endinteract

        <x-slot:empty>
            Aucun ancêtre enregistré.
        </x-slot:empty>
    </x-ts-table>

    @if ($limitReached)
        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">
            Limite de générations atteinte.
        </p>
    @endif
</section>
