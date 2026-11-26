@extends('studio.layout')

@section('title', 'Catalogue & packs')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Studio · Catalogue</p>
            <h1>Catalogue & packs</h1>
            <p class="lede">Rangez vos sons en packs (un projet, une session, une ambiance). Un pack se vend en entier ; ses sons restent achetables à l’unité.</p>
        </div>
        <a href="{{ route('studio.packs.create') }}" class="btn btn-primary"><x-icon name="plus" size="14" /> Nouveau pack</a>
    </div>

    <div class="grid-auto">
        @foreach ($packs as $pack)
            <div class="panel" style="padding: 14px">
                <a href="{{ route('studio.packs.edit', $pack) }}" class="card">
                    <x-cover :pack="$pack" />
                </a>
                <div class="card-body">
                    <div class="row between">
                        <strong><a href="{{ route('studio.packs.edit', $pack) }}" style="color: var(--ice)">{{ $pack->title }}</a></strong>
                        @if ($pack->isPublished())
                            <span class="badge badge-ok"><span class="dot"></span> En ligne</span>
                        @else
                            <span class="badge badge-muted">Brouillon</span>
                        @endif
                    </div>
                    <div class="meta">
                        <span>{{ $pack->published_count }} / {{ $pack->samples_count }} sons publiés</span>
                        <span class="price">{{ $pack->price() }}</span>
                    </div>
                    <div class="row" style="margin-top: 12px; gap: 8px">
                        <a href="{{ route('studio.samples.create', ['pack' => $pack->id]) }}" class="btn btn-sm">+ Sons</a>
                        <a href="{{ route('studio.samples.index', ['pack' => $pack->id]) }}" class="btn btn-sm btn-ghost">Voir les sons</a>
                    </div>
                </div>
            </div>
        @endforeach

        <a href="{{ route('studio.samples.index', ['pack' => -1]) }}" class="panel" style="display: grid; place-items: center; text-align: center; min-height: 260px; color: var(--text)">
            <div>
                <x-icon name="wave" size="32" style="color: var(--cyan)" />
                <p class="display" style="margin-top: 10px">Sons à l’unité</p>
                <p class="muted">{{ $loose }} son(s) hors pack</p>
            </div>
        </a>
    </div>
@endsection
