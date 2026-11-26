<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Services\ArtistLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request, ArtistLedger $ledger): View
    {
        $artist = $request->user();
        $paid = $ledger->paidItems($artist);

        return view('studio.sales', [
            'sales' => (clone $paid)->with('order:id,number,paid_at')->latest('id')->paginate(40),
            'totals' => [
                'gross' => (int) (clone $paid)->sum('price_cents'),
                'earned' => (int) (clone $paid)->sum('artist_earning_cents'),
                'count' => (clone $paid)->count(),
            ],
        ]);
    }
}
