<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\LibraryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pack;
use App\Models\Sample;
use App\Services\InvoiceIssuer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Livre une commande une seule fois par paiement confirmé (retour navigateur
 * ou webhook, selon ce qui arrive en premier) : bibliothèque de l'acheteur,
 * compteurs de ventes, facture puis envoi par e-mail.
 */
class OrderFulfiller
{
    public function __construct(private InvoiceIssuer $invoices) {}

    public function fulfill(Order $order): bool
    {
        /** @var Invoice|null $invoice */
        $invoice = DB::transaction(function () use ($order): ?Invoice {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return null;
            }

            $locked->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
            ]);

            $locked->load(['items', 'user']);

            foreach ($locked->items as $item) {
                $this->deliver($locked, $item);
            }

            return $this->invoices->forOrder($locked);
        });

        $order->refresh();

        if ($invoice === null) {
            return false;
        }

        $this->invoices->send($invoice);

        return true;
    }

    private function deliver(Order $order, OrderItem $item): void
    {
        if ($order->user_id === null) {
            return;
        }

        if ($item->item_type === OrderItem::TYPE_PACK) {
            $sampleIds = Sample::query()->where('pack_id', $item->pack_id)->where('status', Sample::STATUS_PUBLISHED)->pluck('id');
            Pack::withTrashed()->whereKey($item->pack_id)->increment('sales_count');
        } else {
            $sampleIds = collect([$item->sample_id])->filter();
            Sample::withTrashed()->whereKey($item->sample_id)->increment('sales_count');
        }

        foreach ($sampleIds as $sampleId) {
            LibraryItem::query()->firstOrCreate(
                ['user_id' => $order->user_id, 'sample_id' => $sampleId],
                ['order_item_id' => $item->getKey(), 'license_key' => 'SBL-'.Str::upper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4))],
            );
        }
    }
}
