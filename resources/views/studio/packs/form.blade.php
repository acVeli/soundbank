@extends('studio.layout')

@section('title', $pack->exists ? $pack->title : 'Nouveau pack')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow"><a href="{{ route('studio.packs.index') }}">Catalogue</a></p>
            <h1>{{ $pack->exists ? $pack->title : 'Nouveau pack' }}</h1>
        </div>
        @if ($pack->exists)
            <div class="row">
                @if ($pack->isPublished())
                    <a href="{{ route('packs.show', $pack) }}" class="btn btn-ghost">Voir la page publique</a>
                @endif
                <a href="{{ route('studio.samples.create', ['pack' => $pack->id]) }}" class="btn btn-primary"><x-icon name="upload" size="14" /> Ajouter des sons</a>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ $pack->exists ? route('studio.packs.update', $pack) : route('studio.packs.store') }}" enctype="multipart/form-data" class="detail">
        @csrf
        @if ($pack->exists)
            @method('PUT')
        @endif

        <div class="panel">
            <div class="field">
                <label for="title">Titre du pack</label>
                <input id="title" name="title" value="{{ old('title', $pack->title) }}" required maxlength="120" placeholder="Midnight Vox Vol. 1">
            </div>
            <div class="fields" style="margin-top: 16px">
                <div class="field">
                    <label for="genre">Genre</label>
                    <select id="genre" name="genre">
                        <option value="">—</option>
                        @foreach (config('soundbank.genres') as $genre)
                            <option value="{{ $genre }}" @selected(old('genre', $pack->genre) === $genre)>{{ $genre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="price">Prix du pack (€)</label>
                    <input id="price" name="price" type="number" step="0.01" min="{{ config('soundbank.min_price_cents') / 100 }}" required value="{{ old('price', \App\Support\Money::input($pack->price_cents)) }}">
                </div>
            </div>
            <div class="field" style="margin-top: 16px">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="6" maxlength="3000" placeholder="Contenu, matériel, inspiration…">{{ old('description', $pack->description) }}</textarea>
            </div>

            @if ($pack->exists)
                <hr class="divider">
                <label class="check">
                    <input type="checkbox" name="publish" value="1" @checked(old('publish', $pack->isPublished()))>
                    <span>Publier le pack (au moins un de ses sons doit être publié)</span>
                </label>
            @else
                <p class="hint" style="margin-top: 16px">Le pack est créé en brouillon ; vous le publierez une fois ses sons importés.</p>
            @endif

            <div class="form-actions">
                <button class="btn btn-primary">{{ $pack->exists ? 'Enregistrer' : 'Créer et importer des sons' }}</button>
                <a href="{{ route('studio.packs.index') }}" class="btn btn-ghost">Annuler</a>
            </div>
        </div>

        <aside class="panel">
            <p class="panel-title">Pochette</p>
            <x-cover :pack="$pack" />
            <div class="field" style="margin-top: 14px">
                <label for="cover">Image <span>carrée, 300 px min.</span></label>
                <input id="cover" type="file" name="cover" accept="image/*">
            </div>
        </aside>
    </form>

    @if ($pack->exists)
        <section class="section">
            <div class="section-head"><h2>Sons du pack ({{ $samples->count() }})</h2></div>
            @if ($samples->isEmpty())
                <x-empty title="Pack vide">
                    <a href="{{ route('studio.samples.create', ['pack' => $pack->id]) }}" class="btn">Importer des sons</a>
                </x-empty>
            @else
                <div class="panel table-wrap">
                    <table class="table">
                        <thead><tr><th>Titre</th><th>Catégorie</th><th class="num">Prix unitaire</th><th>Statut</th></tr></thead>
                        <tbody>
                            @foreach ($samples as $sample)
                                <tr>
                                    <td><a href="{{ route('studio.samples.edit', $sample) }}">{{ $sample->title }}</a></td>
                                    <td class="muted">{{ $sample->category?->name ?? '—' }}</td>
                                    <td class="num">{{ $sample->price() }}</td>
                                    <td>
                                        @if ($sample->isPublished())
                                            <span class="badge badge-ok">Publié</span>
                                        @else
                                            <span class="badge badge-muted">Brouillon</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><td colspan="2">Total à l’unité (sons publiés)</td><td class="num">{{ \App\Support\Money::format((int) $samples->where('status', 'published')->sum('price_cents')) }}</td><td></td></tr></tfoot>
                    </table>
                </div>
            @endif
        </section>

        <form method="POST" action="{{ route('studio.packs.destroy', $pack) }}" style="margin-top: 24px" data-confirm="Supprimer ce pack ? Ses sons restent dans votre catalogue.">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger btn-sm"><x-icon name="trash" size="14" /> Supprimer le pack</button>
        </form>
    @endif
@endsection
