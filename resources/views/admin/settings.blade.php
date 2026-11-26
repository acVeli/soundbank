@extends('admin.layout')

@section('title', 'Paiements & réglages')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Paiements & réglages</h1>
            <p class="lede">Les clés sont chiffrées en base. Les clés secrètes déjà enregistrées ne sont jamais réaffichées : laissez le champ vide pour les conserver.</p>
        </div>
        <div class="row">
            <form method="POST" action="{{ route('admin.settings.test', 'stripe') }}">@csrf<button class="btn btn-sm btn-ghost">Tester Stripe</button></form>
            <form method="POST" action="{{ route('admin.settings.test', 'paypal') }}">@csrf<button class="btn btn-sm btn-ghost">Tester PayPal</button></form>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid-2">
            <section class="panel">
                <p class="panel-title">Stripe <span @class(['badge', 'badge-ok' => $status['stripe'], 'badge-muted' => ! $status['stripe']])>{{ $status['stripe'] ? 'Actif' : 'Inactif' }}</span></p>
                <label class="check"><input type="checkbox" name="stripe_enabled" value="1" @checked(old('stripe_enabled', $values['stripe_enabled']) === '1')> <span>Activer le paiement par carte (Stripe Checkout)</span></label>
                <div class="field" style="margin-top: 16px">
                    <label for="stripe_public_key">Clé publiable <span>pk_live_… / pk_test_…</span></label>
                    <input id="stripe_public_key" name="stripe_public_key" autocomplete="off" spellcheck="false" value="{{ old('stripe_public_key', $values['stripe_public_key']) }}">
                </div>
                <div class="field">
                    <label for="stripe_secret_key">Clé secrète <span>{{ $saved['stripe_secret_key'] ? 'enregistrée' : 'non renseignée' }}</span></label>
                    <input id="stripe_secret_key" name="stripe_secret_key" type="password" autocomplete="off" placeholder="{{ $saved['stripe_secret_key'] ? '•••••••• (inchangée)' : 'sk_live_…' }}">
                    @if ($saved['stripe_secret_key'])
                        <label class="check small"><input type="checkbox" name="clear_stripe_secret_key" value="1"> <span>Effacer la clé enregistrée</span></label>
                    @endif
                </div>
                <div class="field">
                    <label for="stripe_webhook_secret">Secret du webhook <span>{{ $saved['stripe_webhook_secret'] ? 'enregistré' : 'non renseigné' }}</span></label>
                    <input id="stripe_webhook_secret" name="stripe_webhook_secret" type="password" autocomplete="off" placeholder="{{ $saved['stripe_webhook_secret'] ? '•••••••• (inchangé)' : 'whsec_…' }}">
                    @if ($saved['stripe_webhook_secret'])
                        <label class="check small"><input type="checkbox" name="clear_stripe_webhook_secret" value="1"> <span>Effacer le secret enregistré</span></label>
                    @endif
                    <p class="hint">Stripe › Développeurs › Webhooks : ajoutez <code>{{ $webhookUrl }}</code> avec les événements <code>checkout.session.completed</code> et <code>checkout.session.async_payment_succeeded</code>.</p>
                </div>
            </section>

            <section class="panel">
                <p class="panel-title">PayPal <span @class(['badge', 'badge-ok' => $status['paypal'], 'badge-muted' => ! $status['paypal']])>{{ $status['paypal'] ? 'Actif' : 'Inactif' }}</span></p>
                <label class="check"><input type="checkbox" name="paypal_enabled" value="1" @checked(old('paypal_enabled', $values['paypal_enabled']) === '1')> <span>Activer le paiement PayPal</span></label>
                <div class="field" style="margin-top: 16px">
                    <label for="paypal_mode">Environnement</label>
                    <select id="paypal_mode" name="paypal_mode">
                        <option value="sandbox" @selected(old('paypal_mode', $values['paypal_mode']) !== 'live')>Sandbox (tests)</option>
                        <option value="live" @selected(old('paypal_mode', $values['paypal_mode']) === 'live')>Production</option>
                    </select>
                </div>
                <div class="field">
                    <label for="paypal_client_id">Client ID</label>
                    <input id="paypal_client_id" name="paypal_client_id" autocomplete="off" spellcheck="false" value="{{ old('paypal_client_id', $values['paypal_client_id']) }}">
                </div>
                <div class="field">
                    <label for="paypal_secret">Secret <span>{{ $saved['paypal_secret'] ? 'enregistré' : 'non renseigné' }}</span></label>
                    <input id="paypal_secret" name="paypal_secret" type="password" autocomplete="off" placeholder="{{ $saved['paypal_secret'] ? '•••••••• (inchangé)' : '' }}">
                    @if ($saved['paypal_secret'])
                        <label class="check small"><input type="checkbox" name="clear_paypal_secret" value="1"> <span>Effacer le secret enregistré</span></label>
                    @endif
                    <p class="hint">developer.paypal.com › Apps & Credentials : créez une application REST et copiez le Client ID et le Secret.</p>
                </div>
            </section>
        </div>

        <section class="panel" style="margin-top: 24px">
            <p class="panel-title">Marketplace</p>
            <div class="fields">
                <div class="field">
                    <label for="commission_percent">Commission plateforme (%)</label>
                    <input id="commission_percent" name="commission_percent" type="number" step="0.1" min="0" max="100" required value="{{ old('commission_percent', $values['commission_percent']) }}">
                    <p class="hint">Prélevée sur chaque vente ; l’artiste reçoit le reste. Figée au moment de l’achat.</p>
                </div>
                <div class="field">
                    <label for="payout_min_euros">Versement minimum (€)</label>
                    <input id="payout_min_euros" name="payout_min_euros" type="number" step="0.01" min="0" required value="{{ old('payout_min_euros', \App\Support\Money::input($values['payout_min_cents'])) }}">
                </div>
            </div>
        </section>

        <section class="panel" style="margin-top: 24px">
            <p class="panel-title">Facturation</p>
            <p class="hint" style="margin-bottom: 14px">Ces informations apparaissent sur chaque nouvelle facture. Les factures déjà émises ne changent pas.</p>
            <div class="fields">
                <div class="field">
                    <label for="seller_name">Nom ou raison sociale</label>
                    <input id="seller_name" name="seller_name" maxlength="160" value="{{ old('seller_name', $values['seller_name']) }}">
                </div>
                <div class="field">
                    <label for="seller_legal">Statut juridique <span>facultatif</span></label>
                    <input id="seller_legal" name="seller_legal" maxlength="200" value="{{ old('seller_legal', $values['seller_legal']) }}" placeholder="SAS au capital de…">
                </div>
                <div class="field">
                    <label for="seller_siret">SIRET</label>
                    <input id="seller_siret" name="seller_siret" maxlength="20" value="{{ old('seller_siret', $values['seller_siret']) }}">
                </div>
                <div class="field">
                    <label for="seller_vat_number">N° TVA intracommunautaire <span>facultatif</span></label>
                    <input id="seller_vat_number" name="seller_vat_number" maxlength="40" value="{{ old('seller_vat_number', $values['seller_vat_number']) }}">
                </div>
                <div class="field">
                    <label for="seller_email">E-mail de contact</label>
                    <input id="seller_email" name="seller_email" type="email" maxlength="190" value="{{ old('seller_email', $values['seller_email']) }}">
                </div>
                <div class="field">
                    <label for="vat_rate">Taux de TVA (%)</label>
                    <input id="vat_rate" name="vat_rate" type="number" step="0.01" min="0" max="30" required value="{{ old('vat_rate', $values['vat_rate']) }}">
                </div>
                <div class="field">
                    <label for="vat_mention">Mention si TVA à 0 %</label>
                    <input id="vat_mention" name="vat_mention" maxlength="255" value="{{ old('vat_mention', $values['vat_mention']) }}">
                </div>
                <div class="field">
                    <label for="invoice_series">Préfixe des factures</label>
                    <input id="invoice_series" name="invoice_series" maxlength="12" required value="{{ old('invoice_series', $values['invoice_series']) }}">
                </div>
                <div class="field">
                    <label for="credit_series">Préfixe des avoirs</label>
                    <input id="credit_series" name="credit_series" maxlength="12" required value="{{ old('credit_series', $values['credit_series']) }}">
                </div>
            </div>
            <div class="grid-2" style="margin-top: 16px">
                <div class="field">
                    <label for="seller_address">Adresse</label>
                    <textarea id="seller_address" name="seller_address" rows="3" maxlength="400">{{ old('seller_address', $values['seller_address']) }}</textarea>
                </div>
                <div class="field" style="margin-top: 0">
                    <label for="seller_footer">Pied de facture <span>facultatif</span></label>
                    <textarea id="seller_footer" name="seller_footer" rows="3" maxlength="1000">{{ old('seller_footer', $values['seller_footer']) }}</textarea>
                </div>
            </div>
        </section>

        <div class="sticky-actions">
            <span class="small muted">Les modifications s’appliquent aux prochaines commandes.</span>
            <button class="btn btn-primary">Enregistrer les réglages</button>
        </div>
    </form>
@endsection
