@extends('layouts.app')

@section('title', 'Mon compte')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace acheteur</p>
            <h1>Salut, {{ \Illuminate\Support\Str::before($user->name, ' ') ?: $user->name }}</h1>
        </div>
        <a href="{{ route('explore') }}" class="btn btn-primary">Trouver des sons</a>
    </div>

    @include('account.nav')

    <div class="grid-4">
        <x-stat label="Sons possédés" :value="$stats['library']" accent />
        <x-stat label="Commandes" :value="$stats['orders']" />
        <x-stat label="Favoris" :value="$stats['favorites']" />
        <x-stat label="Total dépensé" :value="\App\Support\Money::format($stats['spent'])" />
    </div>

    <section class="section">
        <div class="section-head">
            <h2>Derniers ajouts à la bibliothèque</h2>
            <a href="{{ route('account.library') }}" class="btn btn-sm btn-ghost">Toute la bibliothèque</a>
        </div>
        @if ($recent->isEmpty())
            <x-empty title="Bibliothèque vide">
                <p style="margin-bottom: 14px">Vos sons achetés apparaîtront ici, prêts à télécharger.</p>
                <a href="{{ route('explore') }}" class="btn">Explorer</a>
            </x-empty>
        @else
            @include('account.library-table', ['items' => $recent])
        @endif
    </section>

    @if ($orders->isNotEmpty())
        <section class="section">
            <div class="section-head"><h2>Dernières commandes</h2></div>
            <div class="panel table-wrap">
                <table class="table">
                    <thead><tr><th>Commande</th><th>Date</th><th>Articles</th><th class="num">Total</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="mono">{{ $order->number }}</td>
                                <td>{{ $order->paid_at?->format('d/m/Y') }}</td>
                                <td>{{ $order->items_count }}</td>
                                <td class="num">{{ $order->total() }}</td>
                                <td class="right"><a href="{{ route('account.orders.show', $order) }}">Détail →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
