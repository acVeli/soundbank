@extends('studio.layout')

@section('title', $sample->title)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow"><a href="{{ route('studio.samples.index') }}">Mes sons</a></p>
            <h1>{{ $sample->title }}</h1>
            <p class="muted mono small">{{ $sample->file_name }} · {{ strtoupper($sample->file_format) }} · {{ $sample->fileSize() }} · {{ $sample->duration() }}</p>
        </div>
        <div class="row">
            @if ($sample->isPublished())
                <a href="{{ route('samples.show', $sample) }}" class="btn btn-ghost">Voir la fiche publique</a>
            @endif
        </div>
    </div>

    @if ($sample->status === 'blocked')
        <div class="flash flash-error">Ce son a été bloqué par la modération et n’est plus en vente.</div>
    @endif

    <div class="panel" data-track='@json($sample->playerPayload())' data-track-id="{{ $sample->id }}" style="margin-bottom: 24px">
        <div class="row" style="gap: 18px; flex-wrap: nowrap">
            <button type="button" class="play play-lg" data-play @disabled(! $sample->preview_path) aria-label="Écouter">
                <x-icon name="play" class="i-play" size="20" />
                <x-icon name="pause" class="i-pause" size="20" />
            </button>
            <div class="wave wave-lg" style="flex: 1; height: 80px"><canvas data-peaks='@json($sample->peaks())'></canvas></div>
        </div>
    </div>

    <form method="POST" action="{{ route('studio.samples.update', $sample) }}" enctype="multipart/form-data" class="panel" data-preview-replacer>
        @csrf
        @method('PUT')

        <div class="fields">
            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="{{ old('title', $sample->title) }}" required maxlength="120">
            </div>
            <div class="field">
                <label for="category_id">Catégorie</label>
                @include('studio.partials.category-select', ['name' => 'category_id', 'id' => 'category_id', 'tree' => $tree, 'selected' => old('category_id', $sample->category_id)])
            </div>
            <div class="field">
                <label for="pack_id">Pack du catalogue</label>
                <select id="pack_id" name="pack_id">
                    <option value="">— Aucun —</option>
                    @foreach ($packs as $pack)
                        <option value="{{ $pack->id }}" @selected((string) old('pack_id', $sample->pack_id) === (string) $pack->id)>{{ $pack->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="type">Type</label>
                <select id="type" name="type">
                    @foreach (config('soundbank.types') as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $sample->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="bpm">BPM</label>
                <input id="bpm" name="bpm" type="number" min="20" max="400" value="{{ old('bpm', $sample->bpm) }}">
            </div>
            <div class="field">
                <label for="musical_key">Tonalité</label>
                <select id="musical_key" name="musical_key">
                    <option value="">—</option>
                    @foreach (config('soundbank.keys') as $key)
                        <option value="{{ $key }}" @selected(old('musical_key', $sample->musical_key) === $key)>{{ $key }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="price">Prix (€)</label>
                <input id="price" name="price" type="number" step="0.01" min="{{ config('soundbank.min_price_cents') / 100 }}" required value="{{ old('price', \App\Support\Money::input($sample->price_cents)) }}">
            </div>
            <div class="field">
                <label for="tags">Tags</label>
                <input id="tags" name="tags" value="{{ old('tags', $sample->tags) }}" maxlength="255" placeholder="dark, airy, trap">
            </div>
        </div>

        <hr class="divider">

        <p class="panel-title">Aperçu public</p>
        <p class="hint" style="margin-bottom: 10px">Choisissez un fichier audio : l’aperçu (22 kHz mono, 30 s max.) et la forme d’onde sont recalculés dans votre navigateur.</p>
        <input type="file" accept="audio/*" data-source aria-label="Fichier source de l’aperçu">
        <input type="file" name="preview" hidden>
        <input type="hidden" name="waveform">
        <p class="small muted" data-replace-status style="margin-top: 6px"></p>

        <hr class="divider">

        <label class="check">
            <input type="checkbox" name="publish" value="1" @checked(old('publish', $sample->isPublished())) @disabled($sample->status === 'blocked')>
            <span>Publié — visible et achetable dans le catalogue</span>
        </label>

        <div class="form-actions">
            <button class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('studio.samples.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>

    <form method="POST" action="{{ route('studio.samples.destroy', $sample) }}" style="margin-top: 20px"
          data-confirm="{{ $sold > 0 ? 'Ce son a été acheté '.$sold.' fois : il sera retiré de la vente mais restera téléchargeable par ses acheteurs. Continuer ?' : 'Supprimer définitivement ce son ?' }}">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger btn-sm"><x-icon name="trash" size="14" /> Supprimer ce son</button>
    </form>
@endsection
