@props(['sample', 'owned' => [], 'favorites' => [], 'showArtist' => true])
@php
    $isOwned = in_array($sample->id, $owned, true);
    $isFavorite = in_array($sample->id, $favorites, true);
    $isOwn = auth()->id() === $sample->artist_id;
@endphp
<div class="track" data-track='@json($sample->playerPayload())' data-track-id="{{ $sample->id }}">
    <button type="button" class="play" data-play aria-label="Écouter {{ $sample->title }}" @disabled(! $sample->preview_path)>
        <x-icon name="play" class="i-play" size="16" />
        <x-icon name="pause" class="i-pause" size="16" />
    </button>

    <div class="track-title">
        <a href="{{ route('samples.show', $sample) }}">{{ $sample->title }}</a>
        @if ($showArtist && $sample->artist?->artistProfile)
            <a class="by" href="{{ route('artists.show', $sample->artist->artistProfile) }}">{{ $sample->artist->artistProfile->stage_name }}</a>
        @endif
    </div>

    <div class="wave"><canvas data-peaks='@json($sample->peaks())'></canvas></div>

    <span class="track-cat">{{ $sample->category?->name ?? '—' }}</span>

    <span class="track-meta">
        {{ $sample->bpm ? $sample->bpm.' BPM' : $sample->typeLabel() }}
        @if ($sample->musical_key)<br>{{ $sample->musical_key }}@endif
    </span>

    <span class="track-meta track-duration">{{ $sample->duration() }}</span>

    <div class="track-buy">
        @auth
            <form method="POST" action="{{ route('favorites.toggle', $sample) }}" data-ajax-fav data-login="{{ route('login') }}">
                @csrf
                <button type="submit" @class(['icon-btn', 'is-on' => $isFavorite]) aria-label="Favori"><x-icon name="heart" size="16" /></button>
            </form>
        @endauth

        @if ($isOwned)
            <a href="{{ route('account.library') }}" class="badge badge-ok">Acheté</a>
        @elseif ($isOwn)
            <span class="badge badge-muted">Votre son</span>
        @else
            <form method="POST" action="{{ route('cart.add', ['type' => 'sample', 'id' => $sample->id]) }}" data-ajax-cart>
                @csrf
                <button type="submit" class="btn btn-sm" data-added="✓ {{ $sample->price() }}">{{ $sample->price() }}</button>
            </form>
        @endif
    </div>
</div>
