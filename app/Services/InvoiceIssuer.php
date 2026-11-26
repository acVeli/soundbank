<?php

namespace App\Services;

use App\Mail\InvoiceIssued;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Émission des factures et avoirs, numérotés sans trou par série et par année
 * (ex. F-2026-000001, AV-2026-000001). Chaque document fige les informations
 * du vendeur, du client et des lignes au moment de l'émission.
 */
class InvoiceIssuer
{
    public function __construct(private Settings $settings) {}

    /**
     * Facture une commande payée (une seule fois). À appeler dans la transaction
     * qui marque la commande comme payée.
     */
    public function forOrder(Order $order): Invoice
    {
        $existing = Invoice::query()->invoices()->where('order_id', $order->getKey())->first();

        if ($existing !== null) {
            return $existing;
        }

        $vatRate = $this->vatRateBasisPoints();
        $lines = [];
        $totalHt = 0;

        foreach ($order->items as $item) {
            $unitHt = $this->excludingVat($item->price_cents, $vatRate);
            $totalHt += $unitHt;
            $lines[] = [
                'description' => mb_substr(
                    ($item->item_type === 'pack' ? 'Pack « ' : 'Sample « ').$item->title.' »'
                    .($item->artist_name ? ' — '.$item->artist_name : '')
                    .' — licence d’utilisation libre de droits',
                    0,
                    255,
                ),
                'quantity' => 1,
                'unit_ht_cents' => $unitHt,
                'total_ht_cents' => $unitHt,
            ];
        }

        $count = count($lines);

        return $this->create(Invoice::TYPE_INVOICE, [
            'order_id' => $order->getKey(),
            'buyer_name' => $order->billing_name ?: ($order->user?->name ?? 'Client'),
            'buyer_email' => $order->billing_email ?: $order->user?->email,
            'buyer_company' => $order->billing_company,
            'buyer_address' => $order->billing_address,
            'buyer_vat' => $order->billing_vat,
            'description' => 'Commande '.$order->number.' — '.$count.' article'.($count > 1 ? 's' : '').' numérique'.($count > 1 ? 's' : ''),
            'lines' => $lines,
            'vat_rate_bp' => $vatRate,
            'vat_mention' => $vatRate === 0 ? $this->settings->get('vat_mention') : null,
            'total_ht_cents' => $totalHt,
            'total_vat_cents' => $order->total_cents - $totalHt,
            'total_ttc_cents' => $order->total_cents,
            'currency' => $order->currency,
            'payment_method' => $order->provider === 'stripe' ? 'Carte bancaire (Stripe)' : 'PayPal',
            'payment_reference' => $order->reference,
            'paid_at' => $order->paid_at ?? now(),
        ]);
    }

    /**
     * Corrige une facture : émet un avoir qui l'annule intégralement, puis une
     * nouvelle facture avec les informations client corrigées.
     *
     * @param  array{buyer_name: string, buyer_email: ?string, buyer_company: ?string, buyer_address: ?string, buyer_vat: ?string}  $buyer
     * @return array{credit_note: Invoice, invoice: Invoice}
     */
    public function correct(Invoice $invoice, array $buyer): array
    {
        return DB::transaction(function () use ($invoice, $buyer): array {
            $locked = Invoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            abort_if($locked->isCreditNote() || $locked->creditNote()->exists(), 409, 'Cette facture a déjà été annulée.');

            $copy = collect($locked->getAttributes())->except([
                'id', 'type', 'series', 'year', 'sequence', 'number', 'issued_at', 'access_token',
                'credited_invoice_id', 'sent_at', 'created_at', 'updated_at', 'seller', 'lines',
            ])->all();
            $copy['lines'] = $locked->lines;

            $creditNote = $this->create(Invoice::TYPE_CREDIT_NOTE, [
                ...$copy,
                'credited_invoice_id' => $locked->getKey(),
                'description' => mb_substr('Annulation de la facture '.$locked->number.' — '.$locked->description, 0, 255),
            ], $locked->seller);

            $replacement = $this->create(Invoice::TYPE_INVOICE, [...$copy, ...$buyer]);

            return ['credit_note' => $creditNote, 'invoice' => $replacement];
        });
    }

    /**
     * Envoie le document au client par e-mail. Un échec d'envoi n'empêche jamais
     * la facture d'exister : elle reste consultable et renvoyable depuis l'admin.
     */
    public function send(Invoice $invoice): bool
    {
        if (blank($invoice->buyer_email)) {
            return false;
        }

        try {
            Mail::to($invoice->buyer_email)->send(new InvoiceIssued($invoice));
            $invoice->forceFill(['sent_at' => now()])->save();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Envoi de facture impossible.', ['invoice' => $invoice->number, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    /**
     * @return array<string, ?string>
     */
    public function sellerSnapshot(): array
    {
        return [
            'name' => $this->settings->get('seller_name') ?? (string) config('app.name'),
            'legal' => $this->settings->get('seller_legal'),
            'address' => $this->settings->get('seller_address'),
            'siret' => $this->settings->get('seller_siret'),
            'vat_number' => $this->settings->get('seller_vat_number'),
            'email' => $this->settings->get('seller_email'),
            'footer' => $this->settings->get('seller_footer'),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, ?string>|null  $seller
     */
    private function create(string $type, array $attributes, ?array $seller = null): Invoice
    {
        $series = (string) $this->settings->get($type === Invoice::TYPE_CREDIT_NOTE ? 'credit_series' : 'invoice_series');
        $year = (int) now()->format('Y');
        $sequence = (int) Invoice::query()->where('series', $series)->where('year', $year)->lockForUpdate()->max('sequence') + 1;

        return Invoice::query()->create([
            ...$attributes,
            'type' => $type,
            'series' => $series,
            'year' => $year,
            'sequence' => $sequence,
            'number' => sprintf('%s-%d-%06d', $series, $year, $sequence),
            'issued_at' => now(),
            'access_token' => Str::random(48),
            'seller' => $seller ?? $this->sellerSnapshot(),
        ]);
    }

    private function vatRateBasisPoints(): int
    {
        return (int) round(((float) str_replace(',', '.', (string) $this->settings->get('vat_rate', '0'))) * 100);
    }

    private function excludingVat(int $ttc, int $rateBasisPoints): int
    {
        return (int) round($ttc * 10000 / (10000 + $rateBasisPoints));
    }
}
