<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Un compte est soit acheteur, soit artiste. Un artiste peut aussi acheter :
 * l'espace acheteur est ouvert à tous les comptes, le studio aux seuls artistes.
 */
#[Fillable(['name', 'email', 'password', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_BUYER = 'buyer';

    public const ROLE_ARTIST = 'artist';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Valeurs par défaut, identiques à celles de la base, connues dès la création.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => self::ROLE_BUYER,
        'status' => self::STATUS_ACTIVE,
        'is_admin' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * @return HasOne<ArtistProfile, $this>
     */
    public function artistProfile(): HasOne
    {
        return $this->hasOne(ArtistProfile::class);
    }

    /**
     * @return HasMany<Sample, $this>
     */
    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class, 'artist_id');
    }

    /**
     * @return HasMany<Pack, $this>
     */
    public function packs(): HasMany
    {
        return $this->hasMany(Pack::class, 'artist_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<LibraryItem, $this>
     */
    public function libraryItems(): HasMany
    {
        return $this->hasMany(LibraryItem::class);
    }

    /**
     * @return HasMany<Payout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'artist_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function soldItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'artist_id');
    }

    /**
     * @return BelongsToMany<Sample, $this>
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Sample::class, 'favorites')->withTimestamps();
    }

    public function isArtist(): bool
    {
        return $this->role === self::ROLE_ARTIST;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function displayName(): string
    {
        return $this->artistProfile?->stage_name ?? $this->name;
    }

    /**
     * @return list<int>
     */
    public function ownedSampleIds(): array
    {
        return $this->libraryItems()->pluck('sample_id')->map(fn ($id): int => (int) $id)->all();
    }
}
