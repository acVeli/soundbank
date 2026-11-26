<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\ArtistLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function index(Request $request, ArtistLedger $ledger): View
    {
        $search = trim($request->string('q')->value());

        $artists = User::query()
            ->where('role', User::ROLE_ARTIST)
            ->with('artistProfile')
            ->withCount('samples')
            ->withSum(['soldItems as earned_cents' => fn (Builder $q) => $q->whereHas('order', fn (Builder $o) => $o->where('status', Order::STATUS_PAID))], 'artist_earning_cents')
            ->when($search !== '', function (Builder $q) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(fn (Builder $inner) => $inner->where('email', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhereHas('artistProfile', fn (Builder $p) => $p->where('stage_name', 'like', $like)));
            })
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $artists->getCollection()->each(fn (User $artist) => $artist->setAttribute('available_cents', $ledger->availableCents($artist)));

        return view('admin.artists', ['artists' => $artists, 'search' => $search]);
    }

    public function verify(User $user): RedirectResponse
    {
        abort_unless($user->isArtist() && $user->artistProfile, 404);
        $user->artistProfile->update(['is_verified' => ! $user->artistProfile->is_verified]);

        return back()->with('status', $user->artistProfile->is_verified ? 'Artiste vérifié.' : 'Badge retiré.');
    }

    public function status(User $user): RedirectResponse
    {
        abort_unless($user->isArtist() && ! $user->is_admin, 404);
        $user->update(['status' => $user->isActive() ? User::STATUS_SUSPENDED : User::STATUS_ACTIVE]);

        return back()->with('status', $user->isActive()
            ? 'Compte réactivé : son catalogue est de nouveau visible.'
            : 'Compte suspendu : son catalogue est masqué, les achats déjà faits restent téléchargeables.');
    }
}
