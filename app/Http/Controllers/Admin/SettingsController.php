<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaypalGateway;
use App\Services\Payments\StripeGateway;
use App\Services\Settings;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Raccordement Stripe / PayPal, commission de la plateforme et facturation.
 */
class SettingsController extends Controller
{
    /**
     * @var list<string>
     */
    private const BILLING_KEYS = [
        'seller_name',
        'seller_legal',
        'seller_address',
        'seller_siret',
        'seller_vat_number',
        'seller_email',
        'seller_footer',
        'vat_rate',
        'vat_mention',
        'invoice_series',
        'credit_series',
    ];

    public function edit(Settings $settings): View
    {
        $values = [];

        foreach (['stripe_enabled', 'stripe_public_key', 'paypal_enabled', 'paypal_client_id', 'paypal_mode', 'commission_percent', ...self::BILLING_KEYS] as $key) {
            $values[$key] = $settings->get($key);
        }

        $values['payout_min_cents'] = $settings->payoutMinCents();

        $saved = [];

        foreach (Settings::SECRETS as $key) {
            $saved[$key] = $settings->filled($key);
        }

        return view('admin.settings', [
            'values' => $values,
            'saved' => $saved,
            'webhookUrl' => route('webhooks.stripe'),
            'status' => [
                'stripe' => $settings->stripeEnabled(),
                'paypal' => $settings->paypalEnabled(),
            ],
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'stripe_public_key' => ['nullable', 'string', 'max:255', 'regex:/^pk_(test|live)_/'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255', 'regex:/^(sk|rk)_(test|live)_/'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255', 'regex:/^whsec_/'],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_secret' => ['nullable', 'string', 'max:255'],
            'paypal_mode' => ['required', 'in:sandbox,live'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payout_min_euros' => ['required', 'numeric', 'min:0', 'max:10000'],
            'seller_name' => ['nullable', 'string', 'max:160'],
            'seller_legal' => ['nullable', 'string', 'max:200'],
            'seller_address' => ['nullable', 'string', 'max:400'],
            'seller_siret' => ['nullable', 'string', 'max:20'],
            'seller_vat_number' => ['nullable', 'string', 'max:40'],
            'seller_email' => ['nullable', 'email', 'max:190'],
            'seller_footer' => ['nullable', 'string', 'max:1000'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:30'],
            'vat_mention' => ['nullable', 'string', 'max:255'],
            'invoice_series' => ['required', 'alpha_num:ascii', 'max:12'],
            'credit_series' => ['required', 'alpha_num:ascii', 'max:12', 'different:invoice_series'],
        ], [
            'credit_series.different' => 'Les avoirs doivent avoir un préfixe différent des factures.',
            'stripe_public_key.regex' => 'La clé publique Stripe commence par pk_test_ ou pk_live_.',
            'stripe_secret_key.regex' => 'La clé secrète Stripe commence par sk_test_, sk_live_, rk_test_ ou rk_live_.',
            'stripe_webhook_secret.regex' => 'Le secret de webhook Stripe commence par whsec_.',
        ]);

        $values = [
            'stripe_enabled' => $request->boolean('stripe_enabled') ? '1' : '0',
            'paypal_enabled' => $request->boolean('paypal_enabled') ? '1' : '0',
            'stripe_public_key' => $data['stripe_public_key'] ?? null,
            'paypal_client_id' => $data['paypal_client_id'] ?? null,
            'paypal_mode' => $data['paypal_mode'],
            'commission_percent' => (string) (float) $data['commission_percent'],
            'payout_min_cents' => (string) Money::toCents($data['payout_min_euros']),
        ];

        foreach (self::BILLING_KEYS as $key) {
            $values[$key] = filled($data[$key] ?? null) ? trim((string) $data[$key]) : null;
        }

        $values['vat_rate'] = (string) (float) $data['vat_rate'];
        $values['invoice_series'] = strtoupper($data['invoice_series']);
        $values['credit_series'] = strtoupper($data['credit_series']);

        foreach (Settings::SECRETS as $key) {
            if ($request->boolean('clear_'.$key)) {
                $values[$key] = null;
            } elseif (filled($data[$key] ?? null)) {
                $values[$key] = trim((string) $data[$key]);
            }
        }

        $settings->put($values);

        return redirect()->route('admin.settings')->with('status', 'Les réglages sont enregistrés.');
    }

    public function test(string $provider, Settings $settings, StripeGateway $stripe, PaypalGateway $paypal): RedirectResponse
    {
        $ok = match ($provider) {
            'stripe' => $settings->filled('stripe_secret_key') && $stripe->test(),
            'paypal' => $settings->filled('paypal_client_id') && $settings->filled('paypal_secret') && $paypal->test(),
            default => abort(404),
        };

        $name = $provider === 'stripe' ? 'Stripe' : 'PayPal';

        return redirect()->route('admin.settings')->with(
            $ok ? 'status' : 'error',
            $ok ? "Connexion à {$name} réussie." : "Connexion à {$name} impossible : vérifiez les clés enregistrées.",
        );
    }
}
