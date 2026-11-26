<?php

namespace App\Http\Controllers;

use App\Models\Sample;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SampleController extends Controller
{
    public function show(Request $request, Sample $sample): View
    {
        $user = $request->user();
        $visible = $sample->isPublished() && $sample->artist?->isActive();
        abort_unless($visible || $user?->getKey() === $sample->artist_id || $user?->is_admin, 404);

        $sample->load(['artist.artistProfile', 'category.parent', 'pack']);

        return view('samples.show', [
            'sample' => $sample,
            'related' => Sample::query()->published()
                ->with(['artist.artistProfile', 'category'])
                ->whereKeyNot($sample->getKey())
                ->where(fn ($q) => $q->where('category_id', $sample->category_id)->orWhere('artist_id', $sample->artist_id))
                ->latest('published_at')
                ->limit(6)
                ->get(),
            'owned' => $user?->ownedSampleIds() ?? [],
            'favorites' => $user ? $user->favorites()->pluck('samples.id')->all() : [],
            'inPackCart' => $sample->pack_id !== null,
        ]);
    }

    /**
     * Compte une écoute d'aperçu (appelé par le lecteur).
     */
    public function play(Sample $sample): Response
    {
        if ($sample->isPublished()) {
            Sample::query()->whereKey($sample->getKey())->increment('plays_count');
        }

        return response()->noContent();
    }
}
