<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\FavouriteList;
use App\Models\Photo;
use App\Support\GalleryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Client favourites (proofing): visitors heart photos in a gallery and send
 * their picks to the photographer. Each browser's picks are one
 * FavouriteList, remembered by an (encrypted) cookie for six months.
 */
class FavouriteController extends Controller
{
    private const COOKIE_DAYS = 182;

    public function show(Request $request, Collection $collection): JsonResponse
    {
        $this->authorizeGallery($request, $collection);
        $list = $this->list($request, $collection);

        return response()->json($this->state($list));
    }

    public function toggle(Request $request, Collection $collection, Photo $photo): JsonResponse
    {
        $this->authorizeGallery($request, $collection);
        abort_unless($photo->set?->collection_id === $collection->id && $photo->isReady() && GalleryAccess::canViewPhoto($request, $photo), 404);

        $list = $this->list($request, $collection);
        if (! $list) {
            if ($collection->favouriteLists()->count() >= FavouriteList::MAX_LISTS_PER_COLLECTION) {
                return response()->json(['message' => 'Favourites are full for this gallery. Please contact the photographer.'], 422);
            }
            $list = $collection->favouriteLists()->create();
        }

        if ($list->photos()->whereKey($photo->id)->exists()) {
            $list->photos()->detach($photo->id);
        } else {
            if ($list->photos()->count() >= FavouriteList::MAX_PHOTOS) {
                return response()->json(['message' => 'That\'s the most favourites one person can pick.'], 422);
            }
            $list->photos()->attach($photo->id, ['created_at' => now()]);
        }
        $list->touch();

        return $this->withCookie(response()->json($this->state($list)), $collection, $list);
    }

    public function submit(Request $request, Collection $collection): JsonResponse
    {
        $this->authorizeGallery($request, $collection);
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $list = $this->list($request, $collection);
        if (! $list || ! $list->photos()->exists()) {
            return response()->json(['message' => 'Heart at least one photo first.'], 422);
        }

        $first = $list->submitted_at === null;
        $list->fill($data + ['name' => null, 'email' => null, 'note' => null])->forceFill(['submitted_at' => now()])->save();

        $photographer = $collection->user;
        if ($photographer && $photographer->notify_favourites && ! $photographer->is_demo) {
            try {
                Mail::send('emails.favourites-submitted', [
                    'list' => $list,
                    'collection' => $collection,
                    'count' => $list->photos()->count(),
                    'url' => route('collections.client-favourites.show', [$collection->id, $list->id]),
                    'updated' => ! $first,
                ], function ($message) use ($photographer, $list, $collection) {
                    $message->to($photographer->email)
                        ->subject($list->clientLabel().' sent their favourites from '.$collection->name);
                    if ($list->email) {
                        $message->replyTo($list->email, $list->name);
                    }
                });
            } catch (\Throwable $e) {
                Log::warning('Favourites notification failed', ['list_id' => $list->id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json($this->state($list) + [
            'message' => $photographer?->is_demo
                ? 'Sent! (This is a demo, so no email goes out. A real photographer would see your picks straight away.)'
                : 'Sent! '.($photographer?->name ?: 'The photographer').' will see your favourites.',
        ]);
    }

    private function authorizeGallery(Request $request, Collection $collection): void
    {
        $owner = $request->user() && $request->user()->id === $collection->user_id;
        abort_unless($collection->favourites_enabled, 404);
        abort_unless($owner || ($collection->private
            ? GalleryAccess::collectionIsUnlocked($request, $collection)
            : $collection->status === 'Published'), 404);
    }

    private function list(Request $request, Collection $collection): ?FavouriteList
    {
        $id = $request->cookie($this->cookieName($collection));

        return $id ? $collection->favouriteLists()->whereKey($id)->first() : null;
    }

    private function state(?FavouriteList $list): array
    {
        return [
            'ids' => $list ? $list->photos()->pluck('photos.id')->map(fn ($id) => (string) $id)->values() : [],
            'submitted_at' => $list?->submitted_at?->toIso8601String(),
            'name' => $list?->name,
            'email' => $list?->email,
            'note' => $list?->note,
        ];
    }

    private function withCookie(JsonResponse $response, Collection $collection, FavouriteList $list): JsonResponse
    {
        return $response->withCookie(Cookie::make($this->cookieName($collection), $list->id, self::COOKIE_DAYS * 24 * 60, '/', null, null, true, false, 'Lax'));
    }

    private function cookieName(Collection $collection): string
    {
        return 'pf_fav_'.str_replace('-', '', (string) $collection->id);
    }
}
