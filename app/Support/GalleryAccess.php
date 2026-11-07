<?php

namespace App\Support;

use App\Models\Collection;
use App\Models\Photo;
use Illuminate\Http\Request;

/**
 * Who can see what. Shared by the gallery, downloads and print purchases so
 * every client-facing action follows the same rules.
 */
class GalleryAccess
{
    private const SESSION_KEY = 'unlocked_collections';

    /** A private collection is open to its owner and to clients who unlocked it. */
    public static function collectionIsUnlocked(Request $request, Collection $collection): bool
    {
        if (! $collection->private) {
            return true;
        }
        if ($request->user() && $request->user()->id === $collection->user_id) {
            return true;
        }

        return in_array((string) $collection->id, $request->session()->get(self::SESSION_KEY, []), true);
    }

    public static function unlockCollection(Request $request, Collection $collection): void
    {
        $unlocked = $request->session()->get(self::SESSION_KEY, []);
        $unlocked[] = (string) $collection->id;
        $request->session()->put(self::SESSION_KEY, array_values(array_unique($unlocked)));
    }

    /**
     * Can the current visitor see this photo? Photos in private galleries need
     * the gallery unlocked; photos elsewhere must be on the public portfolio.
     */
    public static function canViewPhoto(Request $request, Photo $photo): bool
    {
        $collection = $photo->set?->collection;

        if (! $collection) {
            return false;
        }
        if ($request->user() && $request->user()->id === $photo->user_id) {
            return true;
        }
        if ($collection->private) {
            return self::collectionIsUnlocked($request, $collection);
        }

        return $collection->status === 'Published'
            && ! $photo->private
            && ! $photo->hide_from_portfolio
            && ! $photo->set->hide_from_portfolio
            && ! $collection->hide_from_portfolio;
    }

    /** Watermarked photos must never be handed out as clean originals. */
    public static function downloadKey(Photo $photo): string
    {
        $watermarked = $photo->watermarked || $photo->set?->collection?->watermarked;

        return $watermarked ? str_replace('photos/', 'watermarked/', $photo->url) : $photo->url;
    }
}
