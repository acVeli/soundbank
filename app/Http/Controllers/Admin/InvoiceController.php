<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceIssuer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $search = trim((string) $request->query('q', ''));
        $query = $this->filtered($from, $to, $search);

        $totals = (clone $query)->selectRaw(
            "SUM(CASE WHEN type = 'credit_note' THEN -total_ttc_cents ELSE total_ttc_cents END) AS ttc,
             SUM(CASE WHEN type = 'credit_note' THEN -total_ht_cents ELSE total_ht_cents END) AS ht,
             SUM(CASE WHEN type = 'credit_note' THEN -total_vat_cents ELSE total_vat_cents END) AS vat",
        )->first();

        return view('admin.invoices', [
            'invoices' => $query->with('creditNote:id,credited_invoice_id,number')->latest('issued_at')->latest('id')->paginate(50)->withQueryString(),
            'from' => $from,
            'to' => $to,
            'search' => $search,
            'totals' => [
                'ttc' => (int) ($totals->ttc ?? 0),
                'ht' => (int) ($totals->ht ?? 0),
                'vat' => (int) ($totals->vat ?? 0),
            ],
        ]);
    }

    public function show(Invoice $invoice): View
    {
        return view('invoices.show', [
            'invoice' => $invoice,
            'admin' => true,
            'backUrl' => route('admin.invoices'),
        ]);
    }

    public function send(Invoice $invoice, InvoiceIssuer $issuer): RedirectResponse
    {
        $sent = $issuer->send($invoice);

        return redirect()->route('admin.invoices.show', $invoice)->with('status', $sent
            ? 'Le document a été envoyé à '.$invoice->buyer_email.'.'
            : 'L’envoi a échoué : vérifiez la configuration e-mail (MAIL_*) du serveur.');
    }

    public function editCorrection(Invoice $invoice): View
    {
        abort_if($invoice->isCreditNote() || $invoice->isCancelled(), 404);

        return view('admin.invoice-correct', ['invoice' => $invoice]);
    }

    public function storeCorrection(Request $request, Invoice $invoice, InvoiceIssuer $issuer): RedirectResponse
    {
        abort_if($invoice->isCreditNote() || $invoice->isCancelled(), 404);

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:120'],
            'buyer_email' => ['nullable', 'email', 'max:190'],
            'buyer_company' => ['nullable', 'string', 'max:160'],
            'buyer_address' => ['nullable', 'string', 'max:400'],
            'buyer_vat' => ['nullable', 'string', 'max:40'],
        ], [
            'buyer_name.required' => 'Le nom du client est obligatoire.',
        ]);

        $buyer = array_map(fn ($value) => filled($value) ? trim((string) $value) : null, $data);
        $issued = $issuer->correct($invoice, $buyer);

        if ($request->boolean('send')) {
            $issuer->send($issued['credit_note']);
            $issuer->send($issued['invoice']);
        }

        return redirect()
            ->route('admin.invoices.show', $issued['invoice'])
            ->with('status', "Avoir {$issued['credit_note']->number} émis et nouvelle facture {$issued['invoice']->number} créée.");
    }

    /**
     * Export CSV (séparateur « ; », compatible Excel) pour le comptable.
     */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $invoices = $this->filtered($from, $to, '')->with('creditedInvoice:id,number')->orderBy('issued_at')->orderBy('id')->get();
        $filename = 'factures-'.$from->format('Y-m-d').'-'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($invoices): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Numéro', 'Type', 'Date', 'Client', 'Entreprise', 'E-mail', 'N° TVA client', 'Désignation', 'Nb articles', 'Total HT', 'Taux TVA', 'TVA', 'Total TTC', 'Devise', 'Moyen de paiement', 'Référence paiement', 'Facture annulée'], ';');

            foreach ($invoices as $invoice) {
                $sign = $invoice->isCreditNote() ? -1 : 1;
                $amount = fn (int $cents): string => number_format($sign * $cents / 100, 2, ',', '');

                fputcsv($out, [
                    $invoice->number,
                    $invoice->label(),
                    $invoice->issued_at->format('d/m/Y'),
                    $invoice->buyer_name,
                    $invoice->buyer_company,
                    $invoice->buyer_email,
                    $invoice->buyer_vat,
                    $invoice->description,
                    count((array) $invoice->lines),
                    $amount($invoice->total_ht_cents),
                    $invoice->vatRateLabel(),
                    $amount($invoice->total_vat_cents),
                    $amount($invoice->total_ttc_cents),
                    $invoice->currency,
                    $invoice->payment_method,
                    $invoice->payment_reference,
                    $invoice->creditedInvoice?->number,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $parse = function (string $key, Carbon $fallback) use ($request): Carbon {
            try {
                return filled($request->query($key)) ? Carbon::createFromFormat('Y-m-d', (string) $request->query($key)) : $fallback;
            } catch (\Throwable) {
                return $fallback;
            }
        };

        return [
            $parse('du', now()->startOfMonth())->startOfDay(),
            $parse('au', now()->endOfMonth())->endOfDay(),
        ];
    }

    /**
     * @return Builder<Invoice>
     */
    private function filtered(Carbon $from, Carbon $to, string $search): Builder
    {
        return Invoice::query()
            ->whereBetween('issued_at', [$from, $to])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $query->where(fn (Builder $inner) => $inner
                    ->where('number', 'like', $like)
                    ->orWhere('buyer_name', 'like', $like)
                    ->orWhere('buyer_company', 'like', $like)
                    ->orWhere('buyer_email', 'like', $like));
            });
    }
}
