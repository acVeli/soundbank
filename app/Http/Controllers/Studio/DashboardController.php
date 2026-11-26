<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Sample;
use App\Services\ArtistLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Tableau de bord du studio : ventes, gains, écoutes, meilleurs sons.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, ArtistLedger $ledger): View
    {
        $artist = $request->user();
        $paid = $ledger->paidItems($artist);

        $from = now()->subDays(29)->startOfDay();
        $daily = (clone $paid)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.paid_at', '>=', $from)
            ->get(['orders.paid_at', 'order_items.artist_earning_cents'])
            ->groupBy(fn ($row): string => Carbon::parse($row->paid_at)->format('Y-m-d'))
            ->map(fn ($rows): int => (int) $rows->sum('artist_earning_cents'));

        $chart = collect(range(0, 29))->map(function (int $offset) use ($from, $daily): array {
            $day = $from->copy()->addDays($offset);

            return ['date' => $day->format('d/m'), 'cents' => (int) ($daily[$day->format('Y-m-d')] ?? 0)];
        });

        return view('studio.dashboard', [
            'profile' => $artist->artistProfile,
            'stats' => [
                'earned' => $ledger->earnedCents($artist),
                'available' => $ledger->availableCents($artist),
                'month' => (int) (clone $paid)->whereHas('order', fn ($q) => $q->where('paid_at', '>=', now()->startOfMonth()))->sum('artist_earning_cents'),
                'sales' => (clone $paid)->count(),
                'samples' => $artist->samples()->count(),
                'published' => $artist->samples()->where('status', Sample::STATUS_PUBLISHED)->count(),
                'plays' => (int) $artist->samples()->sum('plays_count'),
                'packs' => $artist->packs()->count(),
            ],
            'chart' => $chart,
            'top' => $artist->samples()->with('category')->orderByDesc('sales_count')->orderByDesc('plays_count')->limit(5)->get(),
            'recentSales' => (clone $paid)->with('order')->latest('id')->limit(8)->get(),
        ]);
    }
}
