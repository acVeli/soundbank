@extends('layouts.app')

@section('title', 'Samples & voix vendus par les artistes')

@section('hero')
    <section class="hero">
        <div class="hero-floor" aria-hidden="true"></div>
        <div class="hero-horizon" aria-hidden="true"></div>
        <div class="wrap"><div class="hero-content">
            <p class="eyebrow">Banque de sons // créée par les artistes</p>
            <h1>Entrez dans la <em>grille</em> du son.</h1>
            <p class="lede">Voix féminines et masculines, kicks, hats, basses, textures et packs complets — enregistrés par des vocalistes et producteurs, vendus sous licence libre de droits.</p>

            <form class="hero-search" action="{{ route('explore') }}" method="GET" role="search">
                <input type="search" name="q" placeholder="Rechercher : vocal chop, 808, Am, 140…" aria-label="Rechercher">
                <button type="submit" class="btn btn-primary btn-lg"><x-icon name="search" size="16" /> Chercher</button>
            </form>

            <div class="hero-stats">
                <div class="hero-stat"><strong>{{ number_format($stats['samples'], 0, ',', ' ') }}</strong><span>Sons</span></div>
                <div class="hero-stat"><strong>{{ number_format($stats['packs'], 0, ',', ' ') }}</strong><span>Packs</span></div>
                <div class="hero-stat"><strong>{{ number_format($stats['artists'], 0, ',', ' ') }}</strong><span>Artistes</span></div>
            </div>
        </div></div>
    </section>
@endsection

@section('content')
    <section>
        <div class="section-head">
            <h2>Familles de sons</h2>
            <a href="{{ route('explore') }}" class="btn btn-sm btn-ghost">Tout explorer <x-icon name="arrow" size="14" /></a>
        </div>
        <div class="grid-4">
            @foreach ($families as $family)
                <a href="{{ route('explore.category', $family) }}" class="panel family-card">
                    <span class="row between">
                        <strong>{{ $family->name }}</strong>
                        <span class="count">{{ $family->samples_total }}</span>
                    </span>
                    <p>{{ $family->children->pluck('name')->take(4)->implode(' · ') }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Nouveautés</h2>
            <a href="{{ route('explore', ['sort' => 'recent']) }}" class="btn btn-sm btn-ghost">Voir tout</a>
        </div>
        <x-tracklist :samples="$latest" :owned="$owned" empty="Le catalogue s’allume bientôt.">
            <p>Les premiers artistes sont en train d’importer leurs sons.</p>
        </x-tracklist>
    </section>

    @if ($packs->isNotEmpty())
        <section class="section">
            <div class="section-head">
                <h2>Packs à la une</h2>
                <a href="{{ route('packs.index') }}" class="btn btn-sm btn-ghost">Tous les packs</a>
            </div>
            <div class="grid-4">
                @foreach ($packs as $pack)
                    <x-pack-card :pack="$pack" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($trending->isNotEmpty())
        <section class="section">
            <div class="section-head"><h2>Les plus demandés</h2></div>
            <x-tracklist :samples="$trending" :owned="$owned" />
        </section>
    @endif

    @if ($artists->isNotEmpty())
        <section class="section">
            <div class="section-head">
                <h2>Artistes</h2>
                <a href="{{ route('artists.index') }}" class="btn btn-sm btn-ghost">Tous les artistes</a>
            </div>
            <div class="grid-3">
                @foreach ($artists as $profile)
                    <x-artist-card :profile="$profile" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="section paths">
        <div class="panel panel-glow path-card">
            <p class="eyebrow">Parcours acheteur</p>
            <h2>Trouvez le son exact</h2>
            <ul>
                <li>Filtres par famille, BPM, tonalité, type</li>
                <li>Écoute instantanée, forme d’onde interactive</li>
                <li>Téléchargement WAV haute qualité + licence</li>
                <li>Factures automatiques</li>
            </ul>
            <a href="{{ route('explore') }}" class="btn btn-primary">Explorer le catalogue</a>
        </div>
        <div class="panel panel-glow path-card">
            <p class="eyebrow">Parcours artiste</p>
            <h2>Vendez vos voix et vos sons</h2>
            <ul>
                <li>Import en glisser-déposer, BPM et tonalité détectés</li>
                <li>Aperçus protégés générés automatiquement</li>
                <li>Catalogue organisé en packs</li>
                <li>Suivi des ventes et versements PayPal / virement</li>
            </ul>
            <a href="{{ auth()->user()?->isArtist() ? route('studio.dashboard') : route('register.artist') }}" class="btn">Ouvrir mon studio</a>
        </div>
    </section>
@endsection
