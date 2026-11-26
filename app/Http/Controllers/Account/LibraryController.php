<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LibraryItem;
use App\Services\AudioWatermark;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bibliothèque de l'acheteur : sons achetés, téléchargement de l'original et
 * certificat de licence.
 */
class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->value());
        $categoryId = $request->integer('categorie') ?: null;

        $items = $request->user()->libraryItems()
            ->with(['sample.artist.artistProfile', 'sample.category', 'sample.pack'])
            ->when($search !== '', fn (Builder $q) => $q->whereHas('sample', fn (Builder $s) => $s->withTrashed()
                ->where('title', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%')))
            ->when($categoryId, function (Builder $q) use ($categoryId): void {
                $category = Category::query()->find($categoryId);
                $ids = $category?->selfAndDescendantIds() ?? [$categoryId];
                $q->whereHas('sample', fn (Builder $s) => $s->withTrashed()->whereIn('category_id', $ids));
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('account.library', [
            'items' => $items,
            'search' => $search,
            'categoryId' => $categoryId,
            'tree' => Category::tree(),
        ]);
    }

    /**
     * Téléchargement de l'original, filigrané au nom de l'acheteur (WAV / AIFF).
     */
    public function download(Request $request, LibraryItem $libraryItem, AudioWatermark $watermark): StreamedResponse
    {
        abort_unless($libraryItem->user_id === $request->user()->getKey(), 404);

        $sample = $libraryItem->sample;
        abort_if($sample === null || ! Storage::disk('local')->exists($sample->file_path), 404, 'Fichier introuvable : contactez le support.');

        $libraryItem->increment('download_count', 1, ['last_downloaded_at' => now()]);
        $path = Storage::disk('local')->path($sample->file_path);

        return response()->streamDownload(
            function () use ($watermark, $path, $libraryItem): void {
                $out = fopen('php://output', 'wb');
                $watermark->write($path, $libraryItem, $out);
                fclose($out);
            },
            $sample->downloadName(),
            [
                'Content-Type' => Storage::disk('local')->mimeType($sample->file_path) ?: 'application/octet-stream',
                'Content-Length' => (string) $watermark->length($path, $libraryItem),
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    public function license(Request $request, LibraryItem $libraryItem): View
    {
        abort_unless($libraryItem->user_id === $request->user()->getKey(), 404);

        return view('account.license', [
            'item' => $libraryItem->load(['sample.artist.artistProfile', 'orderItem.order', 'user']),
        ]);
    }
}
