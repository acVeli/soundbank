<?php

namespace App\Http\Controllers;

use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackController extends Controller
{
    public function index(Request $request): View
    {
        $genre = $request->string('genre')->value();

        return view('packs.index', [
            'packs' => Pack::query()->published()
                ->with('artist.artistProfile')
                ->withCount(['samples' => fn (Builder $q) => $q->published()])
                ->when(in_array($genre, config('soundbank.genres'), true), fn (Builder $q) => $q->where('genre', $genre))
                ->latest('published_at')
                ->paginate(18)
                ->withQueryString(),
            'genre' => $genre,
        ]);
    }

    public function show(Request $request, Pack $pack): View
    {
        $user = $request->user();
        abort_unless(($pack->isPublished() && $pack->artist?->isActive()) || $user?->getKey() === $pack->artist_id || $user?->is_admin, 404);

        $samples = $pack->samples()->published()->with(['artist.artistProfile', 'category'])->orderBy('title')->get();
        $owned = $user?->ownedSampleIds() ?? [];

        return view('packs.show', [
            'pack' => $pack->load('artist.artistProfile'),
            'samples' => $samples,
            'unitTotal' => (int) $samples->sum('price_cents'),
            'owned' => $owned,
            'ownsAll' => $samples->isNotEmpty() && $samples->every(fn ($sample): bool => in_array($sample->id, $owned, true)),
            'favorites' => $user ? $user->favorites()->pluck('samples.id')->all() : [],
        ]);
    }
}
