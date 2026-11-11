<?php

namespace App\Http\Controllers;

use App\Downloads\DigitalPricing;
use App\Downloads\DigitalSales;
use App\Support\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Photographers' download prices: account defaults (per photo and bundles)
 * at Selling → Photo downloads, and per collection whether downloads are on
 * sale, with an optional per-photo price and whole-collection price.
 */
class DigitalSalesSettingsController extends Controller
{
    public function edit(Request $request, DigitalPricing $pricing): View
    {
        $user = $request->user();

        return view('profile.digital-sales', [
            'user' => $user,
            'bundles' => DigitalPricing::normaliseBundles($user->digital_bundles ?? []),
            'minimum' => $pricing->minimumPrice(),
            'collections' => $user->collections()->withCount(['photos' => fn ($q) => $q->ready()])->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, DigitalPricing $pricing): RedirectResponse
    {
        $min = $pricing->minimumPrice();
        $validated = $request->validate([
            'digital_photo_price' => ['nullable', 'numeric', 'min:'.$min, 'max:9999.99'],
            'bundles' => ['nullable', 'array', 'max:'.DigitalPricing::MAX_BUNDLES],
            'bundles.*.count' => ['nullable', 'integer', 'min:2', 'max:1000'],
            'bundles.*.price' => ['nullable', 'numeric', 'min:'.$min, 'max:9999.99'],
        ], $this->messages($min));

        $bundles = [];
        foreach ($validated['bundles'] ?? [] as $i => $row) {
            $count = $row['count'] ?? null;
            $price = $row['price'] ?? null;
            if ($count === null && $price === null) {
                continue; // an empty row
            }
            if ($count === null || $price === null) {
                throw ValidationException::withMessages(["bundles.$i.count" => 'Give each bundle a number of photos and a price.']);
            }
            if (isset($bundles[(int) $count])) {
                throw ValidationException::withMessages(["bundles.$i.count" => "You already have a bundle of {$count} photos."]);
            }
            $bundles[(int) $count] = ['count' => (int) $count, 'price' => round((float) $price, 2)];
        }
        ksort($bundles);

        $request->user()->update([
            'digital_photo_price' => $validated['digital_photo_price'] ?? null,
            'digital_bundles' => array_values($bundles) ?: null,
        ]);

        return back()->with('success', 'Download prices saved.');
    }

    /** One collection: on sale or not, and its own prices. */
    public function updateCollection(Request $request, string $collectionId, DigitalPricing $pricing): RedirectResponse
    {
        $collection = $request->user()->collections()->findOrFail($collectionId);
        $min = $pricing->minimumPrice();
        $validated = $request->validate([
            'digital_sales' => ['boolean'],
            'digital_photo_price' => ['nullable', 'numeric', 'min:'.$min, 'max:9999.99'],
            'digital_collection_price' => ['nullable', 'numeric', 'min:'.$min, 'max:99999.99'],
        ], $this->messages($min));

        $collection->fill([
            'digital_sales' => $request->boolean('digital_sales'),
            'digital_photo_price' => $validated['digital_photo_price'] ?? null,
            'digital_collection_price' => $validated['digital_collection_price'] ?? null,
        ]);
        if ($collection->digital_sales && ! DigitalPricing::hasPrices($pricing->offerFor($collection))) {
            throw ValidationException::withMessages(['digital_photo_price' => 'Set a price per photo or for the whole collection here, or default prices in Photo downloads.']);
        }
        $collection->save();

        return back()->with('success', $collection->digital_sales
            ? (DigitalSales::onSale($collection) ? 'Downloads from this collection are on sale.' : 'Saved. Downloads go on sale once payouts are set up.')
            : 'Downloads from this collection are no longer for sale.');
    }

    private function messages(float $min): array
    {
        $minimum = Currency::format($min);

        return [
            'digital_photo_price.min' => "Prices start at {$minimum}, so the card fee is covered.",
            'digital_collection_price.min' => "Prices start at {$minimum}, so the card fee is covered.",
            'bundles.*.price.min' => "Prices start at {$minimum}, so the card fee is covered.",
            'bundles.*.count.min' => 'A bundle needs at least 2 photos.',
        ];
    }
}
