@extends('layouts.app')

@section('title', 'Paiement')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Étape 2 / 2</p>
            <h1>Paiement</h1>
        </div>
        <a href="{{ route('cart') }}" class="btn btn-ghost btn-sm">← Modifier le panier</a>
    </div>

    <form method="POST" action="{{ route('checkout.store', $stripe ? 'stripe' : 'paypal') }}" class="detail" id="checkout-form">
        @csrf
        <div class="panel">
            <p class="panel-title">Facturation</p>
            <div class="fields">
                <div class="field">
                    <label for="billing_name">Nom complet</label>
                    <input id="billing_name" name="billing_name" required maxlength="120" value="{{ old('billing_name', $user->name) }}">
                </div>
                <div class="field">
                    <label for="billing_email">E-mail <span>reçoit la facture</span></label>
                    <input id="billing_email" name="billing_email" type="email" required maxlength="190" value="{{ old('billing_email', $user->email) }}">
                </div>
                <div class="field">
                    <label for="billing_company">Société <span>facultatif</span></label>
                    <input id="billing_company" name="billing_company" maxlength="160" value="{{ old('billing_company') }}">
                </div>
                <div class="field">
                    <label for="billing_vat">N° TVA <span>facultatif</span></label>
                    <input id="billing_vat" name="billing_vat" maxlength="40" value="{{ old('billing_vat') }}">
                </div>
            </div>
            <div class="field" style="margin-top: 16px">
                <label for="billing_address">Adresse <span>facultatif</span></label>
                <textarea id="billing_address" name="billing_address" maxlength="400" rows="3">{{ old('billing_address') }}</textarea>
            </div>

            <hr class="divider">

            <label class="check">
                <input type="checkbox" name="accept_license" value="1" required @checked(old('accept_license'))>
                <span>J’accepte la licence d’utilisation : usage libre de droits dans mes productions, y compris commerciales ; revente ou redistribution des sons seuls interdite. Je demande l’accès immédiat aux fichiers et renonce à mon droit de rétractation.</span>
            </label>
        </div>

        <aside class="panel panel-glow">
            <p class="panel-title">Votre commande</p>
            @foreach ($lines as $line)
                <div class="row between small" style="padding-block: 6px; border-bottom: 1px solid var(--line)">
                    <span>{{ $line['type'] === 'pack' ? 'Pack · ' : '' }}{{ $line['model']->title }}</span>
                    <span class="price">{{ \App\Support\Money::format($line['price_cents']) }}</span>
                </div>
            @endforeach
            <div class="row between" style="margin-top: 16px"><span>Total TTC</span><span class="price-lg">{{ \App\Support\Money::format($total) }}</span></div>

            <div class="stack" style="margin-top: 22px">
                @if ($stripe)
                    <button type="submit" formaction="{{ route('checkout.store', 'stripe') }}" class="btn btn-primary btn-lg btn-block">Payer par carte</button>
                @endif
                @if ($paypal)
                    <button type="submit" formaction="{{ route('checkout.store', 'paypal') }}" class="btn btn-lg btn-block">Payer avec PayPal</button>
                @endif
                @if (! $stripe && ! $paypal)
                    <p class="flash flash-error">Aucun moyen de paiement n’est activé pour le moment.</p>
                @endif
            </div>
            <p class="hint" style="margin-top: 14px">Paiement sécurisé chez {{ $stripe && $paypal ? 'Stripe ou PayPal' : ($stripe ? 'Stripe' : 'PayPal') }}. Vos sons sont disponibles dès la confirmation, la facture vous est envoyée par e-mail.</p>
        </aside>
    </form>
@endsection
