<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'currency' => 'EUR',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'user_id',
        'status',
        'provider',
        'reference',
        'total_cents',
        'currency',
        'billing_name',
        'billing_email',
        'billing_company',
        'billing_address',
        'billing_vat',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->number ??= static::newNumber();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->where('type', Invoice::TYPE_INVOICE)->latestOfMany();
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function total(): string
    {
        return Money::format($this->total_cents, $this->currency);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Payée',
            self::STATUS_FAILED => 'Échouée',
            default => 'En attente',
        };
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            'stripe' => 'Carte (Stripe)',
            'paypal' => 'PayPal',
            default => '—',
        };
    }

    public static function newNumber(): string
    {
        do {
            $number = 'SB-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (static::query()->where('number', $number)->exists());

        return $number;
    }
}
