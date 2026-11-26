@extends('layouts.app')

@section('title', 'Artistes')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Vocalistes & producteurs</p>
            <h1>Artistes</h1>
        </div>
        <form method="GET" class="row">
            <input type="search" name="q" value="{{ $search }}" placeholder="Nom d’artiste" aria-label="Rechercher un artiste" style="width: 240px">
            <button class="btn">Chercher</button>
        </form>
    </div>

    @if ($artists->isEmpty())
        <x-empty title="Aucun artiste trouvé" />
    @else
        <div class="grid-3">
            @foreach ($artists as $profile)
                <x-artist-card :profile="$profile" />
            @endforeach
        </div>
        {{ $artists->links() }}
    @endif
@endsection
