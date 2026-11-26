@extends('admin.layout')

@section('title', 'Commande '.$order->number)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow"><a href="{{ route('admin.orders') }}">Commandes</a></p>
            <h1 class="mono">{{ $order->number }}</h1>
            <p class="muted">
                <span @class(['badge', 'badge-ok' => $order->isPaid(), 'badge-warn' => ! $order->isPaid()])>{{ $order->statusLabel() }}</span>
                {{ $order->providerLabel() }} · créée le {{ $order->created_at->format('d/m/Y H:i') }}
                @if ($order->paid_at) · payée le {{ $order->paid_at->format('d/m/Y H:i') }} @endif
            </p>
        </div>
        @if ($order->invoice)
            <a href="{{ route('admin.invoices.show', $order->invoice) }}" class="btn"><x-icon name="receipt" size="14" /> Facture {{ $order->invoice->number }}</a>
        @endif
    </div>

    <div class="grid-2">
        <div class="panel">
            <p class="panel-title">Client</p>
            <dl class="specs">
                <div><dt>Nom</dt><dd>{{ $order->billing_name ?? '—' }}</dd></div>
                <div><dt>E-mail</dt><dd>{{ $order->billing_email ?? '—' }}</dd></div>
                <div><dt>Société</dt><dd>{{ $order->billing_company ?? '—' }}</dd></div>
                <div><dt>N° TVA</dt><dd>{{ $order->billing_vat ?? '—' }}</dd></div>
            </dl>
            @if ($order->billing_address)
                <p class="muted" style="white-space: pre-line; margin-top: 12px">{{ $order->billing_address }}</p>
            @endif
        </div>
        <div class="panel">
            <p class="panel-title">Paiement</p>
            <dl class="specs">
                <div><dt>Moyen</dt><dd>{{ $order->providerLabel() }}</dd></div>
                <div><dt>Montant</dt><dd>{{ $order->total() }}</dd></div>
                <div style="grid-column: span 2"><dt>Référence {{ $order->provider === 'stripe' ? 'session Stripe' : 'commande PayPal' }}</dt><dd class="mono small">{{ $order->reference ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="panel table-wrap" style="margin-top: 24px">
        <p class="panel-title">Articles et répartition</p>
        <table class="table">
            <thead><tr><th>Article</th><th>Type</th><th>Artiste</th><th class="num">Prix TTC</th><th class="num">Commission</th><th class="num">Part artiste</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td><span class="badge badge-muted">{{ $item->typeLabel() }}</span></td>
                        <td>{{ $item->artist_name }}</td>
                        <td class="num">{{ $item->price() }}</td>
                        <td class="num">{{ \App\Support\Money::format($item->price_cents - $item->artist_earning_cents) }}</td>
                        <td class="num">{{ $item->earning() }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total</td>
                    <td class="num">{{ $order->total() }}</td>
                    <td class="num">{{ \App\Support\Money::format((int) $order->items->sum(fn ($i) => $i->price_cents - $i->artist_earning_cents)) }}</td>
                    <td class="num">{{ \App\Support\Money::format((int) $order->items->sum('artist_earning_cents')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
