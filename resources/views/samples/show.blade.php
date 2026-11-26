@extends('layouts.app')

@section('title', $sample->title.' — '.$sample->artist->displayName())

@section('content')
    @php
        $isOwned = in_array($sample->id, $owned, true);
        $isOwn = auth()->id() === $sample->artist_id;
        $profile = $sample->artist->artistProfile;
    @endphp

    <div class="page-head">
        <div>
            <p class="eyebrow">
                <a href="{{ route('explore') }}">Catalogue</a>
                @if ($sample->category?->parent) / <a href="{{ route('explore.category', $sample->category->parent) }}">{{ $sample->category->parent->name }}</a>@endif
                @if ($sample->category) / <a href="{{ route('explore.category', $sample->category) }}">{{ $sample->category->name }}</a>@endif
            </p>
            <h1>{{ $sample->title }}</h1>
            <p class="lede">par <a href="{{ route('artists.show', $profile) }}">{{ $profile->stage_name }}</a>
                @if ($profile->is_verified) <span class="verified" title="Artiste vérifié">✓</span>@endif
            </p>
        </div>
        @unless ($sample->isPublished())
            <span class="badge badge-warn">Non publié — visible par vous seul</span>
        @endunless
    </div>

    <div class="detail">
        <div>
            <div class="panel panel-glow" data-track='@json($sample->playerPayload())' data-track-id="{{ $sample->id }}">
                <div class="row" style="gap: 20px; flex-wrap: nowrap">
                    <button type="button" class="play play-lg" data-play aria-label="Écouter" @disabled(! $sample->preview_path)>
                        <x-icon name="play" class="i-play" size="22" />
                        <x-icon name="pause" class="i-pause" size="22" />
                    </button>
                    <div class="wave wave-lg" style="flex: 1"><canvas data-peaks='@json($sample->peaks())'></canvas></div>
                </div>
                <p class="hint" style="margin-top: 12px">Aperçu compressé et tagué (22 kHz mono{{ $sample->duration_ms > 30000 ? ', 30 premières secondes' : '' }}). Le fichier livré est l’original {{ strtoupper($sample->file_format) }}, sans tag.</p>
            </div>

            <dl class="specs" style="margin-top: 24px">
                <div><dt>Type</dt><dd>{{ $sample->typeLabel() }}</dd></div>
                <div><dt>Catégorie</dt><dd>{{ $sample->category?->fullName() ?? '—' }}</dd></div>
                <div><dt>Tempo</dt><dd>{{ $sample->bpm ? $sample->bpm.' BPM' : '—' }}</dd></div>
                <div><dt>Tonalité</dt><dd>{{ $sample->musical_key ?: '—' }}</dd></div>
                <div><dt>Durée</dt><dd>{{ $sample->duration() }}</dd></div>
                <div><dt>Fichier</dt><dd>{{ strtoupper($sample->file_format) }} · {{ $sample->fileSize() }}</dd></div>
            </dl>

            @if ($sample->tagList())
                <div class="chips" style="margin-top: 18px">
                    @foreach ($sample->tagList() as $tag)
                        <a href="{{ route('explore', ['q' => $tag]) }}" class="chip">#{{ $tag }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="stack">
            <div class="panel">
                <p class="label">Licence libre de droits</p>
                <p class="price-lg" style="margin-block: 6px 16px">{{ $sample->price() }}</p>

                @if ($isOwned)
                    <a href="{{ route('account.library') }}" class="btn btn-primary btn-block"><x-icon name="download" size="16" /> Dans votre bibliothèque</a>
                @elseif ($isOwn)
                    <a href="{{ route('studio.samples.edit', $sample) }}" class="btn btn-block"><x-icon name="edit" size="16" /> Modifier dans le studio</a>
                @else
                    <form method="POST" action="{{ route('cart.add', ['type' => 'sample', 'id' => $sample->id]) }}" data-ajax-cart>
                        @csrf
                        <button type="submit" class="btn btn-primary btn-block btn-lg" data-added="Ajouté au panier ✓"><x-icon name="cart" size="16" /> Ajouter au panier</button>
                    </form>
                @endif

                @auth
                    <form method="POST" action="{{ route('favorites.toggle', $sample) }}" data-ajax-fav style="margin-top: 10px">
                        @csrf
                        <button type="submit" @class(['btn btn-ghost btn-block', 'is-on' => in_array($sample->id, $favorites, true)])><x-icon name="heart" size="16" /> Favori</button>
                    </form>
                @endauth

                <ul class="small muted" style="margin: 18px 0 0; padding-left: 18px">
                    <li>Utilisation dans vos morceaux, y compris commerciaux</li>
                    <li>Aucune redevance à reverser</li>
                    <li>Revente ou redistribution du son seul interdite</li>
                </ul>
            </div>

            @if ($sample->pack && $sample->pack->isPublished())
                <a href="{{ route('packs.show', $sample->pack) }}" class="panel artist-card">
                    <x-cover :pack="$sample->pack" style="width: 64px; flex: none" />
                    <div>
                        <p class="label">Inclus dans le pack</p>
                        <strong style="color: var(--ice)">{{ $sample->pack->title }}</strong>
                        <span class="small muted">{{ $sample->pack->price() }}</span>
                    </div>
                </a>
            @endif

            <x-artist-card :profile="$profile" />
        </aside>
    </div>

    @if ($related->isNotEmpty())
        <section class="section">
            <div class="section-head"><h2>Dans la même veine</h2></div>
            <x-tracklist :samples="$related" :owned="$owned" :favorites="$favorites" />
        </section>
    @endif
@endsection
