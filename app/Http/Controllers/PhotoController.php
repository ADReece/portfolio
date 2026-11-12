<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Set;
use App\Models\GeneratedPrint;
use App\Models\Template;
use App\Jobs\ApplyTemplateToPrint;
use App\Support\GalleryAccess;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:102400', // Max 100MB
            'set_id' => 'required|exists:sets,id'
        ]);

        $set = Set::findOrFail($request->input('set_id'));

        // Verify user owns this set
        if ($set->collection->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = auth()->user();
        $uploadedFile = $request->file('file');
        $extension = strtolower($uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'jpg');
        $fileName = time().'_'.uniqid().'.'.$extension;
        $key = 'photos/'.$user->id.'/'.$fileName;

        // The request only stores the original, streamed straight to storage
        // without decoding it. Thumbnails and the watermarked copy are made
        // by the queue worker (ProcessImageUpload), one photo per job, so a
        // large camera file can't exhaust the web container's memory.
        try {
            Storage::disk('s3')->putFileAs('photos/'.$user->id, $uploadedFile, $fileName);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => "That photo couldn't be saved. Please try again."], 500);
        }

        try {
            $photo = $set->photos()->create([
                'user_id' => $user->id,
                'url' => $key,
                'private' => $set->collection->private,
                'size' => $uploadedFile->getSize(),
                'status' => Photo::PROCESSING,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('s3')->delete($key); // never leave an orphaned file

            throw $e;
        }

        \App\Jobs\ProcessImageUpload::dispatch($photo->id);

        return response()->json([
            'success' => true,
            'fileName' => $fileName,
            'photo_id' => $photo->id,
            'status' => $photo->status,
            'message' => 'Uploaded. Preparing your photo…',
        ]);
    }

    public function update(Request $request, Photo $photo): JsonResponse
    {
        $this->authorize('update', $photo);

        $validated = $request->validate([
            'caption' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $photo->update($validated);

        return response()->json([
            'success' => true,
            'photo' => $photo,
            'message' => 'Photo updated successfully'
        ]);
    }

    public function destroy(Photo $photo): JsonResponse
    {
        $this->authorize('delete', $photo);

        // Delete from S3
        Storage::disk('s3')->delete($photo->url);
        Storage::disk('s3')->delete(str_replace('photos/', 'thumbnails/', $photo->url));
        Storage::disk('s3')->delete(str_replace('photos/', 'watermarked/', $photo->url));

        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully'
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:photos,id',
        ]);

        $photos = Photo::whereIn('id', $validated['photo_ids'])
            ->where('user_id', auth()->id())
            ->get();

        foreach ($photos as $photo) {
            Storage::disk('s3')->delete($photo->url);
            Storage::disk('s3')->delete(str_replace('photos/', 'thumbnails/', $photo->url));
            Storage::disk('s3')->delete(str_replace('photos/', 'watermarked/', $photo->url));
            $photo->delete();
        }

        return response()->json([
            'success' => true,
            'message' => count($photos) . ' photos deleted successfully'
        ]);
    }

    public function move(Request $request, Photo $photo): JsonResponse
    {
        $this->authorize('update', $photo);

        $validated = $request->validate([
            'set_id' => 'required|exists:sets,id',
        ]);

        $set = Set::findOrFail($validated['set_id']);

        // Verify user owns the target set
        if ($set->collection->user_id !== auth()->id()) {
            abort(403);
        }

        $photo->update(['set_id' => $set->id]);

        return response()->json([
            'success' => true,
            'photo' => $photo,
            'message' => 'Photo moved successfully'
        ]);
    }

    public function requestDownload(Request $request, Photo $photo): JsonResponse
    {
        abort_unless(GalleryAccess::canViewPhoto($request, $photo), 404);

        if ($photo->user?->is_demo) {
            return response()->json(['success' => false, 'message' => 'This is a demo portfolio, so no emails are sent. On your own portfolio, the client gets this by email.'], 422);
        }

        if (! \App\Downloads\DigitalSales::freeDownloadsAllowed($request, $photo->set?->collection)) {
            return response()->json([
                'success' => false,
                'message' => 'This photo is for sale. Choose “Buy photos” to download it.',
                'buy_url' => route('download-purchase.show', ['collection' => $photo->set->collection, 'photo' => $photo->id]),
            ], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // Generate a temporary download link (valid for 24 hours)
        $downloadUrl = Storage::disk('s3')->temporaryUrl(
            GalleryAccess::downloadKey($photo),
            now()->addHours(24)
        );

        // Send email with download link
        try {
            \Mail::send('emails.photo-download', [
                'photo' => $photo,
                'downloadUrl' => $downloadUrl,
                'expiresAt' => now()->addHours(24)->format('F j, Y g:i A')
            ], function ($message) use ($validated, $photo) {
                $message->to($validated['email'])
                    ->subject('Your Requested Photo Download Link');
            });

            return response()->json([
                'success' => true,
                'message' => 'Download link sent to your email'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send download email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again later.'
            ], 500);
        }
    }

    public function requestPurchase(Request $request, Photo $photo): JsonResponse
    {
        abort_unless(GalleryAccess::canViewPhoto($request, $photo), 404);

        if ($photo->user?->is_demo) {
            return response()->json(['success' => false, 'message' => 'This is a demo portfolio, so no emails are sent. On your own portfolio, the client gets this by email.'], 422);
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // Get photographer info
        $photographer = $photo->user;

        // Send email to client with purchase information
        try {
            \Mail::send('emails.photo-purchase-request', [
                'photo' => $photo,
                'photographer' => $photographer,
                'clientEmail' => $validated['email']
            ], function ($message) use ($validated, $photo) {
                $message->to($validated['email'])
                    ->subject('Photo Purchase Information');
            });

            // Optionally notify photographer
            \Mail::send('emails.purchase-notification', [
                'photo' => $photo,
                'clientEmail' => $validated['email']
            ], function ($message) use ($photographer, $photo) {
                $message->to($photographer->email)
                    ->subject('New Photo Purchase Request');
            });

            return response()->json([
                'success' => true,
                'message' => 'Purchase information sent to your email'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send purchase email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again later.'
            ], 500);
        }
    }

    public function downloadOptions(Request $request, Photo $photo)
    {
        abort_unless($photo->isReady() && GalleryAccess::canViewPhoto($request, $photo), 404);

        // Photos in a collection that sells downloads are bought, not downloaded free.
        $collection = $photo->set?->collection;
        if (! \App\Downloads\DigitalSales::freeDownloadsAllowed($request, $collection)) {
            return redirect()->route('download-purchase.show', ['collection' => $collection, 'photo' => $photo->id]);
        }

        $set       = $photo->set?->load('templates.generatedPrints');
        $templates = $set?->templates ?? collect();

        return view('photos.download-options', compact('photo', 'templates'));
    }

    public function downloadWithTemplate(Request $request, Photo $photo, Template $template)
    {
        abort_unless(GalleryAccess::canViewPhoto($request, $photo), 404);
        if (! \App\Downloads\DigitalSales::freeDownloadsAllowed($request, $photo->set?->collection)) {
            return redirect()->route('download-purchase.show', ['collection' => $photo->set->collection, 'photo' => $photo->id]);
        }

        // Ensure template belongs to this photo's set
        $set = $photo->set;
        abort_unless($set && $set->templates->contains($template->id), 404);

        // Build print_data from submitted field values
        $printData = [];
        foreach ($template->fields as $field) {
            $printData[$field['key']] = $request->input('fields.' . $field['key'], '');
        }

        // The public demo keeps visitors' designs for two hours only.
        if ($photo->user?->is_demo) {
            GeneratedPrint::query()->where('template_id', $template->id)->where('created_at', '<', now()->subHours(2))->get()
                ->each(function (GeneratedPrint $old) {
                    if ($old->output_path) {
                        Storage::disk('s3')->delete($old->output_path);
                    }
                    $old->delete();
                });
        }

        $generatedPrint = GeneratedPrint::create([
            'photo_id'    => $photo->id,
            'template_id' => $template->id,
            'print_data'  => $printData,
            'status'      => 'pending',
        ]);

        ApplyTemplateToPrint::dispatchSync($generatedPrint);
        $generatedPrint->refresh();

        return redirect()->route('photos.download-result', [$photo, $generatedPrint]);
    }

    public function downloadResult(Request $request, Photo $photo, GeneratedPrint $generatedPrint)
    {
        abort_unless(GalleryAccess::canViewPhoto($request, $photo), 404);
        abort_unless($generatedPrint->photo_id === $photo->id, 404);

        $outputUrl = null;
        if ($generatedPrint->isCompleted() && $generatedPrint->output_path) {
            $outputUrl = Storage::disk('s3')->temporaryUrl($generatedPrint->output_path, now()->addMinutes(30));
        }

        return view('photos.download-result', compact('photo', 'generatedPrint', 'outputUrl'));
    }
}
