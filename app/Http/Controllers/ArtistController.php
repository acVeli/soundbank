<?php

namespace App\Http\Controllers;

use App\Models\ArtistProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->value());

        return view('artists.index', [
            'artists' => ArtistProfile::query()
                ->whereHas('user', fn (Builder $q) => $q->where('status', User::STATUS_ACTIVE)->whereHas('samples', fn (Builder $s) => $s->published()))
                ->when($search !== '', fn (Builder $q) => $q->where('stage_name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%'))
                ->orderByDesc('is_verified')
                ->orderBy('stage_name')
                ->paginate(24)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function show(Request $request, ArtistProfile $artistProfile): View
    {
        $artist = $artistProfile->user;
        abort_unless($artist->isActive() || $request->user()?->is_admin, 404);
        $user = $request->user();

        return view('artists.show', [
            'profile' => $artistProfile,
            'packs' => $artist->packs()->published()->withCount(['samples' => fn (Builder $q) => $q->published()])->latest('published_at')->get(),
            'samples' => $artist->samples()->published()->with(['artist.artistProfile', 'category'])->latest('published_at')->paginate(20),
            'totals' => [
                'samples' => $artist->samples()->published()->count(),
                'plays' => (int) $artist->samples()->published()->sum('plays_count'),
            ],
            'owned' => $user?->ownedSampleIds() ?? [],
            'favorites' => $user ? $user->favorites()->pluck('samples.id')->all() : [],
        ]);
    }
}
