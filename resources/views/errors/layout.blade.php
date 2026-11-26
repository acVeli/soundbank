@extends('layouts.base')

@section('title', $code)

@section('body')
    <main class="auth">
        <div class="panel panel-glow" style="max-width: 520px; text-align: center; padding: 48px 32px">
            <p class="eyebrow" style="justify-content: center">Erreur {{ $code }}</p>
            <h1 style="font-size: 4rem; margin-block: 10px; text-shadow: 0 0 30px rgba(92, 225, 255, .6)">{{ $code }}</h1>
            <p class="lede" style="margin-inline: auto">{{ $message }}</p>
            <div class="row" style="justify-content: center; margin-top: 24px">
                <a href="{{ url('/') }}" class="btn btn-primary">Accueil</a>
                <a href="javascript:history.back()" class="btn btn-ghost">Retour</a>
            </div>
        </div>
    </main>
@endsection
