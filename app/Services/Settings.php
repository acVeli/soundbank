<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Réglages modifiables depuis l'administration (commission, clés Stripe et PayPal,
 * facturation). Les valeurs sont chiffrées en base ; le cache ne contient que la
 * forme chiffrée.
 */
class Settings
{
    private const CACHE_KEY = 'soundbank.settings';

    /**
     * Clés dont la valeur ne doit jamais être réaffichée dans un formulaire.
     *
     * @var list<string>
     */
    public const SECRETS = [
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paypal_secret',
    ];

    /**
     * @var array<string, ?string>|null
     */
    private ?array $loaded = null;

    public function get(string $key, ?string $default = null): ?string
    {
        $values = $this->all();

        if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
            return $values[$key];
        }

        $fallback = config('soundbank.defaults.'.$key);

        return $fallback === null ? $default : (string) $fallback;
    }

    public function int(string $key): int
    {
        return (int) $this->get($key, '0');
    }

    public function filled(string $key): bool
    {
        return filled($this->get($key));
    }

    /**
     * @param  array<string, ?string>  $values
     */
    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    /**
     * @return array<string, ?string>
     */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        if (! Schema::hasTable('settings')) {
            return $this->loaded = [];
        }

        /** @var array<string, ?string> $encrypted */
        $encrypted = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(10),
            fn (): array => Setting::query()->toBase()->pluck('value', 'key')->all(),
        );

        $values = [];

        foreach ($encrypted as $key => $value) {
            try {
                $values[$key] = $value === null ? null : Crypt::decryptString($value);
            } catch (DecryptException) {
                $values[$key] = null;
            }
        }

        return $this->loaded = $values;
    }

    public function stripeEnabled(): bool
    {
        return $this->get('stripe_enabled') === '1' && $this->filled('stripe_secret_key');
    }

    public function paypalEnabled(): bool
    {
        return $this->get('paypal_enabled') === '1'
            && $this->filled('paypal_client_id')
            && $this->filled('paypal_secret');
    }

    public function anyPaymentEnabled(): bool
    {
        return $this->stripeEnabled() || $this->paypalEnabled();
    }

    /**
     * Commission de la plateforme en points de base (30 % = 3000).
     */
    public function commissionBasisPoints(): int
    {
        $percent = (float) str_replace(',', '.', (string) $this->get('commission_percent', '30'));

        return (int) max(0, min(10000, round($percent * 100)));
    }

    public function payoutMinCents(): int
    {
        return max(0, $this->int('payout_min_cents'));
    }

    public function currency(): string
    {
        return strtoupper((string) $this->get('currency', 'EUR'));
    }
}
