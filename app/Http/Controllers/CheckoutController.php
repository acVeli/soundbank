<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pack;
use App\Models\Sample;
use App\Services\Cart;
use App\Services\Payments\OrderFulfiller;
use App\Services\Payments\PaymentFailed;
use App\Services\Payments\PaypalGateway;
use App\Services\Payments\StripeGateway;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Passage en caisse : la commande fige les articles, les prix et la part de
 * chaque artiste, puis le client paie chez Stripe ou PayPal.
 */
class CheckoutController extends Controller
{
    public function show(Request $request, Cart $cart, Settings $settings): View|RedirectResponse
    {
        $lines = $cart->lines($request->user());

        if ($lines->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Votre panier est vide.');
        }

        return view('checkout', [
            'lines' => $lines,
            'total' => $cart->total($lines),
            'stripe' => $settings->stripeEnabled(),
            'paypal' => $settings->paypalEnabled(),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request, string $provider, Cart $cart, Settings $settings, StripeGateway $stripe, PaypalGateway $paypal): RedirectResponse
    {
        $enabled = $provider === 'stripe' ? $settings->stripeEnabled() : $settings->paypalEnabled();
        abort_unless($enabled, 404);

        $billing = $request->validate([
            'billing_name' => ['required', 'string', 'max:120'],
            'billing_email' => ['required', 'email', 'max:190'],
            'billing_company' => ['nullable', 'string', 'max:160'],
            'billing_address' => ['nullable', 'string', 'max:400'],
            'billing_vat' => ['nullable', 'string', 'max:40'],
            'accept_license' => ['accepted'],
        ], [
            'billing_name.required' => 'Indiquez le nom à faire figurer sur la facture.',
            'accept_license.accepted' => 'Vous devez accepter la licence d’utilisation.',
        ]);
        unset($billing['accept_license']);

        $user = $request->user();
        $lines = $cart->lines($user);

        if ($lines->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Votre panier est vide.');
        }

        $commission = $settings->commissionBasisPoints();

        $order = DB::transaction(function () use ($user, $lines, $billing, $provider, $commission, $settings): Order {
            $order = Order::query()->create([
                ...array_map(fn ($value) => filled($value) ? trim((string) $value) : null, $billing),
                'user_id' => $user->getKey(),
                'status' => Order::STATUS_PENDING,
                'provider' => $provider,
                'total_cents' => (int) $lines->sum('price_cents'),
                'currency' => $settings->currency(),
            ]);

            foreach ($lines as $line) {
                /** @var Sample|Pack $model */
                $model = $line['model'];
                $price = $line['price_cents'];
                $fee = (int) round($price * $commission / 10000);

                $order->items()->create([
                    'item_type' => $line['type'] === 'pack' ? OrderItem::TYPE_PACK : OrderItem::TYPE_SAMPLE,
                    'sample_id' => $model instanceof Sample ? $model->getKey() : null,
                    'pack_id' => $model instanceof Pack ? $model->getKey() : null,
                    'artist_id' => $model->artist_id,
                    'title' => mb_substr($model->title, 0, 160),
                    'artist_name' => mb_substr($model->artist?->displayName() ?? '', 0, 80) ?: null,
                    'price_cents' => $price,
                    'commission_bp' => $commission,
                    'artist_earning_cents' => $price - $fee,
                ]);
            }

            return $order;
        });

        $order->load('items');

        try {
            $url = $provider === 'stripe'
                ? $stripe->checkout($order, route('checkout.stripe.return', $order), route('checkout.cancelled', $order))
                : $paypal->checkout($order, route('checkout.paypal.return', $order), route('checkout.cancelled', $order));
        } catch (PaymentFailed $exception) {
            Log::warning('Création de paiement impossible.', ['order' => $order->number, 'error' => $exception->getMessage()]);
            $order->update(['status' => Order::STATUS_FAILED]);

            return redirect()->route('checkout')->with('error', 'Le paiement n’a pas pu être lancé. Réessayez ou choisissez un autre moyen de paiement.');
        }

        return redirect()->away($url);
    }

    public function stripeReturn(Request $request, Order $order, Cart $cart, StripeGateway $stripe, OrderFulfiller $fulfiller): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if (! $order->isPaid()) {
            $sessionId = $request->string('session_id')->value();

            if ($sessionId === '' || ! $stripe->confirm($order, $sessionId)) {
                return redirect()->route('checkout')->with('error', 'Le paiement n’a pas été confirmé par Stripe.');
            }

            $fulfiller->fulfill($order);
        }

        $cart->clear();

        return redirect()->route('checkout.success', $order);
    }

    public function paypalReturn(Request $request, Order $order, Cart $cart, PaypalGateway $paypal, OrderFulfiller $fulfiller): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if (! $order->isPaid()) {
            $token = $request->string('token')->value();

            try {
                $captured = $token !== '' && $paypal->capture($order, $token);
            } catch (PaymentFailed) {
                $captured = false;
            }

            if (! $captured) {
                return redirect()->route('checkout')->with('error', 'Le paiement PayPal n’a pas abouti.');
            }

            $fulfiller->fulfill($order);
        }

        $cart->clear();

        return redirect()->route('checkout.success', $order);
    }

    public function cancelled(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        return redirect()->route('cart')->with('error', 'Paiement annulé. Votre panier est conservé.');
    }

    public function success(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);
        abort_unless($order->isPaid(), 404);

        return view('checkout-success', [
            'order' => $order->load(['items', 'invoice']),
        ]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->getKey(), 404);
    }
}
