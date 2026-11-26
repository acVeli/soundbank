@extends('layouts.app')

@section('title', 'Ma bibliothèque')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace acheteur</p>
            <h1>Bibliothèque</h1>
        </div>
        <p class="mono muted">{{ $items->total() }} son(s)</p>
    </div>

    @include('account.nav')

    <form method="GET" class="row" style="margin-bottom: 18px">
        <input type="search" name="q" value="{{ $search }}" placeholder="Rechercher dans mes sons" style="max-width: 300px" aria-label="Rechercher">
        <select name="categorie" style="max-width: 240px" data-autosubmit aria-label="Catégorie">
            <option value="">Toutes les catégories</option>
            @foreach ($tree as $family)
                <optgroup label="{{ $family->name }}">
                    <option value="{{ $family->id }}" @selected($categoryId === $family->id)>Toute la famille {{ $family->name }}</option>
                    @foreach ($family->children as $child)
                        <option value="{{ $child->id }}" @selected($categoryId === $child->id)>{{ $child->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <button class="btn">Filtrer</button>
    </form>

    @if ($items->isEmpty())
        <x-empty title="Rien ici pour l’instant">
            <p style="margin-bottom: 14px">Les sons que vous achetez restent téléchargeables à vie.</p>
            <a href="{{ route('explore') }}" class="btn">Explorer</a>
        </x-empty>
    @else
        @include('account.library-table', ['items' => $items])
        {{ $items->links() }}
    @endif
@endsection
