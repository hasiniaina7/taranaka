{{--
The public page mounts the ancestor explorer inside the shared application layout.
--}}
@section('title')
    &vert; Ancêtres de {{ $person->name }}
@endsection

<x-app-layout>
    <section class="w-full p-2" aria-label="Exploration des ancêtres">
        <livewire:people.ancestors.explorer :person-id="$person->id" />
    </section>
</x-app-layout>
