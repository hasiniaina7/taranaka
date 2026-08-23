{{--
The public wrapper keeps the descendant explorer inside the shared application layout.
--}}
@section('title')
    &vert; {{ __('person.descendants') }} — {{ $person->name }}
@endsection

<x-app-layout>
    <livewire:people.descendants.explorer :$person />
</x-app-layout>
