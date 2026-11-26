<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de commande. Le titre, le prix et la part de l'artiste sont figés à
 * l'achat : une modification ultérieure du catalogue ne change pas la vente.
 */
class OrderItem extends Model
{
    public const TYPE_SAMPLE = 'sample';

    public const TYPE_PACK = 'pack';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'item_type',
        'sample_id',
        'pack_id',
        'artist_id',
        'title',
        'artist_name',
        'price_cents',
        'commission_bp',
        'artist_earning_cents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'commission_bp' => 'integer',
            'artist_earning_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Sample, $this>
     */
    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function price(): string
    {
        return Money::format($this->price_cents);
    }

    public function earning(): string
    {
        return Money::format($this->artist_earning_cents);
    }

    public function typeLabel(): string
    {
        return $this->item_type === self::TYPE_PACK ? 'Pack' : 'Sample';
    }
}
