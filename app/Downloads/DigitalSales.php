<?php

namespace App\Downloads;

use App\Billing\Payouts;
use App\Models\Collection;
use App\Models\Photo;
use App\Support\GalleryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as Items;

/**
 * Whether a collection's downloads are on sale, and which of its photos the
 * current visitor can buy. While a collection sells downloads, its free
 * downloads (single photos and "Download all") are turned off.
 */
class DigitalSales
{
    public static function onSale(?Collection $collection): bool
    {
        $owner = $collection?->user;

        return $collection !== null && $owner !== null
            && $collection->digital_sales
            && Payouts::available()
            && $owner->hasFeature('selling')
            && $owner->stripe_connect_enabled && $owner->stripe_connect_id
            && DigitalPricing::hasPrices(app(DigitalPricing::class)->offerFor($collection, $owner));
    }

    /** Can this visitor download photos from the collection for free? */
    public static function freeDownloadsAllowed(Request $request, ?Collection $collection): bool
    {
        if ($collection && $request->user() && $request->user()->id === $collection->user_id) {
            return true; // the photographer can always download their own photos
        }

        return ! self::onSale($collection);
    }

    /**
     * The collection's ready photos this visitor can see, in gallery order.
     *
     * @return Items<int, Photo>
     */
    public static function photosFor(Request $request, Collection $collection): Items
    {
        return $collection->photos()->ready()
            ->with('set.collection')
            ->orderBy('photos.created_at', 'desc')
            ->get()
            ->filter(fn (Photo $photo) => GalleryAccess::canViewPhoto($request, $photo))
            ->values();
    }
}
