<nav class="subnav" aria-label="Mon compte">
    <a href="{{ route('account.dashboard') }}" @class(['is-active' => request()->routeIs('account.dashboard')])>Vue d’ensemble</a>
    <a href="{{ route('account.library') }}" @class(['is-active' => request()->routeIs('account.library*')])>Bibliothèque</a>
    <a href="{{ route('account.orders') }}" @class(['is-active' => request()->routeIs('account.orders*')])>Commandes & factures</a>
    <a href="{{ route('account.favorites') }}" @class(['is-active' => request()->routeIs('account.favorites')])>Favoris</a>
    <a href="{{ route('account.profile') }}" @class(['is-active' => request()->routeIs('account.profile')])>Profil</a>
    @if (auth()->user()->isArtist())
        <a href="{{ route('studio.dashboard') }}">Studio →</a>
    @else
        <a href="{{ route('account.become-artist') }}" @class(['is-active' => request()->routeIs('account.become-artist')])>Vendre mes sons</a>
    @endif
</nav>
