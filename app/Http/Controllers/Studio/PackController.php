<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Models\Sample;
use App\Services\SampleStorage;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Le catalogue de l'artiste : ses packs (collections) et les sons qui y sont rangés.
 */
class PackController extends Controller
{
    public function __construct(private SampleStorage $storage) {}

    public function index(Request $request): View
    {
        $artist = $request->user();

        return view('studio.packs.index', [
            'packs' => $artist->packs()
                ->withCount(['samples', 'samples as published_count' => fn ($q) => $q->where('status', Sample::STATUS_PUBLISHED)])
                ->latest()
                ->get(),
            'loose' => $artist->samples()->whereNull('pack_id')->count(),
        ]);
    }

    public function create(): View
    {
        return view('studio.packs.form', ['pack' => new Pack(['price_cents' => 1499])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $artist = $request->user();
        $data = $this->validated($request);

        $pack = $artist->packs()->create([
            'title' => trim($data['title']),
            'slug' => Pack::uniqueSlug($artist->displayName().' '.$data['title']),
            'description' => $data['description'] ?? null,
            'genre' => $data['genre'] ?? null,
            'price_cents' => Money::toCents($data['price']),
            'cover_path' => $request->hasFile('cover') ? $this->storage->storeImage($request->file('cover'), 'covers/'.$artist->getKey()) : null,
            'status' => Pack::STATUS_DRAFT,
        ]);

        return redirect()->route('studio.samples.create', ['pack' => $pack->getKey()])
            ->with('status', 'Pack « '.$pack->title.' » créé : importez maintenant ses sons.');
    }

    public function edit(Request $request, Pack $pack): View
    {
        $this->own($request, $pack);

        return view('studio.packs.form', [
            'pack' => $pack,
            'samples' => $pack->samples()->with('category')->orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, Pack $pack): RedirectResponse
    {
        $this->own($request, $pack);
        $data = $this->validated($request);
        $publish = $request->boolean('publish');

        if ($publish && ! $pack->samples()->where('status', Sample::STATUS_PUBLISHED)->exists()) {
            return back()->withInput()->withErrors(['publish' => 'Publiez au moins un son de ce pack avant de publier le pack.']);
        }

        if ($request->hasFile('cover')) {
            if ($pack->cover_path) {
                Storage::disk('public')->delete($pack->cover_path);
            }

            $pack->cover_path = $this->storage->storeImage($request->file('cover'), 'covers/'.$request->user()->getKey());
        }

        $pack->fill([
            'title' => trim($data['title']),
            'description' => $data['description'] ?? null,
            'genre' => $data['genre'] ?? null,
            'price_cents' => Money::toCents($data['price']),
            'status' => $publish ? Pack::STATUS_PUBLISHED : Pack::STATUS_DRAFT,
            'published_at' => $publish ? ($pack->published_at ?? now()) : $pack->published_at,
        ])->save();

        return redirect()->route('studio.packs.index')->with('status', 'Pack « '.$pack->title.' » enregistré.');
    }

    /**
     * Supprime le pack ; ses sons restent dans le catalogue, sans pack.
     */
    public function destroy(Request $request, Pack $pack): RedirectResponse
    {
        $this->own($request, $pack);
        $pack->samples()->update(['pack_id' => null]);
        $pack->update(['status' => Pack::STATUS_DRAFT]);
        $pack->delete();

        return redirect()->route('studio.packs.index')->with('status', 'Pack supprimé. Ses sons restent dans votre catalogue.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        if (is_string($request->input('price'))) {
            $request->merge(['price' => str_replace([',', ' '], ['.', ''], $request->input('price'))]);
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'genre' => ['nullable', Rule::in(config('soundbank.genres'))],
            'price' => ['required', 'numeric', 'min:'.(config('soundbank.min_price_cents') / 100), 'max:'.(config('soundbank.max_price_cents') / 100)],
            'cover' => ['nullable', 'image', 'max:5120', 'dimensions:min_width=300,min_height=300'],
        ], [
            'cover.dimensions' => 'La pochette doit faire au moins 300 × 300 px.',
        ]);
    }

    private function own(Request $request, Pack $pack): void
    {
        abort_unless($pack->artist_id === $request->user()->getKey(), 404);
    }
}
