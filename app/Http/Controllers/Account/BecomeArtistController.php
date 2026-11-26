<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Un acheteur peut ouvrir un studio : son compte devient artiste (il garde ses achats).
 */
class BecomeArtistController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->isArtist() && $request->user()->artistProfile) {
            return redirect()->route('studio.dashboard');
        }

        return view('account.become-artist');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stage_name' => ['required', 'string', 'min:2', 'max:80'],
            'terms' => ['accepted'],
        ], [
            'stage_name.required' => 'Choisissez votre nom d’artiste.',
            'terms.accepted' => 'Vous devez accepter les conditions vendeur.',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $data): void {
            $user->update(['role' => User::ROLE_ARTIST]);
            $user->artistProfile()->firstOrCreate([], [
                'stage_name' => trim($data['stage_name']),
                'slug' => ArtistProfile::uniqueSlug($data['stage_name']),
            ]);
        });

        return redirect()->route('studio.samples.create')->with('status', 'Votre studio est ouvert : importez vos premiers sons.');
    }
}
