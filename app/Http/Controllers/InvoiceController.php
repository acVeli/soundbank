<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\View\View;

/**
 * Consultation d'une facture par son lien personnel (envoyé par e-mail).
 */
class InvoiceController extends Controller
{
    public function show(string $token): View
    {
        $invoice = Invoice::query()->where('access_token', $token)->firstOrFail();

        return view('invoices.show', [
            'invoice' => $invoice,
            'backUrl' => auth()->check() ? route('account.orders') : null,
        ]);
    }
}
