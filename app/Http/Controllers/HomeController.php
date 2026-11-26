<?php

namespace App\Http\Controllers;

use App\Models\ArtistProfile;
use App\Models\Category;
use App\Models\Pack;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $counts = Sample::query()->published()
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $families = Category::tree()->map(function (Category $family) use ($counts): Category {
            $family->setAttribute('samples_total', (int) $family->children->sum(fn (Category $child): int => (int) ($counts[$child->id] ?? 0)) + (int) ($counts[$family->id] ?? 0));

            return $family;
        });

        return view('home', [
            'latest' => Sample::query()->published()->with(['artist.artistProfile', 'category'])->latest('published_at')->limit(8)->get(),
            'trending' => Sample::query()->published()->with(['artist.artistProfile', 'category'])->orderByDesc('sales_count')->orderByDesc('plays_count')->limit(6)->get(),
            'packs' => Pack::query()->published()->with('artist.artistProfile')->withCount(['samples' => fn (Builder $q) => $q->published()])->latest('published_at')->limit(4)->get(),
            'artists' => ArtistProfile::query()
                ->whereHas('user', fn (Builder $q) => $q->where('status', User::STATUS_ACTIVE)->whereHas('samples', fn (Builder $s) => $s->published()))
                ->addSelect(['samples_count' => Sample::query()->selectRaw('COUNT(*)')
                    ->whereColumn('samples.artist_id', 'artist_profiles.user_id')
                    ->where('samples.status', Sample::STATUS_PUBLISHED)
                    ->whereNull('samples.deleted_at')])
                ->orderByDesc('is_verified')
                ->orderByDesc('samples_count')
                ->limit(6)
                ->get(),
            'families' => $families,
            'stats' => [
                'samples' => Sample::query()->published()->count(),
                'artists' => User::query()->where('role', User::ROLE_ARTIST)->where('status', User::STATUS_ACTIVE)->count(),
                'packs' => Pack::query()->published()->count(),
            ],
            'owned' => $request->user()?->ownedSampleIds() ?? [],
        ]);
    }
}
