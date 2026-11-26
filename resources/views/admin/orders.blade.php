@extends('admin.layout')

@section('title', 'Commandes')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Commandes</h1>
        </div>
    </div>

    <form method="GET" class="row" style="margin-bottom: 18px">
        <input type="search" name="q" value="{{ $search }}" placeholder="N°, client, e-mail, référence" style="max-width: 320px" aria-label="Rechercher">
        <select name="statut" style="max-width: 200px" data-autosubmit aria-label="Statut">
            <option value="paid" @selected($status === 'paid')>Payées</option>
            <option value="pending" @selected($status === 'pending')>En attente</option>
            <option value="failed" @selected($status === 'failed')>Échouées</option>
            <option value="all" @selected($status === 'all')>Toutes</option>
        </select>
        <button class="btn">Filtrer</button>
    </form>

    <div class="panel table-wrap">
        @if ($orders->isEmpty())
            <p class="muted">Aucune commande.</p>
        @else
            <table class="table">
                <thead><tr><th>Commande</th><th>Client</th><th>Créée</th><th>Statut</th><th>Paiement</th><th>Articles</th><th class="num">Total</th><th>Facture</th></tr></thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="mono">{{ $order->number }}</a></td>
                            <td>{{ $order->billing_name ?? $order->user?->name }}<div class="small dim">{{ $order->billing_email ?? $order->user?->email }}</div></td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td><span @class(['badge', 'badge-ok' => $order->isPaid(), 'badge-warn' => $order->status === 'failed', 'badge-muted' => $order->status === 'pending'])>{{ $order->statusLabel() }}</span></td>
                            <td class="muted">{{ $order->providerLabel() }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td class="num">{{ $order->total() }}</td>
                            <td>@if ($order->invoice)<a href="{{ route('admin.invoices.show', $order->invoice) }}" class="mono small">{{ $order->invoice->number }}</a>@else<span class="dim">—</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    {{ $orders->links() }}
@endsection
