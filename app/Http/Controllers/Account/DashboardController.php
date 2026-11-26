<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('account.dashboard', [
            'user' => $user,
            'recent' => $user->libraryItems()->with(['sample.artist.artistProfile', 'sample.category'])->latest()->limit(6)->get(),
            'orders' => $user->orders()->where('status', Order::STATUS_PAID)->withCount('items')->latest('paid_at')->limit(5)->get(),
            'stats' => [
                'library' => $user->libraryItems()->count(),
                'orders' => $user->orders()->where('status', Order::STATUS_PAID)->count(),
                'favorites' => $user->favorites()->count(),
                'spent' => (int) $user->orders()->where('status', Order::STATUS_PAID)->sum('total_cents'),
            ],
        ]);
    }
}
