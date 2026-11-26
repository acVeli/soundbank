@extends('layouts.app')

@section('title', 'Vendre mes sons')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Parcours artiste</p>
            <h1>Ouvrir mon studio</h1>
            <p class="lede">Votre compte reste le même : vous gardez vos achats et gagnez un studio pour vendre vos voix et vos sons.</p>
        </div>
    </div>

    @include('account.nav')

    <form method="POST" action="{{ route('account.become-artist.store') }}" class="panel panel-glow" style="max-width: 560px">
        @csrf
        <div class="field">
            <label for="stage_name">Nom d’artiste <span>public</span></label>
            <input id="stage_name" name="stage_name" value="{{ old('stage_name') }}" required maxlength="80">
        </div>
        <label class="check" style="margin-top: 16px">
            <input type="checkbox" name="terms" value="1" required>
            <span>Je détiens les droits des sons que je publie et j’accepte la commission de la plateforme.</span>
        </label>
        <div class="form-actions"><button class="btn btn-primary">Ouvrir mon studio</button></div>
    </form>
@endsection
