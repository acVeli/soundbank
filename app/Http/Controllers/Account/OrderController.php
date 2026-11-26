<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Models\Order;
use App\Support\ZipStream;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders', [
            'orders' => $request->user()->orders()
                ->where('status', Order::STATUS_PAID)
                ->with('invoice')
                ->withCount('items')
                ->latest('paid_at')
                ->paginate(20),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        return view('account.order', [
            'order' => $order->load(['items.sample', 'items.pack', 'invoice']),
            'library' => $this->libraryItems($order),
        ]);
    }

    /**
     * Toute la commande dans une archive ZIP, rangée par artiste.
     */
    public function zip(Request $request, Order $order): StreamedResponse
    {
        $this->authorizeOrder($request, $order);
        $items = $this->libraryItems($order);
        abort_if($items->isEmpty(), 404);

        $disk = Storage::disk('local');

        return response()->streamDownload(function () use ($items, $disk): void {
            $zip = new ZipStream(fopen('php://output', 'wb'));
            $used = [];

            foreach ($items as $item) {
                $sample = $item->sample;

                if ($sample === null || ! $disk->exists($sample->file_path)) {
                    continue;
                }

                $folder = Str::slug($sample->artist?->displayName() ?? 'artiste');
                $name = $folder.'/'.$sample->downloadName();

                while (isset($used[$name])) {
                    $name = $folder.'/'.Str::random(4).'_'.$sample->downloadName();
                }

                $used[$name] = true;
                $zip->addFile($disk->path($sample->file_path), $name);
                $item->increment('download_count', 1, ['last_downloaded_at' => now()]);
            }

            $zip->finish();
        }, Str::slug(config('app.name')).'_'.$order->number.'.zip', ['Content-Type' => 'application/zip']);
    }

    /**
     * @return Collection<int, LibraryItem>
     */
    private function libraryItems(Order $order)
    {
        return LibraryItem::query()
            ->where('user_id', $order->user_id)
            ->whereIn('order_item_id', $order->items()->pluck('id'))
            ->with(['sample.artist.artistProfile', 'sample.category'])
            ->get();
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->getKey() && $order->isPaid(), 404);
    }
}
