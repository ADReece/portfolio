<?php

namespace App\Downloads;

use App\Models\Collection;
use App\Models\Photo;
use App\Models\Set;
use App\Support\GalleryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Works out which photos a visitor may download together (a selection, a
 * set, a whole collection) and under what names. It applies the same rules
 * as single downloads:
 *  - the gallery must be visible to them (published, or unlocked if private);
 *  - photos hidden from the public portfolio stay out of public galleries;
 *  - photos in a collection that sells downloads are bought, not downloaded;
 *  - watermarked photos are handed out watermarked (GalleryAccess::downloadKey);
 *  - the photographer always gets their own originals.
 */
class BulkDownload
{
    /** @var list<array{key: string, name: string, size: int}> */
    public array $files = [];

    public string $zipName = 'photos.zip';

    public ?Collection $collection = null;

    public ?string $ownerId = null;

    /** Photos left out because they're for sale (none free to download). */
    public int $forSale = 0;

    public ?string $buyUrl = null;

    public static function resolve(Request $request, ?Collection $collection, ?Set $set, ?array $photoIds): self
    {
        $result = new self;
        $user = $request->user();
        $max = (int) config('downloads.max_photos');

        if ($set) {
            $collection = $set->collection;
        }

        $query = Photo::query()->ready()->with('set.collection.user');
        if ($collection) {
            abort_unless(self::canOpenGallery($request, $collection), 404);
            $result->collection = $collection;
            $query->whereIn('set_id', $set ? [$set->id] : $collection->sets()->pluck('id'));
        } elseif ($photoIds === null) {
            abort(404);
        }
        if ($photoIds !== null) {
            $query->whereIn('photos.id', array_slice(array_map('strval', $photoIds), 0, $max + 1));
        }

        $photos = $query->limit($max + 1)->get();
        if ($photos->count() > $max) {
            throw ValidationException::withMessages(['photo_ids' => "That's more than {$max} photos. Choose fewer, or download a set at a time."]);
        }
        // A selection keeps the order it was chosen in (the gallery's);
        // otherwise gallery order: set by set, then each set's own order.
        if ($photoIds !== null) {
            $position = array_flip(array_map('strval', $photoIds));
            $photos = $photos->sortBy(fn ($p) => $position[(string) $p->id] ?? PHP_INT_MAX)->values();
        } else {
            $photos = $photos->sortBy([
                fn ($a, $b) => [$a->set?->collection_id, $a->set?->sort_order ?? PHP_INT_MAX, $a->set?->created_at, $a->set_id]
                    <=> [$b->set?->collection_id, $b->set?->sort_order ?? PHP_INT_MAX, $b->set?->created_at, $b->set_id],
                fn ($a, $b) => [$a->sort_order ?? PHP_INT_MAX, $a->created_at] <=> [$b->sort_order ?? PHP_INT_MAX, $b->created_at],
            ])->values();
        }

        $chosen = [];
        foreach ($photos as $photo) {
            $photoCollection = $photo->set?->collection;
            $isOwner = $user && $user->id === $photo->user_id;

            if (! $isOwner) {
                if (! $photoCollection || ! GalleryAccess::canViewPhoto($request, $photo)) {
                    continue;
                }
                if (! DigitalSales::freeDownloadsAllowed($request, $photoCollection)) {
                    $result->forSale++;
                    $result->buyUrl ??= route('download-purchase.show', $photoCollection);

                    continue;
                }
            }

            $chosen[] = [$photo, $isOwner ? $photo->url : GalleryAccess::downloadKey($photo)];
            $result->ownerId ??= $photo->user_id;
        }

        $base = self::baseName($set?->name ?? $collection?->name ?? self::photographerName($photos->first()));
        $result->zipName = $base.'.zip';
        $result->files = self::name($chosen, $base);

        return $result;
    }

    public function count(): int
    {
        return count($this->files);
    }

    public function totalBytes(): int
    {
        return array_sum(array_column($this->files, 'size'));
    }

    public function isSmall(): bool
    {
        return $this->count() <= (int) config('downloads.instant_max_photos')
            && $this->totalBytes() <= (int) config('downloads.instant_max_bytes');
    }

    /** Private galleries need unlocking; public ones must be published and not hidden. */
    public static function canOpenGallery(Request $request, Collection $collection): bool
    {
        if ($request->user() && $request->user()->id === $collection->user_id) {
            return true;
        }

        return $collection->private
            ? GalleryAccess::collectionIsUnlocked($request, $collection)
            : $collection->status === 'Published' && ! $collection->hide_from_portfolio;
    }

    /**
     * Names inside the ZIP: the camera's original filename where we have it
     * (so clients and Lightroom recognise them), otherwise "<gallery>-001.jpg".
     * Duplicates become "name (2).jpg". The extension follows the file
     * actually sent (a watermarked copy may differ from the original).
     *
     * @param  list<array{0: Photo, 1: string}>  $chosen
     * @return list<array{key: string, name: string, size: int}>
     */
    private static function name(array $chosen, string $base): array
    {
        $files = [];
        $used = [];
        foreach ($chosen as $i => [$photo, $key]) {
            $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION)) ?: 'jpg';
            $original = self::safe(pathinfo((string) $photo->original_filename, PATHINFO_FILENAME));
            $stem = $original !== '' ? $original : Str::slug($base).'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            $name = $stem.'.'.$extension;
            for ($n = 2; isset($used[strtolower($name)]); $n++) {
                $name = $stem.' ('.$n.').'.$extension;
            }
            $used[strtolower($name)] = true;

            $files[] = ['key' => $key, 'name' => $name, 'size' => (int) $photo->size];
        }

        return $files;
    }

    /** A name that's safe as a file name on every system. */
    public static function safe(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F\/\\\\:*?"<>|]+/u', '-', $name) ?? '';
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '', " .-");

        return Str::limit($name, 120, '');
    }

    private static function baseName(string $name): string
    {
        return self::safe($name) ?: 'photos';
    }

    private static function photographerName(?Photo $photo): string
    {
        $owner = $photo?->set?->collection?->user;

        return $owner ? ($owner->name ?: $owner->username).' photos' : 'photos';
    }
}
