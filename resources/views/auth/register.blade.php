@extends('layouts.app')

@section('title', $artist ? 'Devenir artiste' : 'Créer un compte')
@section('main-class', 'auth')

@section('content')
    <div class="auth-box panel panel-glow">
        <div class="switch" role="tablist">
            <a href="{{ route('register') }}" @class(['is-active' => ! $artist])>J’achète des sons</a>
            <a href="{{ route('register.artist') }}" @class(['is-active' => $artist])>Je vends mes sons</a>
        </div>

        <p class="eyebrow">{{ $artist ? 'Parcours artiste' : 'Parcours acheteur' }}</p>
        <h1>{{ $artist ? 'Ouvrir mon studio' : 'Créer un compte' }}</h1>
        <p class="muted" style="margin-bottom: 22px">
            {{ $artist
                ? 'Importez vos voix et vos sons, organisez votre catalogue, encaissez vos ventes.'
                : 'Achetez, téléchargez en qualité studio, retrouvez tout dans votre bibliothèque.' }}
        </p>

        <form method="POST" action="{{ $artist ? route('register.artist.store') : route('register.store') }}">
            @csrf
            @if ($artist)
                <div class="field">
                    <label for="stage_name">Nom d’artiste <span>public</span></label>
                    <input id="stage_name" name="stage_name" value="{{ old('stage_name') }}" required maxlength="80" autofocus>
                </div>
            @endif
            <div class="field">
                <label for="name">Nom complet <span>privé</span></label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" @unless ($artist) autofocus @endunless>
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            </div>
            <div class="field">
                <label for="password">Mot de passe <span>8 caractères min.</span></label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirmation">Confirmation</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <label class="check" style="margin-top: 16px">
                <input type="checkbox" name="terms" value="1" required>
                <span>{{ $artist
                    ? 'J’accepte les conditions vendeur : je détiens les droits des sons que je publie et j’accepte la commission de la plateforme.'
                    : 'J’accepte les conditions d’utilisation et la licence des sons.' }}</span>
            </label>
            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 22px">{{ $artist ? 'Ouvrir mon studio' : 'Créer mon compte' }}</button>
        </form>

        <p class="small muted" style="margin-top: 18px; text-align: center">Déjà inscrit ? <a href="{{ route('login') }}">Connexion</a></p>
    </div>
@endsection
