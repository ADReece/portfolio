<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class CollectionController extends Controller
{

    public function index(Request $request) : View
    {
        $filters = [
            'all' => 'All',
            'published' => 'Published',
            'draft' => 'Drafts',
            'private' => 'Private',
            'archived' => 'Archived',
        ];
        $filter = array_key_exists($request->query('show'), $filters) ? $request->query('show') : 'all';

        $base = auth()->user()->collections();
        $counts = [
            'all' => (clone $base)->count(),
            'published' => (clone $base)->where('status', 'Published')->count(),
            'draft' => (clone $base)->where('status', 'Draft')->count(),
            'private' => (clone $base)->where('private', true)->count(),
            'archived' => (clone $base)->where('status', 'Archived')->count(),
        ];

        $collections = $base
            ->when($filter === 'published', fn ($q) => $q->where('status', 'Published'))
            ->when($filter === 'draft', fn ($q) => $q->where('status', 'Draft'))
            ->when($filter === 'private', fn ($q) => $q->where('private', true))
            ->when($filter === 'archived', fn ($q) => $q->where('status', 'Archived'))
            ->with('coverPhoto')
            ->withCount(['sets', 'photos'])
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return view('collections.backend.index', compact('collections', 'filters', 'filter', 'counts'));
    }

    public function sets($collection) : View
    {
        return view('collections.backend.sets', ['collection' => $collection]);
    }



    public function create() : View
    {
        return view('collections.backend.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_date' => 'nullable|date',
            'status' => 'nullable|string|in:Draft,Published,Archived',
            'private' => 'nullable|boolean',
            'password' => 'nullable|string|min:4',
        ]);

        $collection = auth()->user()->collections()->create([
            'name' => $validated['name'],
            'event_date' => $validated['event_date'] ?? null,
            'status' => $validated['status'] ?? 'Draft',
            'private' => $validated['private'] ?? false,
            'password' => $validated['password'] ? bcrypt($validated['password']) : null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'collection' => $collection,
                'message' => 'Collection created successfully'
            ]);
        }

        return redirect()->route('collections.edit', $collection)
            ->with('success', 'Collection created successfully');
    }

    public function edit($collectionId) : View
    {
        $collection = auth()->user()->collections()->withCount(['sets', 'photos'])->findOrFail($collectionId);

        // Photos to choose a cover from: the most recent, plus the current cover.
        $coverChoices = $collection->photos()->ready()->latest('photos.created_at')->take(24)->get();
        if ($collection->cover_photo_id && ! $coverChoices->contains('id', $collection->cover_photo_id) && $collection->coverPhoto) {
            $coverChoices->prepend($collection->coverPhoto);
        }

        return view('collections.backend.edit', [
            'collection' => $collection,
            'coverChoices' => $coverChoices,
            'shareUrl' => route('profile.collection', ['username' => auth()->user()->username, 'collection_id' => $collection->id]),
        ]);
    }

    public function update(Request $request, $collectionId)
    {
        $collection = auth()->user()->collections()->findOrFail($collectionId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_date' => 'nullable|date',
            'status' => 'nullable|string|in:Draft,Published,Archived',
            'private' => 'nullable|boolean',
            // A private collection needs a password, unless it already has one.
            'password' => [$collection->password ? 'nullable' : 'required_if:private,1', 'nullable', 'string', 'min:4'],
            // Only photos from this collection can be its cover.
            'cover_photo_id' => ['nullable', \Illuminate\Validation\Rule::in($collection->photos()->ready()->pluck('photos.id')->map(fn ($id) => (string) $id)->all())],
            'watermarked' => 'nullable|boolean',
            'hide_from_portfolio' => 'nullable|boolean',
            'cover_photo_object_position' => 'nullable|string|in:center,top,bottom,top-right,bottom-right',
        ], [
            'password.required_if' => 'Set a password so clients can open this private collection.',
            'cover_photo_id.in' => 'Choose a cover photo from this collection.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'event_date' => $validated['event_date'] ?? null,
            'status' => $validated['status'] ?? $collection->status,
            'private' => (bool) $request->input('private', 0),
            'cover_photo_id' => $validated['cover_photo_id'] ?? $collection->cover_photo_id,
            'watermarked' => (bool) $request->input('watermarked', 0),
            'hide_from_portfolio' => (bool) $request->input('hide_from_portfolio', 0),
            'cover_photo_object_position' => $validated['cover_photo_object_position'] ?? ($collection->cover_photo_object_position ?? 'center'),
        ];

        if (isset($validated['password']) && $validated['password']) {
            $updateData['password'] = bcrypt($validated['password']);
        }

        $collection->update($updateData);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'collection' => $collection,
                'message' => 'Collection updated successfully'
            ]);
        }

        return back()->with('success', 'Collection updated successfully');
    }

    public function destroy($collectionId)
    {
        $collection = auth()->user()->collections()->findOrFail($collectionId);
        $collection->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collection deleted successfully'
            ]);
        }

        return redirect()->route('collections.index')
            ->with('success', 'Collection deleted successfully');
    }

    public function emailClient(Request $request, $collectionId)
    {
        $collection = auth()->user()->collections()->findOrFail($collectionId);

        // Verify collection is private and published
        if (!$collection->private || $collection->status !== 'Published') {
            return response()->json([
                'success' => false,
                'message' => 'This collection cannot be shared via email'
            ], 400);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            // Passwords are stored hashed, so instead of the password we send a
            // signed link that unlocks the gallery for the client.
            $linkExpiresAt = now()->addDays(30);
            $collectionUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('profile.collection', $linkExpiresAt, [
                'username' => auth()->user()->username,
                'collection_id' => $collection->id,
            ]);

            \Mail::send('emails.collection-share', [
                'collection' => $collection,
                'collectionUrl' => $collectionUrl,
                'clientName' => $validated['name'] ?? 'there',
                'customMessage' => $validated['message'] ?? null,
                'photographerName' => auth()->user()->name ?? auth()->user()->username,
                'linkExpiresAt' => $linkExpiresAt,
            ], function ($message) use ($validated, $collection) {
                $message->to($validated['email'])
                    ->subject('Your Private Gallery: ' . $collection->name);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send collection email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again later.'
            ], 500);
        }
    }

    public function requestArchive(Request $request, $collectionId)
    {
        $collection = \App\Models\Collection::findOrFail($collectionId);

        // Verify collection is private
        if (!$collection->private) {
            return response()->json([
                'success' => false,
                'message' => 'Archive download is only available for private collections'
            ], 403);
        }

        if (! \App\Support\GalleryAccess::collectionIsUnlocked($request, $collection)) {
            return response()->json([
                'success' => false,
                'message' => 'Open the gallery with its password before requesting a download.'
            ], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // Dispatch the job to generate the archive
            \App\Jobs\GenerateCollectionArchive::dispatch($collectionId, $validated['email']);

            return response()->json([
                'success' => true,
                'message' => 'Archive is being prepared. You will receive an email with the download link shortly.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to dispatch archive generation job: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process your request. Please try again later.'
            ], 500);
        }
    }

    public function archiveDownloadPage($collectionId, $filename)
    {
        $collection = \App\Models\Collection::findOrFail($collectionId);

        // Verify collection is private
        if (!$collection->private) {
            abort(403, 'Archive download is only available for private collections');
        }

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);

        // Verify filename matches the collection ID pattern
        if (!preg_match('/^collection-' . $collectionId . '-\d+\.zip$/', $filename)) {
            abort(403, 'Invalid archive file');
        }

        // Check if file exists in S3
        if (!Storage::disk('s3')->exists('archives/' . $filename)) {
            abort(404, 'Archive file not found or has expired');
        }

        // Extract timestamp from filename to calculate expiration
        preg_match('/\-(\d+)\.zip$/', $filename, $matches);
        $timestamp = $matches[1] ?? time();
        $expiresAt = \Carbon\Carbon::createFromTimestamp($timestamp)->addHours(48);

        // Check if expired
        if (now()->gt($expiresAt)) {
            abort(410, 'Download link has expired');
        }

        // Get photo count
        $photoCount = $collection->sets()
            ->with('photos')
            ->get()
            ->pluck('photos')
            ->flatten()
            ->count();

        return view('collections.archive-download', [
            'collection' => $collection,
            'user' => $collection->user,
            'downloadUrl' => \Illuminate\Support\Facades\URL::temporarySignedRoute('collections.download-archive', $expiresAt, [
                'collection' => $collection->id,
                'filename' => $filename,
            ]),
            'filename' => $filename,
            'expiresAt' => $expiresAt->toIso8601String(),
            'photoCount' => $photoCount,
        ]);
    }

    public function downloadArchive($collectionId, $filename)
    {
        $collection = \App\Models\Collection::findOrFail($collectionId);

        // Verify collection is private
        if (!$collection->private) {
            abort(403, 'Archive download is only available for private collections');
        }

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);

        // Verify filename matches the collection ID pattern
        if (!preg_match('/^collection-' . $collectionId . '-\d+\.zip$/', $filename)) {
            abort(403, 'Invalid archive file');
        }

        // Check if file exists in S3
        if (!Storage::disk('s3')->exists('archives/' . $filename)) {
            abort(404, 'Archive file not found or has expired');
        }

        // Extract timestamp from filename to calculate expiration
        preg_match('/\-(\d+)\.zip$/', $filename, $matches);
        $timestamp = $matches[1] ?? time();
        $expiresAt = \Carbon\Carbon::createFromTimestamp($timestamp)->addHours(48);

        // Check if expired
        if (now()->gt($expiresAt)) {
            abort(410, 'Download link has expired');
        }

        // Generate a temporary signed URL (valid for 1 hour)
        $url = Storage::disk('s3')->temporaryUrl(
            'archives/' . $filename,
            now()->addHour()
        );

        // Redirect to the signed S3 URL
        return redirect($url);
    }
}
