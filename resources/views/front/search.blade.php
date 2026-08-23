{{--
The public search page hosts reactive results while keeping the application layout shared.
--}}
@section('title')
    &vert; Recherche
@endsection

<x-app-layout>
    <livewire:search-results :query="$query" />
</x-app-layout>
