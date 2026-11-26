@extends('studio.layout')

@section('title', 'Ventes')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Studio · Revenus</p>
            <h1>Ventes</h1>
        </div>
    </div>

    <div class="grid-3">
        <x-stat label="Ventes" :value="$totals['count']" />
        <x-stat label="Chiffre d’affaires TTC" :value="\App\Support\Money::format($totals['gross'])" />
        <x-stat label="Vos gains" :value="\App\Support\Money::format($totals['earned'])" hint="Après commission de la plateforme" accent />
    </div>

    <div class="panel table-wrap" style="margin-top: 24px">
        @if ($sales->isEmpty())
            <x-empty title="Aucune vente pour l’instant"><p>Partagez votre page artiste pour lancer les premières ventes.</p></x-empty>
        @else
            <table class="table">
                <thead><tr><th>Date</th><th>Commande</th><th>Article</th><th>Type</th><th class="num">Prix</th><th class="num">Commission</th><th class="num">Gain</th></tr></thead>
                <tbody>
                    @foreach ($sales as $sale)
                        <tr>
                            <td>{{ $sale->order?->paid_at?->format('d/m/Y H:i') }}</td>
                            <td class="mono small">{{ $sale->order?->number }}</td>
                            <td>{{ $sale->title }}</td>
                            <td><span class="badge badge-muted">{{ $sale->typeLabel() }}</span></td>
                            <td class="num">{{ $sale->price() }}</td>
                            <td class="num muted">{{ rtrim(rtrim(number_format($sale->commission_bp / 100, 2, ',', ''), '0'), ',') }} %</td>
                            <td class="num">{{ $sale->earning() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    {{ $sales->links() }}
@endsection
