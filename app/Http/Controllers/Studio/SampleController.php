<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LibraryItem;
use App\Models\Pack;
use App\Models\Sample;
use App\Models\User;
use App\Services\SampleStorage;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestion des sons d'un artiste : import en lot (un appel par fichier depuis
 * l'importeur), édition, publication et rattachement au catalogue (packs).
 */
class SampleController extends Controller
{
    public function __construct(private SampleStorage $storage) {}

    public function index(Request $request): View
    {
        $artist = $request->user();
        $search = trim($request->string('q')->value());
        $status = $request->string('statut')->value();
        $packId = $request->integer('pack') ?: null;

        $samples = $artist->samples()
            ->with(['category.parent', 'pack'])
            ->when($search !== '', fn (Builder $q) => $q->where('title', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%'))
            ->when(in_array($status, [Sample::STATUS_DRAFT, Sample::STATUS_PUBLISHED, Sample::STATUS_BLOCKED], true), fn (Builder $q) => $q->where('status', $status))
            ->when($packId === -1, fn (Builder $q) => $q->whereNull('pack_id'))
            ->when($packId > 0, fn (Builder $q) => $q->where('pack_id', $packId))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('studio.samples.index', [
            'samples' => $samples,
            'packs' => $artist->packs()->orderBy('title')->get(['id', 'title']),
            'search' => $search,
            'status' => $status,
            'packId' => $packId,
        ]);
    }

    public function create(Request $request): View
    {
        $artist = $request->user();

        return view('studio.samples.create', [
            'packs' => $artist->packs()->orderBy('title')->get(['id', 'title']),
            'tree' => Category::tree(),
            'selectedPack' => $request->integer('pack') ?: null,
        ]);
    }

    /**
     * Reçoit un fichier de l'importeur (appel AJAX) et crée le sample.
     */
    public function store(Request $request): JsonResponse
    {
        $artist = $request->user();
        $data = $this->validated($request, $artist, true);

        $file = $request->file('file');
        $original = $this->storage->storeOriginal($file, $artist->getKey());
        $preview = $request->hasFile('preview') ? $this->storage->storePreview($request->file('preview'), $artist->getKey()) : null;
        $publish = $request->boolean('publish') && filled($data['category_id'] ?? null);

        $sample = Sample::query()->create([
            ...$original,
            'artist_id' => $artist->getKey(),
            'pack_id' => $data['pack_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'title' => trim($data['title']),
            'slug' => Sample::uniqueSlug($artist->displayName().' '.$data['title']),
            'type' => $data['type'],
            'bpm' => $data['bpm'] ?? null,
            'musical_key' => $data['musical_key'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? null,
            'tags' => $this->tags($data['tags'] ?? null),
            'preview_path' => $preview,
            'waveform' => $this->storage->sanitizeWaveform($data['waveform'] ?? null),
            'price_cents' => Money::toCents($data['price']),
            'status' => $publish ? Sample::STATUS_PUBLISHED : Sample::STATUS_DRAFT,
            'published_at' => $publish ? now() : null,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $sample->getKey(),
            'status' => $sample->status,
            'edit' => route('studio.samples.edit', $sample),
        ], 201);
    }

    public function edit(Request $request, Sample $sample): View
    {
        $this->own($request, $sample);

        return view('studio.samples.edit', [
            'sample' => $sample->load(['category', 'pack']),
            'packs' => $request->user()->packs()->orderBy('title')->get(['id', 'title']),
            'tree' => Category::tree(),
            'sold' => LibraryItem::query()->where('sample_id', $sample->getKey())->count(),
        ]);
    }

    public function update(Request $request, Sample $sample): RedirectResponse
    {
        $this->own($request, $sample);
        $artist = $request->user();
        $data = $this->validated($request, $artist, false);

        if ($request->hasFile('preview')) {
            $this->storage->deleteFiles($sample);
            $sample->preview_path = $this->storage->storePreview($request->file('preview'), $artist->getKey());
            $sample->waveform = $this->storage->sanitizeWaveform($data['waveform'] ?? null) ?? $sample->waveform;
        }

        $wantsPublish = $request->boolean('publish');

        if ($wantsPublish && blank($data['category_id'] ?? null)) {
            return back()->withInput()->withErrors(['category_id' => 'Choisissez une catégorie avant de publier.']);
        }

        $status = $sample->status === Sample::STATUS_BLOCKED
            ? Sample::STATUS_BLOCKED
            : ($wantsPublish ? Sample::STATUS_PUBLISHED : Sample::STATUS_DRAFT);

        $sample->fill([
            'pack_id' => $data['pack_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'title' => trim($data['title']),
            'type' => $data['type'],
            'bpm' => $data['bpm'] ?? null,
            'musical_key' => $data['musical_key'] ?? null,
            'tags' => $this->tags($data['tags'] ?? null),
            'price_cents' => Money::toCents($data['price']),
            'status' => $status,
            'published_at' => $status === Sample::STATUS_PUBLISHED ? ($sample->published_at ?? now()) : $sample->published_at,
        ])->save();

        return redirect()->route('studio.samples.index')->with('status', '« '.$sample->title.' » enregistré.');
    }

    public function destroy(Request $request, Sample $sample): RedirectResponse
    {
        $this->own($request, $sample);
        $this->remove($sample);

        return redirect()->route('studio.samples.index')->with('status', 'Sample supprimé.');
    }

    /**
     * Actions groupées depuis la liste : publier, dépublier, ranger dans un pack, supprimer.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $artist = $request->user();
        $data = $request->validate([
            'action' => ['required', 'in:publish,unpublish,move,delete'],
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
            'pack_id' => ['nullable', 'integer', Rule::exists('packs', 'id')->where('artist_id', $artist->getKey())->whereNull('deleted_at')],
        ], [
            'ids.required' => 'Sélectionnez au moins un son.',
        ]);

        $samples = $artist->samples()->whereIn('id', $data['ids']);

        $message = match ($data['action']) {
            'publish' => $this->bulkPublish($samples),
            'unpublish' => (clone $samples)->where('status', Sample::STATUS_PUBLISHED)->update(['status' => Sample::STATUS_DRAFT]).' son(s) repassé(s) en brouillon.',
            'move' => (clone $samples)->update(['pack_id' => $data['pack_id'] ?? null]).' son(s) déplacé(s).',
            'delete' => $samples->get()->each(fn (Sample $sample) => $this->remove($sample))->count().' son(s) supprimé(s).',
        };

        return back()->with('status', $message);
    }

    /**
     * @param  HasMany<Sample, User>  $samples
     */
    private function bulkPublish($samples): string
    {
        $ready = (clone $samples)->where('status', Sample::STATUS_DRAFT)->whereNotNull('category_id');
        $count = (clone $ready)->count();
        (clone $ready)->whereNull('published_at')->update(['published_at' => now()]);
        $ready->update(['status' => Sample::STATUS_PUBLISHED]);
        $skipped = (clone $samples)->where('status', Sample::STATUS_DRAFT)->whereNull('category_id')->count();

        return $count.' son(s) publié(s).'.($skipped > 0 ? " {$skipped} sans catégorie restent en brouillon." : '');
    }

    /**
     * Un son déjà vendu reste téléchargeable par ses acheteurs (suppression douce) ;
     * un son jamais vendu est effacé avec ses fichiers.
     */
    private function remove(Sample $sample): void
    {
        $this->storage->deleteFiles($sample);

        if (LibraryItem::query()->where('sample_id', $sample->getKey())->exists()) {
            $sample->forceFill(['status' => Sample::STATUS_DRAFT, 'preview_path' => null])->save();
            $sample->delete();

            return;
        }

        Storage::disk('local')->delete($sample->file_path);
        $sample->forceDelete();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, User $artist, bool $withFile): array
    {
        $formats = implode(',', config('soundbank.audio_formats'));

        if (is_string($request->input('price'))) {
            $request->merge(['price' => str_replace([',', ' '], ['.', ''], $request->input('price'))]);
        }

        return $request->validate([
            'file' => $withFile
                ? ['required', 'file', 'max:'.config('soundbank.max_sample_kilobytes'), 'extensions:'.$formats, 'mimetypes:audio/*,video/ogg,application/octet-stream']
                : ['prohibited'],
            'preview' => ['nullable', 'file', 'max:'.config('soundbank.max_preview_kilobytes'), 'mimetypes:audio/*,video/ogg'],
            'waveform' => ['nullable', 'string', 'max:4000'],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
            'title' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'pack_id' => ['nullable', 'integer', Rule::exists('packs', 'id')->where('artist_id', $artist->getKey())->whereNull('deleted_at')],
            'type' => ['required', Rule::in(array_keys(config('soundbank.types')))],
            'bpm' => ['nullable', 'integer', 'min:20', 'max:400'],
            'musical_key' => ['nullable', Rule::in(config('soundbank.keys'))],
            'tags' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:'.(config('soundbank.min_price_cents') / 100), 'max:'.(config('soundbank.max_price_cents') / 100)],
        ], [
            'file.extensions' => 'Formats acceptés : '.strtoupper(str_replace(',', ', ', $formats)).'.',
            'file.max' => 'Fichier trop volumineux.',
            'price.min' => 'Prix minimum : '.Money::format(config('soundbank.min_price_cents')).'.',
        ]);
    }

    private function tags(?string $tags): ?string
    {
        $list = collect(explode(',', (string) $tags))
            ->map(fn (string $tag): string => mb_strtolower(trim($tag)))
            ->filter()
            ->unique()
            ->take(12);

        return $list->isEmpty() ? null : mb_substr($list->implode(', '), 0, 255);
    }

    private function own(Request $request, Sample $sample): void
    {
        abort_unless($sample->artist_id === $request->user()->getKey(), 404);
    }
}
