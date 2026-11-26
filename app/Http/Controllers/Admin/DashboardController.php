<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\Sample;
use App\Models\User;
use App\Services\Settings;
use Illuminate\View\View;

/**
 * Vue d'ensemble : chiffre d'affaires, commission de la plateforme, dû aux artistes.
 */
class DashboardController extends Controller
{
    public function __invoke(Settings $settings): View
    {
        $paid = Order::query()->where('status', Order::STATUS_PAID);
        $paidItems = OrderItem::query()->whereHas('order', fn ($q) => $q->where('status', Order::STATUS_PAID));
        $gross = (int) (clone $paidItems)->sum('price_cents');
        $artistShare = (int) (clone $paidItems)->sum('artist_earning_cents');
        $paidOut = (int) Payout::query()->where('status', Payout::STATUS_PAID)->sum('amount_cents');

        return view('admin.dashboard', [
            'stats' => [
                'revenue' => $gross,
                'month' => (int) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total_cents'),
                'commission' => $gross - $artistShare,
                'owed' => $artistShare - $paidOut,
                'orders' => (clone $paid)->count(),
                'artists' => User::query()->where('role', User::ROLE_ARTIST)->count(),
                'buyers' => User::query()->where('role', User::ROLE_BUYER)->count(),
                'samples' => Sample::query()->where('status', Sample::STATUS_PUBLISHED)->count(),
                'payoutRequests' => Payout::query()->where('status', Payout::STATUS_REQUESTED)->count(),
            ],
            'orders' => (clone $paid)->with('user:id,name,email')->withCount('items')->latest('paid_at')->limit(10)->get(),
            'checklist' => [
                'Paiement Stripe ou PayPal activé' => $settings->anyPaymentEnabled(),
                'Webhook Stripe configuré' => ! $settings->stripeEnabled() || $settings->filled('stripe_webhook_secret'),
                'Informations vendeur (nom, adresse, SIRET)' => $settings->filled('seller_name') && $settings->filled('seller_address') && $settings->filled('seller_siret'),
            ],
        ]);
    }
}
