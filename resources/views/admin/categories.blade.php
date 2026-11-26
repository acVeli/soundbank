@extends('admin.layout')

@section('title', 'Catégories')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Arborescence des catégories</h1>
            <p class="lede">Deux niveaux : des familles (Vocals, Drums…) et leurs sous-catégories, seules proposées aux artistes à l’import.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.categories.store') }}" class="panel panel-glow" style="margin-bottom: 24px">
        @csrf
        <p class="panel-title">Nouvelle catégorie</p>
        <div class="fields">
            <div class="field">
                <label for="name">Nom</label>
                <input id="name" name="name" required maxlength="80" placeholder="Vocal Loops">
            </div>
            <div class="field">
                <label for="parent_id">Famille</label>
                <select id="parent_id" name="parent_id">
                    <option value="">— C’est une famille —</option>
                    @foreach ($families as $family)
                        <option value="{{ $family->id }}">{{ $family->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="description">Description <span>facultatif</span></label>
                <input id="description" name="description" maxlength="255">
            </div>
            <div class="field">
                <label for="position">Ordre</label>
                <input id="position" name="position" type="number" min="0" max="999" value="0">
            </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary"><x-icon name="plus" size="14" /> Ajouter</button></div>
    </form>

    <div class="grid-2">
        @foreach ($families as $family)
            <div class="panel">
                @include('admin.partials.category-row', ['category' => $family, 'families' => $families, 'isFamily' => true])
                <div style="margin-top: 10px; padding-left: 14px; border-left: 1px solid var(--line)">
                    @forelse ($family->children as $child)
                        @include('admin.partials.category-row', ['category' => $child, 'families' => $families, 'isFamily' => false])
                    @empty
                        <p class="small dim">Aucune sous-catégorie.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@endsection
