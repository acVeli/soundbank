@extends('layouts.base')

@section('body')
    <header class="topbar">
        <div class="wrap topbar-inner">
            <a href="{{ route('home') }}" class="logo"><span class="logo-mark"></span>{{ strtoupper(config('app.name')) }}</a>

            <nav class="nav" id="main-nav">
                <a href="{{ route('explore') }}" @class(['is-active' => request()->routeIs('explore*', 'samples.*')])>Explorer</a>
                <a href="{{ route('packs.index') }}" @class(['is-active' => request()->routeIs('packs.*')])>Packs</a>
                <a href="{{ route('artists.index') }}" @class(['is-active' => request()->routeIs('artists.*')])>Artistes</a>
                @guest
                    <a href="{{ route('register.artist') }}">Vendre mes sons</a>
                @endguest
            </nav>

            <form class="topbar-search" action="{{ route('explore') }}" method="GET" role="search">
                <x-icon name="search" size="16" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Kick, voix féminine, 140 bpm…" aria-label="Rechercher un son">
            </form>

            <div class="topbar-actions">
                <a href="{{ route('cart') }}" class="icon-btn cart-link" aria-label="Panier">
                    <x-icon name="cart" />
                    <span class="cart-count" data-count="{{ $cartCount ?? 0 }}">{{ $cartCount ?? 0 }}</span>
                </a>

                @auth
                    <details class="menu">
                        <summary class="avatar" aria-label="Mon compte">
                            @if (auth()->user()->artistProfile?->avatarUrl())
                                <img src="{{ auth()->user()->artistProfile->avatarUrl() }}" alt="">
                            @else
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->displayName(), 0, 2)) }}
                            @endif
                        </summary>
                        <div class="menu-panel">
                            <p class="menu-label">{{ auth()->user()->displayName() }}</p>
                            @if (auth()->user()->isArtist())
                                <a href="{{ route('studio.dashboard') }}"><x-icon name="wave" size="16" /> Mon studio</a>
                                <a href="{{ route('studio.samples.create') }}"><x-icon name="upload" size="16" /> Importer des sons</a>
                            @endif
                            <a href="{{ route('account.library') }}"><x-icon name="download" size="16" /> Ma bibliothèque</a>
                            <a href="{{ route('account.orders') }}"><x-icon name="receipt" size="16" /> Commandes & factures</a>
                            <a href="{{ route('account.favorites') }}"><x-icon name="heart" size="16" /> Favoris</a>
                            <a href="{{ route('account.profile') }}"><x-icon name="user" size="16" /> Mon compte</a>
                            @if (auth()->user()->is_admin)
                                <a href="{{ route('admin.dashboard') }}"><x-icon name="shield" size="16" /> Administration</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"><x-icon name="logout" size="16" /> Déconnexion</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-ghost">Connexion</a>
                    <a href="{{ route('register') }}" class="btn btn-sm">Inscription</a>
                @endauth

                <button type="button" class="icon-btn burger" data-toggle="#main-nav" aria-label="Menu"><x-icon name="menu" /></button>
            </div>
        </div>
    </header>

    @yield('hero')

    <main class="@yield('main-class', 'wrap page')">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="footer">
        <div class="wrap footer-inner">
            <div style="max-width: 340px">
                <a href="{{ route('home') }}" class="logo"><span class="logo-mark"></span>{{ strtoupper(config('app.name')) }}</a>
                <p class="small" style="margin-top: 12px">Des sons créés par des artistes, pour des artistes. Chaque achat rémunère directement son créateur.</p>
            </div>
            <div>
                <p class="eyebrow">Acheter</p>
                <ul>
                    <li><a href="{{ route('explore') }}">Explorer les sons</a></li>
                    <li><a href="{{ route('packs.index') }}">Packs</a></li>
                    <li><a href="{{ route('artists.index') }}">Artistes</a></li>
                </ul>
            </div>
            <div>
                <p class="eyebrow">Vendre</p>
                <ul>
                    <li><a href="{{ route('register.artist') }}">Devenir artiste</a></li>
                    <li><a href="{{ route('studio.dashboard') }}">Mon studio</a></li>
                </ul>
            </div>
            <div>
                <p class="eyebrow">Licence</p>
                <ul>
                    <li class="muted">Libre de droits</li>
                    <li class="muted">Usage commercial inclus</li>
                    <li class="muted">Revente des sons seuls interdite</li>
                </ul>
            </div>
        </div>
        <p class="wrap small dim" style="margin-top: 30px">© {{ date('Y') }} {{ config('app.name') }}</p>
    </footer>
@endsection
