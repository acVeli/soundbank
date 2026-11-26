<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Settings;
use Illuminate\Support\Facades\Http;

/**
 * PayPal Checkout (Orders v2) via l'API REST.
 */
class PaypalGateway
{
    public function __construct(private Settings $settings) {}

    /**
     * Crée une commande PayPal et retourne l'URL d'approbation.
     */
    public function checkout(Order $order, string $returnUrl, string $cancelUrl): string
    {
        $currency = strtoupper($order->currency);
        $items = $order->items->map(fn ($item): array => [
            'name' => mb_substr(($item->item_type === 'pack' ? 'Pack — ' : '').$item->title, 0, 120),
            'quantity' => '1',
            'category' => 'DIGITAL_GOODS',
            'unit_amount' => ['currency_code' => $currency, 'value' => $this->format($item->price_cents)],
        ])->values()->all();

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(20)
            ->withHeaders(['PayPal-Request-Id' => 'soundbank-order-'.$order->getKey().'-'.$order->updated_at?->timestamp])
            ->post($this->base().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->number,
                    'custom_id' => (string) $order->getKey(),
                    'description' => mb_substr('Commande '.$order->number.' — '.config('app.name'), 0, 120),
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $this->format($order->total_cents),
                        'breakdown' => [
                            'item_total' => ['currency_code' => $currency, 'value' => $this->format($order->total_cents)],
                        ],
                    ],
                    'items' => $items,
                ]],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'brand_name' => mb_substr((string) config('app.name'), 0, 120),
                            'shipping_preference' => 'NO_SHIPPING',
                            'user_action' => 'PAY_NOW',
                            'return_url' => $returnUrl,
                            'cancel_url' => $cancelUrl,
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new PaymentFailed('PayPal a refusé la création du paiement : '.$response->json('message', 'erreur inconnue'));
        }

        $approve = collect((array) $response->json('links'))
            ->first(fn ($link): bool => in_array($link['rel'] ?? null, ['payer-action', 'approve'], true));

        if (! is_array($approve) || ! is_string($approve['href'] ?? null)) {
            throw new PaymentFailed('PayPal n’a pas renvoyé de lien de paiement.');
        }

        $order->update(['reference' => (string) $response->json('id')]);

        return $approve['href'];
    }

    /**
     * Capture la commande approuvée et vérifie le montant encaissé.
     */
    public function capture(Order $order, string $paypalOrderId): bool
    {
        if ($order->reference !== $paypalOrderId) {
            return false;
        }

        $token = $this->accessToken();
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->withBody('{}', 'application/json')
            ->post($this->base().'/v2/checkout/orders/'.rawurlencode($paypalOrderId).'/capture');

        $paypalOrder = (array) $response->json();

        if ($response->failed()) {
            $paypalOrder = (array) Http::withToken($token)->acceptJson()->timeout(20)
                ->get($this->base().'/v2/checkout/orders/'.rawurlencode($paypalOrderId))
                ->json();
        }

        if (($paypalOrder['status'] ?? null) !== 'COMPLETED') {
            return false;
        }

        $unit = $paypalOrder['purchase_units'][0] ?? [];
        $capture = $unit['payments']['captures'][0] ?? [];
        $amount = $capture['amount'] ?? $unit['amount'] ?? [];

        return ($capture['status'] ?? 'COMPLETED') === 'COMPLETED'
            && (string) ($amount['value'] ?? '') === $this->format($order->total_cents)
            && strtoupper((string) ($amount['currency_code'] ?? '')) === strtoupper($order->currency);
    }

    public function test(): bool
    {
        try {
            return $this->accessToken() !== '';
        } catch (PaymentFailed) {
            return false;
        }
    }

    private function accessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth((string) $this->settings->get('paypal_client_id'), (string) $this->settings->get('paypal_secret'))
            ->acceptJson()
            ->timeout(20)
            ->post($this->base().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->failed() || ! is_string($response->json('access_token'))) {
            throw new PaymentFailed('Connexion à PayPal impossible : vérifiez les identifiants API.');
        }

        return (string) $response->json('access_token');
    }

    private function base(): string
    {
        return $this->settings->get('paypal_mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
