<?php

namespace App\Services;

use App\Models\Pack;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Panier en session : une liste de références « sample:ID » / « pack:ID ».
 * Les articles sont relus en base à chaque affichage : un son dépublié ou déjà
 * acheté disparaît du panier tout seul.
 */
class Cart
{
    private const KEY = 'cart';

    public function __construct(private Session $session) {}

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_values(array_unique((array) $this->session->get(self::KEY, [])));
    }

    public function has(string $type, int $id): bool
    {
        return in_array($type.':'.$id, $this->keys(), true);
    }

    /**
     * Ajoute un article. Retourne un message d'erreur si l'ajout est refusé.
     */
    public function add(Sample|Pack $item, ?User $user): ?string
    {
        if (! $item->isPublished()) {
            return 'Cet article n’est plus disponible.';
        }

        if ($user !== null && $item->artist_id === $user->getKey()) {
            return 'Vous ne pouvez pas acheter vos propres sons.';
        }

        $keys = $this->keys();

        if ($item instanceof Sample) {
            if ($user !== null && $user->libraryItems()->where('sample_id', $item->getKey())->exists()) {
                return 'Ce sample est déjà dans votre bibliothèque.';
            }

            if ($item->pack_id !== null && in_array('pack:'.$item->pack_id, $keys, true)) {
                return 'Ce sample est déjà inclus dans un pack de votre panier.';
            }

            $keys[] = 'sample:'.$item->getKey();
        } else {
            $packSampleKeys = $item->samples()->pluck('id')->map(fn ($id): string => 'sample:'.$id)->all();
            $keys = array_values(array_diff($keys, $packSampleKeys));
            $keys[] = 'pack:'.$item->getKey();
        }

        $this->session->put(self::KEY, array_values(array_unique($keys)));

        return null;
    }

    public function remove(string $type, int $id): void
    {
        $this->session->put(self::KEY, array_values(array_diff($this->keys(), [$type.':'.$id])));
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function count(): int
    {
        return count($this->keys());
    }

    /**
     * Articles valides du panier.
     *
     * @return Collection<int, array{type: string, model: Sample|Pack, price_cents: int}>
     */
    public function lines(?User $user = null): Collection
    {
        $keys = $this->keys();
        $sampleIds = [];
        $packIds = [];

        foreach ($keys as $key) {
            [$type, $id] = array_pad(explode(':', $key, 2), 2, '0');

            if ($type === 'sample') {
                $sampleIds[] = (int) $id;
            } elseif ($type === 'pack') {
                $packIds[] = (int) $id;
            }
        }

        $owned = $user?->ownedSampleIds() ?? [];

        $samples = Sample::query()->published()->with(['artist.artistProfile', 'category'])->whereIn('id', $sampleIds)
            ->when($user, fn ($q) => $q->where('artist_id', '!=', $user->getKey()))
            ->whereNotIn('id', $owned)
            ->get()
            ->map(fn (Sample $sample): array => ['type' => 'sample', 'model' => $sample, 'price_cents' => $sample->price_cents]);

        $packs = Pack::query()->published()->with('artist.artistProfile')->withCount(['samples' => fn ($q) => $q->published()])->whereIn('id', $packIds)
            ->when($user, fn ($q) => $q->where('artist_id', '!=', $user->getKey()))
            ->get()
            ->map(fn (Pack $pack): array => ['type' => 'pack', 'model' => $pack, 'price_cents' => $pack->price_cents]);

        $lines = $packs->concat($samples)->values();
        $validKeys = $lines->map(fn (array $line): string => $line['type'].':'.$line['model']->getKey())->all();

        if (count($validKeys) !== count($keys)) {
            $this->session->put(self::KEY, $validKeys);
        }

        return $lines;
    }

    /**
     * @param  Collection<int, array{type: string, model: Sample|Pack, price_cents: int}>  $lines
     */
    public function total(Collection $lines): int
    {
        return (int) $lines->sum('price_cents');
    }
}
