@if ($shown)
    <div class="mb-4 flex items-center gap-2 rounded-sm bg-amber-100 p-3 text-sm text-amber-800 dark:bg-amber-900 dark:text-amber-200">
        <x-ts-icon icon="tabler.lock" class="inline-block size-5 shrink-0" />
        {{ __('person.profile_private') }}
    </div>
@endif
