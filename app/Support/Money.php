<?php

namespace App\Support;

/**
 * Formatage des montants stockés en centimes.
 */
class Money
{
    public static function format(int $cents, string $currency = 'EUR'): string
    {
        $symbol = match (strtoupper($currency)) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => strtoupper($currency),
        };

        return number_format($cents / 100, 2, ',', ' ').' '.$symbol;
    }

    /**
     * Convertit une saisie en euros (« 4,99 » ou « 4.99 ») en centimes.
     */
    public static function toCents(string|float|int|null $value): int
    {
        return (int) round(((float) str_replace([',', ' '], ['.', ''], (string) $value)) * 100);
    }

    public static function input(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
