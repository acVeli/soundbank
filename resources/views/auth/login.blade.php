@extends('layouts.app')

@section('title', 'Connexion')
@section('main-class', 'auth')

@section('content')
    <div class="auth-box panel panel-glow">
        <p class="eyebrow">Accès</p>
        <h1>Connexion</h1>
        <p class="muted" style="margin-bottom: 22px">Acheteurs, artistes et administration : un seul accès.</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </div>
            <div class="field">
                <label for="password">Mot de passe <a href="{{ route('password.request') }}">oublié ?</a></label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            <label class="check" style="margin-top: 14px"><input type="checkbox" name="remember" value="1"> <span>Rester connecté</span></label>
            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 22px">Entrer</button>
        </form>

        <hr class="divider">
        <div class="grid-2" style="gap: 10px">
            <a href="{{ route('register') }}" class="btn btn-ghost btn-sm">Créer un compte acheteur</a>
            <a href="{{ route('register.artist') }}" class="btn btn-sm">Devenir artiste</a>
        </div>
    </div>
@endsection
