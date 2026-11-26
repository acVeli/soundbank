@extends('layouts.app')

@section('title', 'Favoris')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace acheteur</p>
            <h1>Favoris</h1>
        </div>
    </div>

    @include('account.nav')

    <x-tracklist :samples="$samples" :owned="$owned" :favorites="$samples->pluck('id')->all()" empty="Aucun favori">
        <p>Cliquez sur le cœur d’un son pour le garder sous la main.</p>
    </x-tracklist>
    {{ $samples->links() }}
@endsection
