@extends('layouts.app')

@section('title', 'Commandes & factures')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace acheteur</p>
            <h1>Commandes & factures</h1>
        </div>
    </div>

    @include('account.nav')

    @if ($orders->isEmpty())
        <x-empty title="Aucune commande" />
    @else
        <div class="panel table-wrap">
            <table class="table">
                <thead><tr><th>Commande</th><th>Date</th><th>Articles</th><th>Paiement</th><th class="num">Total</th><th>Facture</th><th></th></tr></thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td class="mono">{{ $order->number }}</td>
                            <td>{{ $order->paid_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td class="muted">{{ $order->providerLabel() }}</td>
                            <td class="num">{{ $order->total() }}</td>
                            <td>
                                @if ($order->invoice)
                                    <a href="{{ $order->invoice->publicUrl() }}" class="mono">{{ $order->invoice->number }}</a>
                                @else
                                    <span class="dim">—</span>
                                @endif
                            </td>
                            <td class="right">
                                <a href="{{ route('account.orders.show', $order) }}">Détail</a> ·
                                <a href="{{ route('account.orders.zip', $order) }}">ZIP</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    @endif
@endsection
