<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Arborescence des sons (ex. Vocals › Female Vocals, Drums › Kicks).
 * Deux niveaux suffisent : une famille et ses sous-catégories.
 */
class Category extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'position',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('name');
    }

    /**
     * @return HasMany<Sample, $this>
     */
    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('position')->orderBy('name');
    }

    /**
     * Familles avec leurs sous-catégories (menus, filtres, formulaires), chargées
     * une fois par requête. Laravel 13 ne désérialise plus de modèles depuis le cache.
     *
     * @return Collection<int, Category>
     */
    public static function tree(): Collection
    {
        return once(fn () => static::query()->roots()->with('children')->get());
    }

    /**
     * Identifiant de la catégorie et de toutes ses sous-catégories.
     *
     * @return list<int>
     */
    public function selfAndDescendantIds(): array
    {
        return [$this->getKey(), ...$this->children()->pluck('id')->map(fn ($id): int => (int) $id)->all()];
    }

    public function fullName(): string
    {
        return $this->parent ? $this->parent->name.' › '.$this->name : $this->name;
    }
}
