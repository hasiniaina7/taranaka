{{--
Warns contributors about scored duplicate candidates without exposing private profile details.
--}}
<div>
    @if ($candidates !== [])
        <section
            aria-labelledby="duplicate-warning-heading"
            aria-live="polite"
            @class([
                'rounded-lg border p-4 shadow-sm',
                'border-amber-400 bg-amber-50 text-amber-950 dark:border-amber-500 dark:bg-amber-950/40 dark:text-amber-50' => $this->hasHighConfidence(),
                'border-sky-300 bg-sky-50 text-sky-950 dark:border-sky-600 dark:bg-sky-950/40 dark:text-sky-50' => ! $this->hasHighConfidence(),
            ])
        >
            <div class="flex items-start gap-3">
                <x-ts-icon
                    icon="tabler.alert-triangle"
                    class="mt-0.5 size-5 shrink-0"
                    aria-hidden="true"
                />
    
                <div class="min-w-0 flex-1">
                    <h2
                        id="duplicate-warning-heading"
                        class="font-semibold"
                    >
                        {{ __('person.duplicate_warning_title') }}
                    </h2>
    
                    <p class="mt-1 text-sm">
                        {{ __('person.duplicate_warning_intro') }}
                    </p>
                </div>
            </div>
    
            <ul class="mt-4 space-y-3">
                @foreach ($candidates as $candidate)
                    <li wire:key="duplicate-candidate-{{ $candidate['id'] }}">
                        <article class="rounded-md border border-current/20 bg-white/70 p-3 dark:bg-neutral-900/40">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <a
                                        href="{{ $candidate['url'] }}"
                                        class="font-semibold underline decoration-transparent underline-offset-2 transition hover:decoration-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                                    >
                                        {{ $candidate['name'] }}
                                    </a>
    
                                    @if ($candidate['private'])
                                        <p class="mt-1 text-xs font-medium">
                                            {{ __('person.duplicate_private_profile') }}
                                        </p>
                                    @else
                                        @if ($candidate['lifespan'])
                                            <p class="mt-1 text-sm">{{ $candidate['lifespan'] }}</p>
                                        @endif
    
                                        @if ($candidate['lineages'] !== [])
                                            <p class="mt-1 text-xs">
                                                {{ __('lineage.lineage') }} : {{ implode(', ', $candidate['lineages']) }}
                                            </p>
                                        @endif
                                    @endif
                                </div>
    
                                <span class="rounded-full border border-current/30 px-2 py-1 text-xs font-semibold">
                                    {{ $candidate['high_confidence'] ? __('person.duplicate_high_confidence') : __('person.duplicate_low_confidence') }}
                                </span>
                            </div>
    
                            <div class="mt-3 flex items-center gap-3">
                                <label
                                    for="duplicate-score-{{ $candidate['id'] }}"
                                    class="text-xs font-medium"
                                >
                                    {{ __('person.duplicate_similarity') }} : {{ $candidate['percentage'] }} %
                                </label>
    
                                <progress
                                    id="duplicate-score-{{ $candidate['id'] }}"
                                    value="{{ $candidate['percentage'] }}"
                                    max="100"
                                    class="h-2 min-w-24 flex-1 accent-amber-600"
                                >
                                    {{ $candidate['percentage'] }} %
                                </progress>
                            </div>
    
                            <button
                                type="button"
                                wire:click="reuse({{ $candidate['id'] }})"
                                class="mt-3 rounded-md border border-current/40 px-3 py-2 text-sm font-semibold transition hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current dark:hover:bg-white/10"
                            >
                                {{ __('person.duplicate_same_person') }}
                            </button>
                        </article>
                    </li>
                @endforeach
            </ul>
    
            @if ($this->hasHighConfidence())
                <button
                    type="button"
                    wire:click="acknowledgeDistinct"
                    class="mt-4 rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 dark:bg-amber-500 dark:text-amber-950 dark:hover:bg-amber-400"
                >
                    {{ __('person.duplicate_distinct_people') }}
                </button>
            @endif
        </section>
    @endif
</div>
