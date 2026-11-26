<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Droit de téléchargement d'un sample par un acheteur, avec sa clé de licence.
 */
class LibraryItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'sample_id',
        'order_item_id',
        'license_key',
        'download_count',
        'last_downloaded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'download_count' => 'integer',
            'last_downloaded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Sample, $this>
     */
    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class)->withTrashed();
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
