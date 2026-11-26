<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Un son du catalogue. L'original reste sur le disque privé ; seul l'aperçu
 * basse qualité est public.
 */
class Sample extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_BLOCKED = 'blocked';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'artist_id',
        'pack_id',
        'category_id',
        'title',
        'slug',
        'type',
        'bpm',
        'musical_key',
        'duration_ms',
        'tags',
        'file_path',
        'file_name',
        'file_format',
        'file_size',
        'preview_path',
        'waveform',
        'price_cents',
        'status',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'waveform' => 'array',
            'price_cents' => 'integer',
            'bpm' => 'integer',
            'duration_ms' => 'integer',
            'file_size' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    /**
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * @param  Builder<Sample>  $query
     * @return Builder<Sample>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('samples.status', self::STATUS_PUBLISHED)
            ->whereHas('artist', fn (Builder $q) => $q->where('status', User::STATUS_ACTIVE));
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function previewUrl(): ?string
    {
        return $this->preview_path ? Storage::disk('public')->url($this->preview_path) : null;
    }

    public function price(): string
    {
        return Money::format($this->price_cents);
    }

    public function typeLabel(): string
    {
        return config('soundbank.types.'.$this->type, $this->type);
    }

    public function duration(): string
    {
        if (! $this->duration_ms) {
            return '—';
        }

        $seconds = $this->duration_ms / 1000;

        return $seconds < 60
            ? number_format($seconds, 1, ',', '').' s'
            : sprintf('%d:%02d', intdiv((int) $seconds, 60), ((int) $seconds) % 60);
    }

    public function fileSize(): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $size = (float) $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < 3) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, $unit === 0 ? 0 : 1, ',', ' ').' '.$units[$unit];
    }

    /**
     * @return list<string>
     */
    public function tagList(): array
    {
        return collect(explode(',', (string) $this->tags))->map(fn ($tag) => trim($tag))->filter()->values()->all();
    }

    /**
     * Pics de la forme d'onde (0–1) pour l'affichage.
     *
     * @return list<float>
     */
    public function peaks(): array
    {
        return array_values(array_map('floatval', (array) ($this->waveform ?? [])));
    }

    /**
     * Nom du fichier livré à l'acheteur.
     */
    public function downloadName(): string
    {
        $artist = $this->artist?->displayName() ?? 'soundbank';

        return Str::slug($artist).'_'.Str::slug($this->title, '_').'.'.$this->file_format;
    }

    /**
     * Données passées au lecteur global (JSON).
     *
     * @return array<string, mixed>
     */
    public function playerPayload(): array
    {
        return [
            'id' => $this->getKey(),
            'title' => $this->title,
            'artist' => $this->artist?->displayName(),
            'src' => $this->previewUrl(),
            'url' => route('samples.show', $this),
            'peaks' => $this->peaks(),
            'play' => route('samples.play', $this),
        ];
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'sample';
        $slug = $base;

        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }
}
