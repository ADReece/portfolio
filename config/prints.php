<?php

/*
 * Print sale pricing (see App\Prints\PrintPricing).
 */
return [
    // Used when neither the photographer nor the catalogue sets a price.
    'fallback_retail_price' => (float) env('PRINT_FALLBACK_RETAIL_PRICE', 29.99),

    // Card fees, paid by the platform out of its fee. The minimum retail price
    // keeps the platform fee above them. Default: Stripe's European-card rate.
    'stripe_fee_percent' => (float) env('PRINT_STRIPE_FEE_PERCENT', 2.5),
    'stripe_fee_fixed' => (float) env('PRINT_STRIPE_FEE_FIXED', 0.20),

    // Where Prodigi costs are quoted to.
    'quote_country' => env('PRINT_QUOTE_COUNTRY', 'GB'),
    'quote_currency' => env('PRINT_QUOTE_CURRENCY', 'GBP'),
    'shipping_method' => env('PRINT_SHIPPING_METHOD', 'Standard'),

    // Bulk adds (App\Prints\CatalogueImporter): SKU lookups per minute, so a
    // big CSV or discovery scan doesn't hammer Prodigi's API.
    'import_per_minute' => (int) env('PRODIGI_IMPORT_PER_MINUTE', 60),
    'import_max_skus' => 2000,

    // "Discover" tries each product family with each size, as
    // GLOBAL-{family}-{size}, and adds the ones Prodigi recognises. Lookups are
    // free, so SKUs that don't exist just count as "not found". Add families
    // or sizes here when Prodigi adds products.
    'discovery' => [
        'families' => [
            'FAP' => 'Fine art prints (enhanced matte art paper)',
            'HPR' => 'Fine art prints (Hahnemühle Photo Rag)',
            'HGE' => 'Fine art prints (Hahnemühle German Etching)',
            'BAP' => 'Fine art prints (baryta)',
            'PHO' => 'Photo prints',
            'LPP' => 'Photo prints (lustre)',
            'CAN' => 'Canvas',
            'SLIMCAN' => 'Canvas (slim)',
            'CFP' => 'Classic frames',
            'CFPM' => 'Classic frames with mount',
            'BOX' => 'Box frames',
            'BOXM' => 'Box frames with mount',
            'ACRY' => 'Acrylic prints',
        ],
        // Width × height in inches; both orientations are tried.
        'inch_sizes' => [
            '4X6', '5X7', '6X8', '8X8', '8X10', '8X12', '10X10', '10X12', '11X14', '12X12',
            '12X16', '12X18', '16X16', '16X20', '16X24', '18X24', '20X20', '20X24', '20X28',
            '20X30', '24X24', '24X30', '24X32', '24X36', '28X40', '30X40',
        ],
        'paper_sizes' => ['A5', 'A4', 'A3', 'A2', 'A1', 'A0'],
    ],
];
