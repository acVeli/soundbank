@extends('layouts.shell')

@section('shell-name', 'Administration')

@section('nav')
    @php($pendingPayouts = \App\Models\Payout::query()->where('status', 'requested')->count())
    <nav class="side-nav">
        <a href="{{ route('admin.dashboard') }}" @class(['is-active' => request()->routeIs('admin.dashboard')])><x-icon name="chart" /> Vue d’ensemble</a>
        <a href="{{ route('admin.orders') }}" @class(['is-active' => request()->routeIs('admin.orders*')])><x-icon name="cart" /> Commandes</a>
        <a href="{{ route('admin.invoices') }}" @class(['is-active' => request()->routeIs('admin.invoices*')])><x-icon name="receipt" /> Factures</a>
        <a href="{{ route('admin.payouts') }}" @class(['is-active' => request()->routeIs('admin.payouts')])><x-icon name="coins" /> Versements @if ($pendingPayouts)<span class="count">{{ $pendingPayouts }}</span>@endif</a>
        <a href="{{ route('admin.artists') }}" @class(['is-active' => request()->routeIs('admin.artists')])><x-icon name="users" /> Artistes</a>
        <a href="{{ route('admin.samples') }}" @class(['is-active' => request()->routeIs('admin.samples')])><x-icon name="wave" /> Modération</a>
        <a href="{{ route('admin.categories') }}" @class(['is-active' => request()->routeIs('admin.categories')])><x-icon name="tag" /> Catégories</a>
        <a href="{{ route('admin.settings') }}" @class(['is-active' => request()->routeIs('admin.settings')])><x-icon name="settings" /> Paiements & réglages</a>
    </nav>
@endsection
