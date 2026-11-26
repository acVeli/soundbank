<?php

namespace App\Http\Controllers;

use App\Models\Pack;
use App\Models\Sample;
use App\Services\Cart;
use App\Services\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, Cart $cart, Settings $settings): View
    {
        $lines = $cart->lines($request->user());

        return view('cart', [
            'lines' => $lines,
            'total' => $cart->total($lines),
            'paymentsReady' => $settings->anyPaymentEnabled(),
        ]);
    }

    public function add(Request $request, string $type, int $id, Cart $cart): RedirectResponse|JsonResponse
    {
        $item = $type === 'pack' ? Pack::query()->findOrFail($id) : Sample::query()->findOrFail($id);
        $error = $cart->add($item, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $error === null,
                'message' => $error ?? '« '.$item->title.' » ajouté au panier.',
                'count' => $cart->count(),
            ], $error === null ? 200 : 422);
        }

        return back()->with($error === null ? 'status' : 'error', $error ?? '« '.$item->title.' » ajouté au panier.');
    }

    public function remove(Request $request, string $type, int $id, Cart $cart): RedirectResponse
    {
        $cart->remove($type, $id);

        return redirect()->route('cart')->with('status', 'Article retiré du panier.');
    }
}
