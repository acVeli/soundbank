@extends('admin.layout')

@section('title', 'Modération')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Modération du catalogue</h1>
            <p class="lede">Bloquer un son le retire immédiatement de la vente. Les acheteurs existants le gardent.</p>
        </div>
    </div>

    <form method="GET" class="row" style="margin-bottom: 18px">
        <input type="search" name="q" value="{{ $search }}" placeholder="Titre ou artiste" style="max-width: 300px" aria-label="Rechercher">
        <select name="statut" style="max-width: 200px" data-autosubmit aria-label="Statut">
            <option value="">Tous</option>
            <option value="published" @selected($status === 'published')>Publiés</option>
            <option value="draft" @selected($status === 'draft')>Brouillons</option>
            <option value="blocked" @selected($status === 'blocked')>Bloqués</option>
        </select>
        <button class="btn">Filtrer</button>
    </form>

    <div class="panel table-wrap">
        @if ($samples->isEmpty())
            <p class="muted">Aucun son.</p>
        @else
            <table class="table">
                <thead><tr><th style="width: 44px"></th><th>Titre</th><th>Artiste</th><th>Catégorie</th><th>Fichier</th><th class="num">Prix</th><th class="num">Ventes</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    @foreach ($samples as $sample)
                        <tr data-track='@json($sample->playerPayload())' data-track-id="{{ $sample->id }}">
                            <td>
                                <button type="button" class="play" data-play style="width: 32px; height: 32px" @disabled(! $sample->preview_path) aria-label="Écouter">
                                    <x-icon name="play" class="i-play" size="12" />
                                    <x-icon name="pause" class="i-pause" size="12" />
                                </button>
                            </td>
                            <td><a href="{{ route('samples.show', $sample) }}">{{ $sample->title }}</a><div class="small dim">{{ $sample->created_at->format('d/m/Y') }}</div></td>
                            <td>{{ $sample->artist?->displayName() }}</td>
                            <td class="muted">{{ $sample->category?->fullName() ?? '—' }}</td>
                            <td class="mono small muted">{{ strtoupper($sample->file_format) }} · {{ $sample->fileSize() }}</td>
                            <td class="num">{{ $sample->price() }}</td>
                            <td class="num">{{ $sample->sales_count }}</td>
                            <td>
                                <span @class(['badge', 'badge-ok' => $sample->status === 'published', 'badge-warn' => $sample->status === 'blocked', 'badge-muted' => $sample->status === 'draft'])>
                                    {{ ['published' => 'Publié', 'blocked' => 'Bloqué', 'draft' => 'Brouillon'][$sample->status] ?? $sample->status }}
                                </span>
                            </td>
                            <td class="right">
                                <form method="POST" action="{{ route('admin.samples.status', $sample) }}" data-confirm="{{ $sample->status === 'blocked' ? 'Débloquer ce son ?' : 'Bloquer ce son et le retirer de la vente ?' }}">
                                    @csrf
                                    @method('PATCH')
                                    <button @class(['btn btn-sm', 'btn-danger' => $sample->status !== 'blocked'])>{{ $sample->status === 'blocked' ? 'Débloquer' : 'Bloquer' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    {{ $samples->links() }}
@endsection
