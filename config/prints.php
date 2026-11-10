<?php

/*
 * Print sale pricing (see App\Prints\PrintPricing).
 */
return [
    // Used when neither the photographer nor the catalogue sets a price.
    'fallback_retail_price' => (float) env('PRINT_FALLBACK_RETAIL_PRICE', 29.99),

    // Card fees assumed when working out the minimum retail price. The default
    // is Stripe's European-card rate, so most UK and EU sales are covered.
    'stripe_fee_percent' => (float) env('PRINT_STRIPE_FEE_PERCENT', 2.5),
    'stripe_fee_fixed' => (float) env('PRINT_STRIPE_FEE_FIXED', 0.20),

    // Where Prodigi costs are quoted to.
    'quote_country' => env('PRINT_QUOTE_COUNTRY', 'GB'),
    'quote_currency' => env('PRINT_QUOTE_CURRENCY', 'GBP'),
    'shipping_method' => env('PRINT_SHIPPING_METHOD', 'Standard'),
];
