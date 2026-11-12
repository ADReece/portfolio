<?php

namespace App\Http\Controllers;

use App\Models\FavouriteList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** The photographer's view of clients' favourites in a collection. */
class ClientFavouritesController extends Controller
{
    public function index(Request $request, string $collectionId): View
    {
        $collection = $request->user()->collections()->findOrFail($collectionId);

        return view('collections.backend.favourites', [
            'collection' => $collection,
            'lists' => $collection->favouriteLists()->withCount('photos')->having('photos_count', '>', 0)
                ->orderByRaw('submitted_at is null')->orderByDesc('submitted_at')->orderByDesc('updated_at')->get(),
        ]);
    }

    public function show(Request $request, string $collectionId, string $listId): View
    {
        [$collection, $list] = $this->find($request, $collectionId, $listId);

        return view('collections.backend.favourite-list', [
            'collection' => $collection,
            'list' => $list,
            'photos' => $list->photos()->orderBy('photos.created_at')->orderBy('photos.url')->get(),
        ]);
    }

    /** One filename per line, e.g. to paste into Lightroom's filename filter. */
    public function export(Request $request, string $collectionId, string $listId): Response
    {
        [$collection, $list] = $this->find($request, $collectionId, $listId);
        $name = Str::slug($collection->name.' '.$list->clientLabel().' favourites').'.txt';

        return response(implode("\n", $list->filenames())."\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    public function destroy(Request $request, string $collectionId, string $listId): RedirectResponse
    {
        [$collection, $list] = $this->find($request, $collectionId, $listId);
        $list->delete();

        return redirect()->route('collections.client-favourites.index', $collection->id)->with('success', 'Favourites deleted.');
    }

    /** Whether clients can pick favourites here, and whether to email the photographer. */
    public function settings(Request $request, string $collectionId): RedirectResponse
    {
        $collection = $request->user()->collections()->findOrFail($collectionId);
        $collection->update(['favourites_enabled' => $request->boolean('favourites_enabled')]);
        $request->user()->update(['notify_favourites' => $request->boolean('notify_favourites')]);

        return back()->with('success', 'Favourites settings saved.');
    }

    private function find(Request $request, string $collectionId, string $listId): array
    {
        $collection = $request->user()->collections()->findOrFail($collectionId);
        $list = $collection->favouriteLists()->findOrFail($listId);

        return [$collection, $list];
    }
}
