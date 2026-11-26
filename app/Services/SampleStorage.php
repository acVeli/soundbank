<?php

namespace App\Services;

use App\Models\Sample;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Rangement des fichiers : l'original sur le disque privé (« local »), l'aperçu
 * et les visuels sur le disque public.
 */
class SampleStorage
{
    /**
     * @return array{file_path: string, file_name: string, file_format: string, file_size: int}
     */
    public function storeOriginal(UploadedFile $file, int $artistId): array
    {
        $extension = $this->extension($file);
        $path = $file->storeAs('samples/'.$artistId, Str::uuid().'.'.$extension, 'local');

        return [
            'file_path' => (string) $path,
            'file_name' => mb_substr($file->getClientOriginalName(), 0, 190),
            'file_format' => $extension,
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function storePreview(UploadedFile $file, int $artistId): string
    {
        $extension = in_array($this->extension($file), ['mp3', 'ogg'], true) ? $this->extension($file) : 'wav';

        return (string) $file->storeAs('previews/'.$artistId, Str::random(32).'.'.$extension, 'public');
    }

    public function storeImage(UploadedFile $file, string $folder): string
    {
        return (string) $file->store($folder, 'public');
    }

    public function deleteFiles(Sample $sample): void
    {
        if ($sample->preview_path) {
            Storage::disk('public')->delete($sample->preview_path);
        }
    }

    /**
     * Normalise et nettoie une forme d'onde envoyée par le navigateur.
     *
     * @return list<float>|null
     */
    public function sanitizeWaveform(?string $json): ?array
    {
        $values = json_decode((string) $json, true);

        if (! is_array($values) || $values === []) {
            return null;
        }

        return collect($values)
            ->take(200)
            ->map(fn ($value): float => round(max(0, min(1, (float) $value)), 3))
            ->values()
            ->all();
    }

    private function extension(UploadedFile $file): string
    {
        $extension = Str::lower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());

        return $extension === 'aif' ? 'aiff' : $extension;
    }
}
