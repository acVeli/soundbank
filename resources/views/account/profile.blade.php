@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace acheteur</p>
            <h1>Profil</h1>
        </div>
    </div>

    @include('account.nav')

    <div class="grid-2">
        <form method="POST" action="{{ route('account.profile.update') }}" class="panel">
            @csrf
            @method('PUT')
            <p class="panel-title">Identité</p>
            <div class="field">
                <label for="name">Nom</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120">
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190">
            </div>
            <div class="form-actions"><button class="btn btn-primary">Enregistrer</button></div>
        </form>

        <form method="POST" action="{{ route('account.password') }}" class="panel">
            @csrf
            @method('PUT')
            <p class="panel-title">Mot de passe</p>
            <div class="field">
                <label for="current_password">Mot de passe actuel</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="field">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirmation">Confirmation</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <div class="form-actions"><button class="btn">Changer le mot de passe</button></div>
        </form>
    </div>
@endsection
