<?php

namespace App\Support;

final class Money
{
    public static function symbol(): string
    {
        return (string) config('workshop.currency.symbol', '€');
    }

    public static function format(float|string|int|null $amount, ?string $currency = null): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $currency ??= self::symbol();
        $formatted = number_format(
            (float) $amount,
            2,
            config('workshop.currency.decimal_separator', ','),
            config('workshop.currency.thousands_separator', '.'),
        );

        if (config('workshop.currency.position') === 'before') {
            return trim($currency.' '.$formatted);
        }

        return trim($formatted.' '.$currency);
    }
}
