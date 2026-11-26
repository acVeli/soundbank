@extends('layouts.app')

@section('title', $category ? $category->fullName() : 'Explorer')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">
                <a href="{{ route('explore') }}">Catalogue</a>
                @if ($category?->parent) / <a href="{{ route('explore.category', $category->parent) }}">{{ $category->parent->name }}</a>@endif
            </p>
            <h1>{{ $category?->name ?? 'Tous les sons' }}</h1>
            @if ($category?->description)
                <p class="lede">{{ $category->description }}</p>
            @endif
        </div>
        <p class="mono muted">{{ number_format($samples->total(), 0, ',', ' ') }} résultat(s)</p>
    </div>

    <div class="explore">
        <aside class="sidebar-tree">
            <ul class="tree">
                <li><a href="{{ route('explore', request()->except('page')) }}" @class(['is-active' => ! $category])>Tout le catalogue</a></li>
                @foreach ($tree as $family)
                    <li class="tree-family">
                        <a href="{{ route('explore.category', [$family, ...request()->except('page')]) }}" @class(['is-active' => $category?->id === $family->id])>{{ $family->name }}</a>
                        <ul class="tree tree-children">
                            @foreach ($family->children as $child)
                                <li><a href="{{ route('explore.category', [$child, ...request()->except('page')]) }}" @class(['is-active' => $category?->id === $child->id])>{{ $child->name }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </aside>

        <div>
            <div class="chips mobile-only" style="margin-bottom: 16px">
                <a href="{{ route('explore', request()->except('page')) }}" @class(['chip', 'is-active' => ! $category])>Tout</a>
                @foreach ($tree as $family)
                    <a href="{{ route('explore.category', [$family, ...request()->except('page')]) }}" @class(['chip', 'is-active' => $category && in_array($category->id, [$family->id, ...$family->children->pluck('id')->all()], true)])>{{ $family->name }}</a>
                @endforeach
            </div>

            <form method="GET" class="filters">
                <div class="field">
                    <label for="q">Recherche</label>
                    <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Titre, tag, artiste…">
                </div>
                <div class="field">
                    <label for="type">Type</label>
                    <select id="type" name="type" data-autosubmit>
                        <option value="">Tous</option>
                        @foreach (config('soundbank.types') as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="key">Tonalité</label>
                    <select id="key" name="key" data-autosubmit>
                        <option value="">Toutes</option>
                        @foreach (config('soundbank.keys') as $key)
                            <option value="{{ $key }}" @selected(($filters['key'] ?? '') === $key)>{{ $key }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>BPM</label>
                    <div class="row" style="gap: 6px; flex-wrap: nowrap">
                        <input type="number" name="bpm_min" min="20" max="400" value="{{ $filters['bpm_min'] ?? '' }}" placeholder="min" aria-label="BPM minimum">
                        <input type="number" name="bpm_max" min="20" max="400" value="{{ $filters['bpm_max'] ?? '' }}" placeholder="max" aria-label="BPM maximum">
                    </div>
                </div>
                <div class="field">
                    <label for="sort">Tri</label>
                    <select id="sort" name="sort" data-autosubmit>
                        <option value="recent" @selected(($filters['sort'] ?? 'recent') === 'recent')>Plus récents</option>
                        <option value="popular" @selected(($filters['sort'] ?? '') === 'popular')>Populaires</option>
                        <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Prix croissant</option>
                        <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Prix décroissant</option>
                    </select>
                </div>
                <button type="submit" class="btn">Filtrer</button>
            </form>

            @if ($category && $category->children->isNotEmpty())
                <div class="chips" style="margin-bottom: 18px">
                    @foreach ($category->children as $child)
                        <a href="{{ route('explore.category', [$child, ...request()->except('page')]) }}" class="chip">{{ $child->name }}</a>
                    @endforeach
                </div>
            @endif

            <x-tracklist :samples="$samples" :owned="$owned" :favorites="$favorites" empty="Aucun son ne correspond">
                <p>Élargissez la recherche ou changez de catégorie.</p>
            </x-tracklist>

            {{ $samples->links() }}
        </div>
    </div>
@endsection
