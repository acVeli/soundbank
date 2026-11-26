@extends('studio.layout')

@section('title', 'Mes sons')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Studio · Catalogue</p>
            <h1>Mes sons</h1>
        </div>
        <a href="{{ route('studio.samples.create') }}" class="btn btn-primary"><x-icon name="upload" size="14" /> Importer</a>
    </div>

    <form method="GET" class="row" style="margin-bottom: 18px">
        <input type="search" name="q" value="{{ $search }}" placeholder="Titre" style="max-width: 260px" aria-label="Rechercher">
        <select name="statut" style="max-width: 180px" data-autosubmit aria-label="Statut">
            <option value="">Tous les statuts</option>
            <option value="published" @selected($status === 'published')>Publiés</option>
            <option value="draft" @selected($status === 'draft')>Brouillons</option>
            <option value="blocked" @selected($status === 'blocked')>Bloqués</option>
        </select>
        <select name="pack" style="max-width: 240px" data-autosubmit aria-label="Pack">
            <option value="">Tous les packs</option>
            <option value="-1" @selected($packId === -1)>Sans pack</option>
            @foreach ($packs as $pack)
                <option value="{{ $pack->id }}" @selected($packId === $pack->id)>{{ $pack->title }}</option>
            @endforeach
        </select>
        <button class="btn">Filtrer</button>
    </form>

    @if ($samples->isEmpty())
        <x-empty title="Aucun son">
            <p style="margin-bottom: 14px">Importez vos premiers fichiers pour remplir votre catalogue.</p>
            <a href="{{ route('studio.samples.create') }}" class="btn btn-primary">Importer</a>
        </x-empty>
    @else
        <form method="POST" action="{{ route('studio.samples.bulk') }}" id="bulk-form" data-confirm-delete>
            @csrf
            <div class="panel table-wrap">
                <table class="table" id="bulk-scope">
                    <thead>
                        <tr>
                            <th style="width: 32px"><label class="check"><input type="checkbox" data-select-all="#bulk-scope" aria-label="Tout sélectionner"></label></th>
                            <th style="width: 44px"></th>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Pack</th>
                            <th>Infos</th>
                            <th class="num">Prix</th>
                            <th class="num">Écoutes</th>
                            <th class="num">Ventes</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($samples as $sample)
                            <tr data-track='@json($sample->playerPayload())' data-track-id="{{ $sample->id }}">
                                <td><label class="check"><input type="checkbox" name="ids[]" value="{{ $sample->id }}" aria-label="Sélectionner"></label></td>
                                <td>
                                    <button type="button" class="play" data-play style="width: 32px; height: 32px" @disabled(! $sample->preview_path) aria-label="Écouter">
                                        <x-icon name="play" class="i-play" size="12" />
                                        <x-icon name="pause" class="i-pause" size="12" />
                                    </button>
                                </td>
                                <td><a href="{{ route('studio.samples.edit', $sample) }}">{{ $sample->title }}</a><div class="small dim mono">{{ $sample->file_name }}</div></td>
                                <td class="muted">{{ $sample->category?->name ?? '—' }}</td>
                                <td class="muted">{{ $sample->pack?->title ?? '—' }}</td>
                                <td class="mono small muted">{{ $sample->typeLabel() }}{{ $sample->bpm ? ' · '.$sample->bpm : '' }}{{ $sample->musical_key ? ' · '.$sample->musical_key : '' }}</td>
                                <td class="num">{{ $sample->price() }}</td>
                                <td class="num">{{ $sample->plays_count }}</td>
                                <td class="num">{{ $sample->sales_count }}</td>
                                <td>
                                    @if ($sample->status === 'published')
                                        <span class="badge badge-ok"><span class="dot"></span> Publié</span>
                                    @elseif ($sample->status === 'blocked')
                                        <span class="badge badge-warn">Bloqué</span>
                                    @else
                                        <span class="badge badge-muted">Brouillon</span>
                                    @endif
                                    @unless ($sample->preview_path)<span class="badge badge-warn" title="Sans aperçu, le son ne peut pas être écouté">Sans aperçu</span>@endunless
                                </td>
                                <td class="right"><a href="{{ route('studio.samples.edit', $sample) }}" class="icon-btn" aria-label="Modifier"><x-icon name="edit" size="16" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sticky-actions">
                <span class="mono small muted"><span data-selected-count>0</span> sélectionné(s)</span>
                <div class="row">
                    <select name="pack_id" style="max-width: 220px" aria-label="Pack de destination">
                        <option value="">— Retirer du pack —</option>
                        @foreach ($packs as $pack)
                            <option value="{{ $pack->id }}">{{ $pack->title }}</option>
                        @endforeach
                    </select>
                    <button type="submit" name="action" value="move" class="btn btn-sm btn-ghost">Déplacer</button>
                    <button type="submit" name="action" value="publish" class="btn btn-sm">Publier</button>
                    <button type="submit" name="action" value="unpublish" class="btn btn-sm btn-ghost">Dépublier</button>
                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer les sons sélectionnés ? Les sons déjà vendus restent téléchargeables par leurs acheteurs.')">Supprimer</button>
                </div>
            </div>
        </form>
        {{ $samples->links() }}
    @endif
@endsection
