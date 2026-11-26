<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Comptes d'un artiste : gains (part figée sur chaque vente payée) moins les
 * versements demandés ou effectués.
 */
class ArtistLedger
{
    /**
     * @return Builder<OrderItem>
     */
    public function paidItems(User $artist): Builder
    {
        return OrderItem::query()
            ->where('artist_id', $artist->getKey())
            ->whereHas('order', fn (Builder $q) => $q->where('status', Order::STATUS_PAID));
    }

    public function earnedCents(User $artist): int
    {
        return (int) $this->paidItems($artist)->sum('artist_earning_cents');
    }

    public function paidOutCents(User $artist): int
    {
        return (int) $artist->payouts()->where('status', Payout::STATUS_PAID)->sum('amount_cents');
    }

    public function pendingPayoutCents(User $artist): int
    {
        return (int) $artist->payouts()->where('status', Payout::STATUS_REQUESTED)->sum('amount_cents');
    }

    /**
     * Solde disponible pour une nouvelle demande de versement.
     */
    public function availableCents(User $artist): int
    {
        return max(0, $this->earnedCents($artist) - $this->paidOutCents($artist) - $this->pendingPayoutCents($artist));
    }
}
