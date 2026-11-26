@extends('layouts.base')

{{-- Espace de travail (studio artiste ou administration) : barre latérale + contenu. --}}
@section('body')
    <div class="shell">
        <aside class="shell-side" id="shell-side">
            <div>
                <a href="{{ route('home') }}" class="logo"><span class="logo-mark"></span>{{ strtoupper(config('app.name')) }}</a>
                <p class="shell-tag" style="margin-top: 10px">// @yield('shell-name')</p>
            </div>

            @yield('nav')

            <div class="side-foot">
                <a href="{{ route('home') }}" class="muted">← Retour au site</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link-btn">Déconnexion</button>
                </form>
            </div>
        </aside>

        <main class="shell-main">
            <button type="button" class="btn btn-sm btn-ghost shell-toggle" data-toggle="#shell-side"><x-icon name="menu" size="14" /> Menu</button>
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
@endsection
