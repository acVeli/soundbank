@extends('admin.layout')

@section('title', 'Administration')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Vue d’ensemble</h1>
        </div>
    </div>

    @if (in_array(false, $checklist, true))
        <div class="panel panel-glow" style="margin-bottom: 24px">
            <p class="panel-title">Mise en service</p>
            <ul style="list-style: none; padding: 0; display: grid; gap: 6px">
                @foreach ($checklist as $label => $done)
                    <li class="row" style="gap: 10px">
                        <span @class(['badge', 'badge-ok' => $done, 'badge-warn' => ! $done])>{{ $done ? 'OK' : 'À faire' }}</span> {{ $label }}
                    </li>
                @endforeach
            </ul>
            <div class="form-actions"><a href="{{ route('admin.settings') }}" class="btn">Ouvrir les réglages</a></div>
        </div>
    @endif

    <div class="grid-4">
        <x-stat label="Ventes totales TTC" :value="\App\Support\Money::format($stats['revenue'])" :hint="$stats['orders'].' commande(s)'" accent />
        <x-stat label="Ce mois-ci" :value="\App\Support\Money::format($stats['month'])" />
        <x-stat label="Commission plateforme" :value="\App\Support\Money::format($stats['commission'])" />
        <x-stat label="Dû aux artistes" :value="\App\Support\Money::format($stats['owed'])" :hint="$stats['payoutRequests'].' demande(s) en attente'" />
    </div>
    <div class="grid-3" style="margin-top: 16px">
        <x-stat label="Artistes" :value="$stats['artists']" />
        <x-stat label="Acheteurs" :value="$stats['buyers']" />
        <x-stat label="Sons publiés" :value="$stats['samples']" />
    </div>

    <div class="panel table-wrap" style="margin-top: 24px">
        <p class="panel-title">Dernières commandes payées <a href="{{ route('admin.orders') }}" class="small">Toutes</a></p>
        @if ($orders->isEmpty())
            <p class="muted">Aucune vente pour l’instant.</p>
        @else
            <table class="table">
                <thead><tr><th>Commande</th><th>Client</th><th>Date</th><th>Articles</th><th>Paiement</th><th class="num">Total</th></tr></thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="mono">{{ $order->number }}</a></td>
                            <td>{{ $order->billing_name }}<div class="small dim">{{ $order->billing_email }}</div></td>
                            <td>{{ $order->paid_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td class="muted">{{ $order->providerLabel() }}</td>
                            <td class="num">{{ $order->total() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
