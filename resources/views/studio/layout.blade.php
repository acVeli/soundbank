@extends('layouts.shell')

@section('shell-name', 'Studio')

@section('nav')
    @php($profile = auth()->user()->artistProfile)
    <a href="{{ route('artists.show', $profile) }}" class="row" style="padding: 0 8px; gap: 12px; color: var(--text)">
        <x-avatar :profile="$profile" size="40" />
        <span style="min-width: 0">
            <strong style="display: block; color: var(--ice)">{{ $profile->stage_name }}</strong>
            <span class="small muted">Voir ma page publique</span>
        </span>
    </a>

    <a href="{{ route('studio.samples.create') }}" class="btn btn-primary btn-block"><x-icon name="upload" size="16" /> Importer des sons</a>

    <nav class="side-nav">
        <a href="{{ route('studio.dashboard') }}" @class(['is-active' => request()->routeIs('studio.dashboard')])><x-icon name="chart" /> Tableau de bord</a>
        <a href="{{ route('studio.samples.index') }}" @class(['is-active' => request()->routeIs('studio.samples.index', 'studio.samples.edit')])><x-icon name="wave" /> Mes sons</a>
        <a href="{{ route('studio.packs.index') }}" @class(['is-active' => request()->routeIs('studio.packs.*')])><x-icon name="folder" /> Catalogue & packs</a>
        <a href="{{ route('studio.sales') }}" @class(['is-active' => request()->routeIs('studio.sales')])><x-icon name="receipt" /> Ventes</a>
        <a href="{{ route('studio.payouts') }}" @class(['is-active' => request()->routeIs('studio.payouts')])><x-icon name="coins" /> Versements</a>
        <a href="{{ route('studio.profile') }}" @class(['is-active' => request()->routeIs('studio.profile')])><x-icon name="user" /> Profil artiste</a>
        <a href="{{ route('account.library') }}"><x-icon name="download" /> Mes achats</a>
    </nav>
@endsection
