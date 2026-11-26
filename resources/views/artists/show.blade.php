@extends('layouts.app')

@section('title', $profile->stage_name)

@section('content')
    <section class="panel panel-glow profile-hero">
        <x-avatar :profile="$profile" />
        <div style="flex: 1; min-width: 240px">
            <p class="eyebrow">Artiste{{ $profile->location ? ' · '.$profile->location : '' }}</p>
            <h1 class="row" style="gap: 12px; margin-top: 6px">
                {{ $profile->stage_name }}
                @if ($profile->is_verified)<span class="verified" title="Artiste vérifié">✓</span>@endif
            </h1>
            @if ($profile->bio)
                <p class="lede" style="white-space: pre-line; margin-top: 10px">{{ $profile->bio }}</p>
            @endif
            <div class="row" style="margin-top: 14px">
                @if ($profile->website)<a href="{{ $profile->website }}" class="chip" rel="noopener nofollow" target="_blank">Site</a>@endif
                @if ($profile->instagram)<a href="https://instagram.com/{{ $profile->instagram }}" class="chip" rel="noopener nofollow" target="_blank">Instagram</a>@endif
                @if ($profile->soundcloud)<a href="{{ $profile->soundcloud }}" class="chip" rel="noopener nofollow" target="_blank">SoundCloud</a>@endif
            </div>
        </div>
        <div class="row" style="gap: 28px">
            <div class="hero-stat"><strong>{{ $totals['samples'] }}</strong><span>Sons</span></div>
            <div class="hero-stat"><strong>{{ $packs->count() }}</strong><span>Packs</span></div>
            <div class="hero-stat"><strong>{{ number_format($totals['plays'], 0, ',', ' ') }}</strong><span>Écoutes</span></div>
        </div>
    </section>

    @if ($packs->isNotEmpty())
        <section class="section">
            <div class="section-head"><h2>Catalogue · Packs</h2></div>
            <div class="grid-4">
                @foreach ($packs as $pack)
                    <x-pack-card :pack="$pack" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="section-head"><h2>Tous les sons</h2></div>
        <x-tracklist :samples="$samples" :owned="$owned" :favorites="$favorites" :show-artist="false" empty="Aucun son publié pour l’instant." />
        {{ $samples->links() }}
    </section>
@endsection
