@extends('layouts.app')

@section('title', 'Mot de passe oublié')
@section('main-class', 'auth')

@section('content')
    <div class="auth-box panel panel-glow">
        <p class="eyebrow">Récupération</p>
        <h1>Mot de passe oublié</h1>
        <p class="muted" style="margin-bottom: 22px">Recevez un lien pour choisir un nouveau mot de passe.</p>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px">Envoyer le lien</button>
        </form>
        <p class="small muted" style="margin-top: 18px; text-align: center"><a href="{{ route('login') }}">← Connexion</a></p>
    </div>
@endsection
