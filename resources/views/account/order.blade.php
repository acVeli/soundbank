@extends('layouts.app')

@section('title', 'Commande '.$order->number)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow"><a href="{{ route('account.orders') }}">Commandes</a></p>
            <h1 class="mono">{{ $order->number }}</h1>
            <p class="muted">Payée le {{ $order->paid_at?->format('d/m/Y à H:i') }} · {{ $order->providerLabel() }} · {{ $order->total() }}</p>
        </div>
        <div class="row">
            @if ($order->invoice)
                <a href="{{ $order->invoice->publicUrl() }}" class="btn btn-ghost"><x-icon name="receipt" size="16" /> Facture {{ $order->invoice->number }}</a>
            @endif
            <a href="{{ route('account.orders.zip', $order) }}" class="btn btn-primary"><x-icon name="download" size="16" /> Tout en ZIP</a>
        </div>
    </div>

    <div class="panel table-wrap" style="margin-bottom: 28px">
        <table class="table">
            <thead><tr><th>Article</th><th>Artiste</th><th>Type</th><th class="num">Prix</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td class="muted">{{ $item->artist_name }}</td>
                        <td><span class="badge">{{ $item->typeLabel() }}</span></td>
                        <td class="num">{{ $item->price() }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="3">Total TTC</td><td class="num">{{ $order->total() }}</td></tr></tfoot>
        </table>
    </div>

    <div class="section-head"><h2>Fichiers ({{ $library->count() }})</h2></div>
    @include('account.library-table', ['items' => $library])
@endsection
