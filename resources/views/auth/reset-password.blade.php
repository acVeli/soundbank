@extends('layouts.app')

@section('title', 'Nouveau mot de passe')
@section('main-class', 'auth')

@section('content')
    <div class="auth-box panel panel-glow">
        <p class="eyebrow">Récupération</p>
        <h1>Nouveau mot de passe</h1>
        <form method="POST" action="{{ route('password.update') }}" style="margin-top: 20px">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
            </div>
            <div class="field">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirmation">Confirmation</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px">Enregistrer</button>
        </form>
    </div>
@endsection
