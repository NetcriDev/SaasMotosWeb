<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Moneda del taller
    |--------------------------------------------------------------------------
    |
    | WORKSHOP_CURRENCY_SYMBOL: símbolo mostrado ($, €, S/, MXN, etc.)
    | WORKSHOP_CURRENCY_POSITION: "before" → $149.00 | "after" → 149,00 €
    |
    */

    'currency' => [
        'symbol' => env('WORKSHOP_CURRENCY_SYMBOL', '$'),
        'code' => env('WORKSHOP_CURRENCY_CODE', 'USD'),
        'position' => env('WORKSHOP_CURRENCY_POSITION', 'before'),
        'decimal_separator' => env('WORKSHOP_DECIMAL_SEPARATOR', '.'),
        'thousands_separator' => env('WORKSHOP_THOUSANDS_SEPARATOR', ','),
    ],

];
