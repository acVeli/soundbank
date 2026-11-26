<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Versements aux artistes : l'admin effectue le virement / paiement PayPal,
 * puis marque la demande comme versée (avec sa référence) ou la refuse.
 */
class PayoutController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('statut')->value() ?: Payout::STATUS_REQUESTED;

        return view('admin.payouts', [
            'payouts' => Payout::query()
                ->with('artist.artistProfile')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(40)
                ->withQueryString(),
            'status' => $status,
            'totals' => [
                'requested' => (int) Payout::query()->where('status', Payout::STATUS_REQUESTED)->sum('amount_cents'),
                'paid' => (int) Payout::query()->where('status', Payout::STATUS_PAID)->sum('amount_cents'),
            ],
        ]);
    }

    public function update(Request $request, Payout $payout): RedirectResponse
    {
        abort_unless($payout->status === Payout::STATUS_REQUESTED, 409, 'Cette demande a déjà été traitée.');

        $data = $request->validate([
            'decision' => ['required', 'in:paid,rejected'],
            'reference' => ['nullable', 'required_if:decision,paid', 'string', 'max:190'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ], [
            'reference.required_if' => 'Indiquez la référence du virement ou de la transaction PayPal.',
        ]);

        $payout->update([
            'status' => $data['decision'] === 'paid' ? Payout::STATUS_PAID : Payout::STATUS_REJECTED,
            'reference' => $data['reference'] ?? null,
            'admin_note' => $data['admin_note'] ?? null,
            'processed_at' => now(),
        ]);

        return back()->with('status', $data['decision'] === 'paid'
            ? 'Versement de '.$payout->amount().' marqué comme effectué.'
            : 'Demande refusée : le montant revient dans le solde de l’artiste.');
    }
}
