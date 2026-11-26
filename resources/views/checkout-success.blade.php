@extends('layouts.app')

@section('title', 'Merci')

@section('content')
    <div class="panel panel-glow" style="max-width: 720px; margin: 40px auto; text-align: center; padding: 48px 28px">
        <p class="eyebrow" style="justify-content: center">Paiement confirmé</p>
        <h1 style="margin-block: 12px">Accès débloqué.</h1>
        <p class="lede" style="margin-inline: auto">Commande <span class="mono">{{ $order->number }}</span> — {{ $order->total() }}. Vos sons sont dans votre bibliothèque{{ $order->invoice ? ' et la facture '.$order->invoice->number.' vous a été envoyée' : '' }}.</p>

        <div class="row" style="justify-content: center; margin-top: 28px">
            <a href="{{ route('account.orders.zip', $order) }}" class="btn btn-primary btn-lg"><x-icon name="download" size="16" /> Tout télécharger (ZIP)</a>
            <a href="{{ route('account.library') }}" class="btn btn-lg">Ma bibliothèque</a>
            @if ($order->invoice)
                <a href="{{ $order->invoice->publicUrl() }}" class="btn btn-ghost btn-lg">Facture</a>
            @endif
        </div>
    </div>
@endsection
