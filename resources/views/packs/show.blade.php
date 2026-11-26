@extends('layouts.app')

@section('title', $pack->title)

@section('content')
    @php
        $profile = $pack->artist->artistProfile;
        $isOwn = auth()->id() === $pack->artist_id;
    @endphp

    <div class="detail" style="grid-template-columns: 320px minmax(0, 1fr)">
        <div class="stack">
            <x-cover :pack="$pack" />
            <div class="panel">
                <p class="label">Pack complet · {{ $samples->count() }} sons</p>
                <p class="price-lg" style="margin-block: 6px 4px">{{ $pack->price() }}</p>
                @if ($unitTotal > $pack->price_cents)
                    <p class="small muted">Au lieu de <s>{{ \App\Support\Money::format($unitTotal) }}</s> à l’unité — économie de {{ \App\Support\Money::format($unitTotal - $pack->price_cents) }}</p>
                @endif

                <div style="margin-top: 16px">
                    @if ($ownsAll)
                        <a href="{{ route('account.library') }}" class="btn btn-primary btn-block">Déjà dans votre bibliothèque</a>
                    @elseif ($isOwn)
                        <a href="{{ route('studio.packs.edit', $pack) }}" class="btn btn-block">Modifier dans le studio</a>
                    @elseif ($samples->isNotEmpty())
                        <form method="POST" action="{{ route('cart.add', ['type' => 'pack', 'id' => $pack->id]) }}" data-ajax-cart>
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg btn-block" data-added="Pack ajouté ✓"><x-icon name="cart" size="16" /> Ajouter le pack</button>
                        </form>
                    @endif
                </div>
            </div>
            <x-artist-card :profile="$profile" />
        </div>

        <div>
            <div class="page-head">
                <div>
                    <p class="eyebrow">Pack{{ $pack->genre ? ' · '.$pack->genre : '' }}</p>
                    <h1>{{ $pack->title }}</h1>
                    <p class="lede">par <a href="{{ route('artists.show', $profile) }}">{{ $profile->stage_name }}</a></p>
                </div>
                @unless ($pack->isPublished())
                    <span class="badge badge-warn">Non publié</span>
                @endunless
            </div>

            @if ($pack->description)
                <p class="lede" style="white-space: pre-line; margin-bottom: 24px">{{ $pack->description }}</p>
            @endif

            <x-tracklist :samples="$samples" :owned="$owned" :favorites="$favorites" :show-artist="false" empty="Ce pack est encore vide." />
        </div>
    </div>
@endsection
