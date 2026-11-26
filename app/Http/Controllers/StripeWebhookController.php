<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\OrderFulfiller;
use App\Services\Payments\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $stripe, OrderFulfiller $fulfiller): Response
    {
        $payload = $request->getContent();

        if (! $stripe->verifySignature($payload, (string) $request->header('Stripe-Signature'))) {
            return response('Signature invalide.', 400);
        }

        $event = json_decode($payload, true);
        $type = is_array($event) ? ($event['type'] ?? null) : null;

        if (! in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return response('Ignoré.', 200);
        }

        $session = (array) ($event['data']['object'] ?? []);
        $order = Order::query()
            ->where('provider', 'stripe')
            ->where('reference', (string) ($session['id'] ?? ''))
            ->first();

        if ($order !== null && $stripe->sessionMatches($order, $session)) {
            $fulfiller->fulfill($order);
        }

        return response('OK', 200);
    }
}
