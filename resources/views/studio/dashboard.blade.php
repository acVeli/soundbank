@extends('studio.layout')

@section('title', 'Studio')

@section('content')
    @php($max = max(1, $chart->max('cents')))
    <div class="page-head">
        <div>
            <p class="eyebrow">Studio</p>
            <h1>Tableau de bord</h1>
        </div>
        <div class="row">
            <a href="{{ route('studio.packs.create') }}" class="btn btn-ghost"><x-icon name="plus" size="14" /> Nouveau pack</a>
            <a href="{{ route('studio.samples.create') }}" class="btn btn-primary"><x-icon name="upload" size="14" /> Importer</a>
        </div>
    </div>

    @if ($stats['samples'] === 0)
        <div class="panel panel-glow" style="margin-bottom: 24px">
            <p class="eyebrow">Démarrage</p>
            <h2 style="margin-block: 8px">Trois étapes pour vendre</h2>
            <ol class="muted" style="padding-left: 18px; display: grid; gap: 6px">
                <li><a href="{{ route('studio.profile') }}">Complétez votre profil</a> (photo, bio, coordonnées de versement).</li>
                <li><a href="{{ route('studio.packs.create') }}">Créez un pack</a> pour organiser votre catalogue (facultatif).</li>
                <li><a href="{{ route('studio.samples.create') }}">Importez vos sons</a> : glissez vos WAV, on s’occupe des aperçus.</li>
            </ol>
        </div>
    @endif

    <div class="grid-4">
        <x-stat label="Solde disponible" :value="\App\Support\Money::format($stats['available'])" hint="Versable sur demande" accent />
        <x-stat label="Gains ce mois" :value="\App\Support\Money::format($stats['month'])" />
        <x-stat label="Gains totaux" :value="\App\Support\Money::format($stats['earned'])" :hint="$stats['sales'].' vente(s)'" />
        <x-stat label="Écoutes" :value="number_format($stats['plays'], 0, ',', ' ')" :hint="$stats['published'].' / '.$stats['samples'].' sons publiés · '.$stats['packs'].' pack(s)'" />
    </div>

    <div class="panel" style="margin-top: 24px">
        <p class="panel-title">Gains des 30 derniers jours <span class="mono muted">{{ \App\Support\Money::format($chart->sum('cents')) }}</span></p>
        <div class="bars">
            @foreach ($chart as $day)
                <span style="height: {{ max(1, round($day['cents'] / $max * 100)) }}%" data-label="{{ $day['date'] }} · {{ \App\Support\Money::format($day['cents']) }}"></span>
            @endforeach
        </div>
    </div>

    <div class="grid-2" style="margin-top: 24px">
        <div class="panel">
            <p class="panel-title">Meilleurs sons</p>
            @if ($top->isEmpty())
                <p class="muted">Aucun son pour l’instant.</p>
            @else
                <table class="table">
                    <thead><tr><th>Son</th><th class="num">Écoutes</th><th class="num">Ventes</th></tr></thead>
                    <tbody>
                        @foreach ($top as $sample)
                            <tr>
                                <td><a href="{{ route('studio.samples.edit', $sample) }}">{{ $sample->title }}</a><div class="small dim">{{ $sample->category?->name }}</div></td>
                                <td class="num">{{ $sample->plays_count }}</td>
                                <td class="num">{{ $sample->sales_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="panel">
            <p class="panel-title">Dernières ventes <a href="{{ route('studio.sales') }}" class="small">Tout voir</a></p>
            @if ($recentSales->isEmpty())
                <p class="muted">Vos ventes apparaîtront ici.</p>
            @else
                <table class="table">
                    <thead><tr><th>Article</th><th>Date</th><th class="num">Gain</th></tr></thead>
                    <tbody>
                        @foreach ($recentSales as $sale)
                            <tr>
                                <td>{{ $sale->title }} <span class="badge badge-muted">{{ $sale->typeLabel() }}</span></td>
                                <td class="muted">{{ $sale->order?->paid_at?->format('d/m') }}</td>
                                <td class="num">{{ $sale->earning() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
