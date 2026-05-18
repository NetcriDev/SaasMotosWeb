<?php

use App\Support\Money;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'workshop.currency.symbol' => '$',
        'workshop.currency.position' => 'before',
        'workshop.currency.decimal_separator' => '.',
        'workshop.currency.thousands_separator' => ',',
    ]);
});

it('formats amounts in usd style by default', function (): void {
    expect(Money::format(149))->toBe('$ 149.00')
        ->and(Money::format(1250.5))->toBe('$ 1,250.50');
});

it('returns placeholder for null amounts', function (): void {
    expect(Money::format(null))->toBe('—');
});

it('formats with symbol after when configured', function (): void {
    config([
        'workshop.currency.symbol' => '€',
        'workshop.currency.position' => 'after',
        'workshop.currency.decimal_separator' => ',',
        'workshop.currency.thousands_separator' => '.',
    ]);

    expect(Money::format(149))->toBe('149,00 €');
});
