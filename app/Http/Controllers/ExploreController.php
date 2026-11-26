<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Exploration du catalogue : catégories, type, tempo, tonalité, recherche, tri.
 */
class ExploreController extends Controller
{
    public function index(Request $request, ?Category $category = null): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(config('soundbank.types')))],
            'bpm_min' => ['nullable', 'integer', 'min:20', 'max:400'],
            'bpm_max' => ['nullable', 'integer', 'min:20', 'max:400'],
            'key' => ['nullable', 'in:'.implode(',', config('soundbank.keys'))],
            'sort' => ['nullable', 'in:recent,popular,price_asc,price_desc'],
        ]);

        $category?->load(['parent', 'children']);
        $search = trim((string) ($filters['q'] ?? ''));

        $samples = Sample::query()->published()
            ->with(['artist.artistProfile', 'category', 'pack'])
            ->when($category, fn (Builder $q) => $q->whereIn('category_id', $category->selfAndDescendantIds()))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['bpm_min'] ?? null, fn (Builder $q, int|string $bpm) => $q->where('bpm', '>=', (int) $bpm))
            ->when($filters['bpm_max'] ?? null, fn (Builder $q, int|string $bpm) => $q->where('bpm', '<=', (int) $bpm))
            ->when($filters['key'] ?? null, fn (Builder $q, string $key) => $q->where('musical_key', $key))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', $like)
                    ->orWhere('tags', 'like', $like)
                    ->orWhereHas('artist.artistProfile', fn (Builder $a) => $a->where('stage_name', 'like', $like))
                    ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like)));
            });

        match ($filters['sort'] ?? 'recent') {
            'popular' => $samples->orderByDesc('sales_count')->orderByDesc('plays_count'),
            'price_asc' => $samples->orderBy('price_cents'),
            'price_desc' => $samples->orderByDesc('price_cents'),
            default => $samples->latest('published_at'),
        };

        $user = $request->user();

        return view('explore', [
            'category' => $category,
            'tree' => Category::tree(),
            'samples' => $samples->paginate(24)->withQueryString(),
            'filters' => $filters,
            'owned' => $user?->ownedSampleIds() ?? [],
            'favorites' => $user ? $user->favorites()->pluck('samples.id')->all() : [],
        ]);
    }
}
