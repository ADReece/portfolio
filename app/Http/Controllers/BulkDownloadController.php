<?php

namespace App\Http\Controllers;

use App\Downloads\ArchiveWriter;
use App\Downloads\BulkDownload;
use App\Jobs\BuildDownloadArchive;
use App\Models\Collection;
use App\Models\DownloadArchive;
use App\Models\Set;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloading several photos as one ZIP: a selection, a set, a collection or
 * a client's favourites. Small downloads stream straight away; big ones are
 * built in the background and offered (and optionally emailed) when ready.
 */
class BulkDownloadController extends Controller
{
    /** Checks what can be downloaded and says how: a link to stream now, or a page to wait on. */
    public function prepare(Request $request): JsonResponse
    {
        $data = $request->validate([
            'collection_id' => ['nullable', 'uuid'],
            'set_id' => ['nullable', 'uuid'],
            'photo_ids' => ['nullable', 'array', 'min:1', 'max:'.(int) config('downloads.max_photos')],
            'photo_ids.*' => ['string', 'max:64'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
        ]);

        $set = isset($data['set_id']) ? Set::find($data['set_id']) : null;
        $collection = isset($data['collection_id']) ? Collection::find($data['collection_id']) : null;
        abort_if((isset($data['set_id']) && ! $set) || (isset($data['collection_id']) && ! $collection), 404);
        abort_if($set && $collection && $set->collection_id !== $collection->id, 404);

        $download = BulkDownload::resolve($request, $collection, $set, $data['photo_ids'] ?? null);

        if ($download->count() === 0) {
            return $download->forSale > 0
                ? response()->json(['message' => 'These photos are for sale. Choose “Buy photos” to download them.', 'buy_url' => $download->buyUrl], 403)
                : response()->json(['message' => 'There are no photos to download here yet.'], 422);
        }
        if ($download->totalBytes() > (int) config('downloads.max_bytes')) {
            return response()->json(['message' => 'That\'s too much to download in one go. Download a set at a time.'], 422);
        }

        $note = $download->forSale > 0
            ? $download->forSale.' '.Str::plural('photo', $download->forSale).' for sale '.($download->forSale === 1 ? 'was' : 'were').' left out.'
            : null;
        $owner = $download->ownerId ? \App\Models\User::find($download->ownerId) : null;

        if ($download->isSmall()) {
            $archive = $this->record($request, $download, DownloadArchive::INSTANT, now()->addMinutes((int) config('downloads.instant_link_minutes')));

            return response()->json(['mode' => 'instant', 'url' => $archive->streamUrl(), 'count' => $download->count(), 'name' => $download->zipName, 'note' => $note]);
        }

        // Building a big ZIP costs real work: a few at a time per visitor, and not for the public demo.
        if ($owner?->is_demo && ! ($request->user()?->id === $owner->id)) {
            return response()->json(['message' => 'This is a demo portfolio, so big downloads are switched off. Select up to '.config('downloads.instant_max_photos').' photos to download them straight away.'], 422);
        }
        $building = DownloadArchive::where('requester_ip', $request->ip())
            ->whereIn('status', [DownloadArchive::PENDING, DownloadArchive::BUILDING])
            ->where('created_at', '>', now()->subHour())->count();
        if ($building >= (int) config('downloads.max_building_per_ip')) {
            return response()->json(['message' => 'You already have downloads being prepared. Please wait for those to finish.'], 429);
        }

        $archive = $this->record($request, $download, DownloadArchive::BACKGROUND, now()->addHours((int) config('downloads.archive_hours')), $data['email'] ?? null);
        BuildDownloadArchive::dispatch($archive->id);

        return response()->json([
            'mode' => 'background',
            'count' => $download->count(),
            'name' => $download->zipName,
            'status_url' => $archive->pageUrl(),
            'email_url' => $archive->emailUrl(),
            'note' => $note,
        ], 202);
    }

    /** An instant download: the ZIP is written to the browser as each photo is read. */
    public function stream(DownloadArchive $archive): Response
    {
        abort_unless($archive->mode === DownloadArchive::INSTANT && ! $archive->isExpired(), 410, 'This download link has expired. Go back to the gallery and choose Download again.');
        $files = $archive->files;

        return new StreamedResponse(function () use ($files) {
            @set_time_limit(0);
            ArchiveWriter::write($files);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => $this->disposition($archive->name),
            'X-Accel-Buffering' => 'no', // stream through nginx rather than buffering
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /** The waiting page for a background download (JSON for the gallery's progress dialog). */
    public function archive(Request $request, DownloadArchive $archive): Response
    {
        abort_unless($archive->mode === DownloadArchive::BACKGROUND, 404);
        $state = [
            'status' => $archive->isExpired() ? 'expired' : $archive->status,
            'done' => (int) $archive->done_count,
            'total' => (int) $archive->photo_count,
            'name' => $archive->name,
            'download_url' => $archive->isReady() ? $archive->fileUrl() : null,
            'email' => $archive->email ? Str::mask($archive->email, '*', 2, max(0, strpos($archive->email, '@') - 2)) : null,
            'message' => $archive->status === DownloadArchive::FAILED ? $archive->error : null,
        ];

        if ($request->expectsJson()) {
            return response()->json($state);
        }

        return response()->view('downloads.archive', [
            'archive' => $archive->load('collection', 'user'),
            'state' => $state,
            'statusUrl' => $archive->pageUrl(),
            'emailUrl' => $archive->emailUrl(),
        ])->header('X-Robots-Tag', 'noindex');
    }

    /** "Email me the link too": sent when the ZIP is ready (or now, if it already is). */
    public function email(Request $request, DownloadArchive $archive): JsonResponse
    {
        abort_unless($archive->mode === DownloadArchive::BACKGROUND && ! $archive->isExpired(), 404);
        $data = $request->validate(['email' => ['required', app()->runningUnitTests() ? 'email:rfc' : 'email:rfc,dns', 'max:255']]);

        if ($archive->emailed_at) {
            return response()->json(['message' => 'We\'ve already emailed the link for this download.'], 422);
        }
        $archive->update(['email' => $data['email']]);
        BuildDownloadArchive::emailIfAsked($archive->fresh());

        return response()->json(['message' => $archive->fresh()->emailed_at
            ? 'We\'ve emailed the link to '.$data['email'].'.'
            : 'We\'ll email '.$data['email'].' when it\'s ready.']);
    }

    /** The built ZIP, from storage (a short-lived link). */
    public function file(DownloadArchive $archive): RedirectResponse
    {
        abort_unless($archive->mode === DownloadArchive::BACKGROUND, 404);
        abort_unless($archive->isReady(), 410, 'This download has expired. Go back to the gallery and choose Download again.');

        return redirect()->away(Storage::disk('s3')->temporaryUrl($archive->path, now()->addMinutes(30), [
            'ResponseContentDisposition' => $this->disposition($archive->name),
        ]));
    }

    private function record(Request $request, BulkDownload $download, string $mode, $expires, ?string $email = null): DownloadArchive
    {
        return DownloadArchive::create([
            'collection_id' => $download->collection?->id,
            'user_id' => $download->ownerId,
            'mode' => $mode,
            'name' => $download->zipName,
            'files' => $download->files,
            'photo_count' => $download->count(),
            'total_bytes' => $download->totalBytes(),
            'status' => $mode === DownloadArchive::INSTANT ? DownloadArchive::READY : DownloadArchive::PENDING,
            'email' => $email,
            'requester_ip' => $request->ip(),
            'expires_at' => $expires,
        ]);
    }

    private function disposition(string $name): string
    {
        $fallback = Str::ascii($name);
        $fallback = preg_replace('/[^A-Za-z0-9 .()-]/', '-', $fallback) ?: 'photos.zip';

        return HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, $fallback);
    }
}
