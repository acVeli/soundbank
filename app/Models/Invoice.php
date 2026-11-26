<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Facture ou avoir. Une fois émis, un document n'est jamais modifié :
 * une correction passe par un avoir puis une nouvelle facture.
 */
class Invoice extends Model
{
    public const TYPE_INVOICE = 'invoice';

    public const TYPE_CREDIT_NOTE = 'credit_note';

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seller' => 'array',
            'lines' => 'array',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'sent_at' => 'datetime',
            'vat_rate_bp' => 'integer',
            'total_ht_cents' => 'integer',
            'total_vat_cents' => 'integer',
            'total_ttc_cents' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function creditedInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'credited_invoice_id');
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function creditNote(): HasOne
    {
        return $this->hasOne(self::class, 'credited_invoice_id');
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeInvoices(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INVOICE);
    }

    public function isCreditNote(): bool
    {
        return $this->type === self::TYPE_CREDIT_NOTE;
    }

    public function isCancelled(): bool
    {
        return ! $this->isCreditNote() && $this->creditNote()->exists();
    }

    public function label(): string
    {
        return $this->isCreditNote() ? 'Avoir' : 'Facture';
    }

    /**
     * Montant signé (négatif pour un avoir), pour l'affichage et l'export.
     */
    public function money(int $cents, bool $signed = true): string
    {
        return Money::format($signed && $this->isCreditNote() ? -$cents : $cents, $this->currency);
    }

    public function vatRateLabel(): string
    {
        return rtrim(rtrim(number_format($this->vat_rate_bp / 100, 2, ',', ''), '0'), ',').' %';
    }

    public function publicUrl(): string
    {
        return route('invoices.show', $this->access_token);
    }
}
