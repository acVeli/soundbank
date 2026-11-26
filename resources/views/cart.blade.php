@extends('layouts.app')

@section('title', 'Panier')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Étape 1 / 2</p>
            <h1>Panier</h1>
        </div>
    </div>

    @if ($lines->isEmpty())
        <x-empty title="Votre panier est vide">
            <p style="margin-bottom: 16px">Écoutez, comparez, ajoutez.</p>
            <a href="{{ route('explore') }}" class="btn btn-primary">Explorer le catalogue</a>
        </x-empty>
    @else
        <div class="detail">
            <div class="panel">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Article</th><th>Artiste</th><th>Type</th><th class="num">Prix</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $line)
                                @php($model = $line['model'])
                                <tr>
                                    <td>
                                        <a href="{{ $line['type'] === 'pack' ? route('packs.show', $model) : route('samples.show', $model) }}">{{ $model->title }}</a>
                                        @if ($line['type'] === 'pack')
                                            <div class="small dim">{{ $model->samples_count }} sons inclus</div>
                                        @else
                                            <div class="small dim">{{ $model->category?->name }}{{ $model->bpm ? ' · '.$model->bpm.' BPM' : '' }}</div>
                                        @endif
                                    </td>
                                    <td class="muted">{{ $model->artist?->displayName() }}</td>
                                    <td><span class="badge">{{ $line['type'] === 'pack' ? 'Pack' : $model->typeLabel() }}</span></td>
                                    <td class="num">{{ \App\Support\Money::format($line['price_cents']) }}</td>
                                    <td class="right">
                                        <form method="POST" action="{{ route('cart.remove', ['type' => $line['type'], 'id' => $model->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-btn" aria-label="Retirer"><x-icon name="x" size="16" /></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="panel panel-glow">
                <p class="panel-title">Récapitulatif</p>
                <div class="row between"><span class="muted">{{ $lines->count() }} article(s)</span><span class="price">{{ \App\Support\Money::format($total) }}</span></div>
                <hr class="divider">
                <div class="row between"><span>Total TTC</span><span class="price-lg">{{ \App\Support\Money::format($total) }}</span></div>

                @if (! $paymentsReady)
                    <p class="flash flash-error" style="margin-top: 16px">Le paiement en ligne n’est pas encore ouvert.</p>
                @else
                    <a href="{{ route('checkout') }}" class="btn btn-primary btn-lg btn-block" style="margin-top: 20px">Passer commande <x-icon name="arrow" size="16" /></a>
                @endif
                @guest
                    <p class="hint" style="margin-top: 12px">Vous créerez un compte (ou vous connecterez) à l’étape suivante : vos sons seront rangés dans votre bibliothèque.</p>
                @endguest
            </aside>
        </div>
    @endif
@endsection
