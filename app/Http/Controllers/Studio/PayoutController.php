<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\ArtistLedger;
use App\Services\Settings;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Demandes de versement : l'artiste demande tout ou partie de son solde,
 * l'administration effectue le virement et le marque comme versé.
 */
class PayoutController extends Controller
{
    public function index(Request $request, ArtistLedger $ledger, Settings $settings): View
    {
        $artist = $request->user();

        return view('studio.payouts', [
            'profile' => $artist->artistProfile,
            'payouts' => $artist->payouts()->latest()->paginate(20),
            'available' => $ledger->availableCents($artist),
            'earned' => $ledger->earnedCents($artist),
            'paidOut' => $ledger->paidOutCents($artist),
            'pending' => $ledger->pendingPayoutCents($artist),
            'minimum' => $settings->payoutMinCents(),
        ]);
    }

    public function store(Request $request, ArtistLedger $ledger, Settings $settings): RedirectResponse
    {
        $artist = $request->user();
        $profile = $artist->artistProfile;
        $destination = $profile->payoutDestination();

        if (blank($destination)) {
            return redirect()->route('studio.profile')->with('error', 'Renseignez d’abord vos coordonnées de versement.');
        }

        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);
        $amount = Money::toCents($data['amount']);

        $error = DB::transaction(function () use ($artist, $ledger, $settings, $amount, $profile, $destination): ?string {
            $artist->newQuery()->whereKey($artist->getKey())->lockForUpdate()->first();
            $available = $ledger->availableCents($artist);

            if ($amount < $settings->payoutMinCents()) {
                return 'Le versement minimum est de '.Money::format($settings->payoutMinCents()).'.';
            }

            if ($amount > $available) {
                return 'Montant supérieur à votre solde disponible ('.Money::format($available).').';
            }

            $artist->payouts()->create([
                'amount_cents' => $amount,
                'method' => $profile->payout_method,
                'destination' => mb_substr((string) $destination, 0, 255),
                'status' => Payout::STATUS_REQUESTED,
            ]);

            return null;
        });

        return redirect()->route('studio.payouts')->with(
            $error ? 'error' : 'status',
            $error ?? 'Demande de versement de '.Money::format($amount).' envoyée.',
        );
    }
}
