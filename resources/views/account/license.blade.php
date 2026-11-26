@extends('layouts.app')

@section('title', 'Licence '.$item->license_key)

@section('content')
    @php
        $sample = $item->sample;
        $order = $item->orderItem?->order;
    @endphp
    <div class="license">
        <div class="row between no-print" style="margin-bottom: 20px">
            <a href="{{ route('account.library') }}">← Bibliothèque</a>
            <button type="button" class="btn btn-sm" onclick="window.print()">Imprimer / PDF</button>
        </div>

        <article class="panel panel-glow" style="padding: 36px">
            <p class="eyebrow">Certificat de licence</p>
            <h1 style="margin-block: 12px 4px">{{ $sample->title }}</h1>
            <p class="lede">par {{ $sample->artist?->displayName() }}</p>

            <p class="label" style="margin-top: 26px">Clé de licence</p>
            <p class="license-key">{{ $item->license_key }}</p>

            <dl class="specs" style="margin-top: 24px">
                <div><dt>Licencié</dt><dd>{{ $item->user->name }}</dd></div>
                <div><dt>Date d’acquisition</dt><dd>{{ $item->created_at->format('d/m/Y') }}</dd></div>
                <div><dt>Commande</dt><dd class="mono">{{ $order?->number ?? '—' }}</dd></div>
                <div><dt>Fichier</dt><dd>{{ $sample->file_name }}</dd></div>
            </dl>

            <h2 style="margin-top: 30px">Conditions</h2>
            <ul class="muted" style="padding-left: 18px; margin-top: 10px; display: grid; gap: 6px">
                <li>Licence non exclusive, mondiale et perpétuelle, accordée à la personne ci-dessus.</li>
                <li>Le son peut être intégré, transformé et exploité dans des œuvres musicales ou audiovisuelles, y compris commerciales (streaming, ventes, synchronisation, diffusion).</li>
                <li>Aucune redevance supplémentaire n’est due à l’artiste ni à {{ config('app.name') }}.</li>
                <li>Interdit : revendre, redistribuer, partager ou intégrer le son seul ou à l’identique dans une autre banque de sons, un pack ou un instrument virtuel.</li>
                <li>Interdit : enregistrer le son seul auprès d’un service d’identification de contenu (Content ID…).</li>
            </ul>
        </article>
    </div>
@endsection
