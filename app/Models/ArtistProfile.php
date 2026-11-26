<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArtistProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'stage_name',
        'slug',
        'bio',
        'avatar_path',
        'banner_path',
        'location',
        'website',
        'instagram',
        'soundcloud',
        'payout_method',
        'payout_email',
        'payout_iban',
        'payout_holder',
        'is_verified',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'payout_iban' => 'encrypted',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function initials(): string
    {
        return Str::upper(Str::substr(Str::of($this->stage_name)->replaceMatches('/[^\pL\pN ]/u', '')->squish()->value() ?: '?', 0, 2));
    }

    /**
     * Destination du versement, telle qu'affichée à l'admin.
     */
    public function payoutDestination(): ?string
    {
        if ($this->payout_method === 'bank') {
            return filled($this->payout_iban) ? trim(($this->payout_holder ?? '').' — '.$this->payout_iban, ' —') : null;
        }

        return $this->payout_email;
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'artiste';
        $slug = $base;
        $i = 2;

        while (static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
