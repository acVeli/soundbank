<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de versement d'un artiste, traitée manuellement depuis l'admin.
 */
class Payout extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'artist_id',
        'amount_cents',
        'method',
        'destination',
        'status',
        'reference',
        'admin_note',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function amount(): string
    {
        return Money::format($this->amount_cents);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Versé',
            self::STATUS_REJECTED => 'Refusé',
            default => 'Demandé',
        };
    }

    public function methodLabel(): string
    {
        return $this->method === 'bank' ? 'Virement' : 'PayPal';
    }
}
