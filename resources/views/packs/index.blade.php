@extends('layouts.app')

@section('title', 'Packs')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Collections complètes</p>
            <h1>Packs</h1>
            <p class="lede">Un pack réunit les sons d’un même projet : achetez-le en entier pour moins cher qu’à l’unité.</p>
        </div>
    </div>

    <div class="chips" style="margin-bottom: 24px">
        <a href="{{ route('packs.index') }}" @class(['chip', 'is-active' => ! $genre])>Tous</a>
        @foreach (config('soundbank.genres') as $item)
            <a href="{{ route('packs.index', ['genre' => $item]) }}" @class(['chip', 'is-active' => $genre === $item])>{{ $item }}</a>
        @endforeach
    </div>

    @if ($packs->isEmpty())
        <x-empty title="Aucun pack pour l’instant"><p>Revenez bientôt.</p></x-empty>
    @else
        <div class="grid-auto">
            @foreach ($packs as $pack)
                <x-pack-card :pack="$pack" />
            @endforeach
        </div>
        {{ $packs->links() }}
    @endif
@endsection
