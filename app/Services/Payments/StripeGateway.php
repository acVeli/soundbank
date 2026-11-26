<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Settings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Stripe Checkout via l'API REST (aucun SDK requis).
 */
class StripeGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function __construct(private Settings $settings) {}

    /**
     * Crée une session Checkout (une ligne par article) et retourne l'URL de paiement.
     */
    public function checkout(Order $order, string $successUrl, string $cancelUrl): string
    {
        $lines = $order->items->map(fn ($item): array => [
            'quantity' => 1,
            'price_data' => [
                'currency' => strtolower($order->currency),
                'unit_amount' => $item->price_cents,
                'product_data' => [
                    'name' => mb_substr(($item->item_type === 'pack' ? 'Pack — ' : '').$item->title, 0, 120),
                    'description' => mb_substr('par '.($item->artist_name ?? 'artiste').' — licence libre de droits', 0, 200),
                ],
            ],
        ])->values()->all();

        $response = $this->client()->asForm()->post(self::API.'/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $successUrl.(str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $order->getKey(),
            'customer_email' => $order->billing_email,
            'metadata' => ['order_id' => (string) $order->getKey(), 'order_number' => $order->number],
            'line_items' => $lines,
        ]);

        if ($response->failed() || ! is_string($response->json('url'))) {
            throw new PaymentFailed('Stripe a refusé la création du paiement : '.$response->json('error.message', 'erreur inconnue'));
        }

        $order->update(['reference' => (string) $response->json('id')]);

        return (string) $response->json('url');
    }

    /**
     * Vérifie auprès de Stripe qu'une session a bien été payée pour cette commande.
     */
    public function confirm(Order $order, string $sessionId): bool
    {
        if ($order->reference !== $sessionId) {
            return false;
        }

        $response = $this->client()->get(self::API.'/checkout/sessions/'.rawurlencode($sessionId));

        return $response->successful() && $this->sessionMatches($order, (array) $response->json());
    }

    /**
     * @param  array<string, mixed>  $session
     */
    public function sessionMatches(Order $order, array $session): bool
    {
        return ($session['payment_status'] ?? null) === 'paid'
            && (string) ($session['client_reference_id'] ?? '') === (string) $order->getKey()
            && (int) ($session['amount_total'] ?? 0) === $order->total_cents
            && strtoupper((string) ($session['currency'] ?? '')) === strtoupper($order->currency);
    }

    /**
     * Vérifie la signature d'un webhook Stripe (en-tête Stripe-Signature).
     */
    public function verifySignature(string $payload, string $header, int $tolerance = 300): bool
    {
        $secret = (string) $this->settings->get('stripe_webhook_secret');

        if ($secret === '' || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            if ($key === 't') {
                $timestamp = (int) $value;
            }

            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function test(): bool
    {
        return $this->client()->get(self::API.'/balance')->successful();
    }

    private function client(): PendingRequest
    {
        return Http::withToken((string) $this->settings->get('stripe_secret_key'))
            ->acceptJson()
            ->timeout(20);
    }
}
