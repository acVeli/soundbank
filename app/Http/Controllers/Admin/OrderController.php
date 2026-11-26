<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('statut')->value() ?: Order::STATUS_PAID;
        $search = trim($request->string('q')->value());

        return view('admin.orders', [
            'orders' => Order::query()
                ->with(['user:id,name,email', 'invoice'])
                ->withCount('items')
                ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
                ->when($search !== '', function (Builder $q) use ($search): void {
                    $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                    $q->where(fn (Builder $inner) => $inner->where('number', 'like', $like)
                        ->orWhere('billing_email', 'like', $like)
                        ->orWhere('billing_name', 'like', $like)
                        ->orWhere('reference', 'like', $like));
                })
                ->latest()
                ->paginate(40)
                ->withQueryString(),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.order', [
            'order' => $order->load(['user', 'items.artist.artistProfile', 'invoice']),
        ]);
    }
}
