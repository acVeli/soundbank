@extends('studio.layout')

@section('title', 'Importer des sons')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Studio · Import</p>
            <h1>Importer des sons</h1>
            <p class="lede">Glissez vos fichiers : titre, BPM, tonalité et catégorie sont devinés depuis le nom. L’aperçu public (22 kHz mono, 30 s max.) est fabriqué dans votre navigateur — l’original n’est jamais exposé.</p>
        </div>
    </div>

    <div data-uploader
         data-store-url="{{ route('studio.samples.store') }}"
         data-max-files="{{ config('soundbank.max_files_per_upload') }}"
         data-formats="{{ implode(',', config('soundbank.audio_formats')) }}"
         data-preview-seconds="{{ config('soundbank.preview_seconds') }}">

        <div class="panel">
            <p class="panel-title">Réglages par défaut <button type="button" class="btn btn-sm btn-ghost" data-apply-defaults>Appliquer à tous</button></p>
            <div class="fields">
                <div class="field">
                    <label for="default_pack">Pack du catalogue</label>
                    <select id="default_pack" name="default_pack">
                        <option value="">— Aucun (sons à l’unité) —</option>
                        @foreach ($packs as $pack)
                            <option value="{{ $pack->id }}" @selected($selectedPack === $pack->id)>{{ $pack->title }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('studio.packs.create') }}" class="small">+ Créer un pack</a>
                </div>
                <div class="field">
                    <label for="default_category">Catégorie <span>si non devinée</span></label>
                    @include('studio.partials.category-select', ['name' => 'default_category', 'id' => 'default_category', 'tree' => $tree])
                </div>
                <div class="field">
                    <label for="default_type">Type</label>
                    <select id="default_type" name="default_type">
                        @foreach (config('soundbank.types') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="default_price">Prix unitaire (€)</label>
                    <input id="default_price" name="default_price" type="number" step="0.01" min="{{ config('soundbank.min_price_cents') / 100 }}" value="1.99">
                </div>
                <div class="field">
                    <label for="default_tags">Tags <span>séparés par des virgules</span></label>
                    <input id="default_tags" name="default_tags" type="text" placeholder="dark, trap, airy">
                </div>
            </div>
            <label class="check" style="margin-top: 16px">
                <input type="checkbox" name="default_publish" value="1" checked>
                <span>Publier immédiatement (sinon : brouillons à relire)</span>
            </label>
        </div>

        <div class="dropzone" data-dropzone style="margin-top: 20px">
            <x-icon name="upload" size="34" style="color: var(--cyan)" />
            <strong>Déposez vos fichiers audio</strong>
            <span class="muted">{{ strtoupper(implode(' · ', config('soundbank.audio_formats'))) }} — jusqu’à {{ config('soundbank.max_files_per_upload') }} fichiers, {{ round(config('soundbank.max_sample_kilobytes') / 1024) }} Mo chacun</span>
            <input type="file" multiple accept=".{{ implode(',.', config('soundbank.audio_formats')) }},audio/*" data-files aria-label="Choisir des fichiers audio">
        </div>

        <div class="upload-list" data-list></div>

        <div class="sticky-actions">
            <span class="mono muted small" data-summary>Aucun fichier sélectionné.</span>
            <div class="row">
                <a href="{{ route('studio.samples.index') }}" class="btn btn-ghost">Mes sons</a>
                <button type="button" class="btn btn-primary" data-start disabled><x-icon name="upload" size="14" /> Tout importer</button>
            </div>
        </div>

        <template data-row>
            <div class="upload-item">
                <button type="button" class="play" data-preview disabled aria-label="Écouter l’aperçu">
                    <x-icon name="play" class="i-play" size="16" />
                    <x-icon name="pause" class="i-pause" size="16" />
                </button>
                <div style="min-width: 0">
                    <div class="row between" style="flex-wrap: nowrap">
                        <span class="mono small" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap"><span data-name></span> · <span class="dim" data-size></span></span>
                        <span class="small muted" data-status></span>
                    </div>
                    <div class="wave" style="height: 34px; margin-top: 6px"><canvas></canvas></div>
                    <div class="upload-fields">
                        <div class="field"><label>Titre</label><input type="text" data-field="title" maxlength="120" required></div>
                        <div class="field"><label>Catégorie</label>@include('studio.partials.category-select', ['name' => '', 'field' => 'category_id', 'tree' => $tree])</div>
                        <div class="field"><label>Pack</label>
                            <select data-field="pack_id">
                                <option value="">— Aucun —</option>
                                @foreach ($packs as $pack)
                                    <option value="{{ $pack->id }}">{{ $pack->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Type</label>
                            <select data-field="type">
                                @foreach (config('soundbank.types') as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>BPM</label><input type="number" data-field="bpm" min="20" max="400"></div>
                        <div class="field"><label>Tonalité</label>
                            <select data-field="musical_key">
                                <option value="">—</option>
                                @foreach (config('soundbank.keys') as $key)
                                    <option value="{{ $key }}">{{ $key }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Prix €</label><input type="number" data-field="price" step="0.01" min="{{ config('soundbank.min_price_cents') / 100 }}"></div>
                        <div class="field" style="grid-column: span 2"><label>Tags</label><input type="text" data-field="tags" maxlength="255"></div>
                    </div>
                    <div class="progress"><span></span></div>
                </div>
                <button type="button" class="icon-btn" data-remove aria-label="Retirer"><x-icon name="x" size="16" /></button>
            </div>
        </template>
    </div>
@endsection
